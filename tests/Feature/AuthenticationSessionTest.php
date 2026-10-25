<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['inertia.ssr.enabled' => false]);
        // Laravel normally skips CSRF checks in tests. Exercise the real check.
        $this->app->bind(ValidateCsrfToken::class, EnforceAuthCsrfToken::class);
    }

    private function token(): string
    {
        $response = $this->getJson('/auth/session')->assertOk()->assertJsonStructure(['token']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertCookie(config('session.cookie'));

        return $response->json('token');
    }

    public function test_login_uses_current_token_rotates_session_and_returns_relative_destination(): void
    {
        foreach (['user' => '/', 'reseller' => '/', 'admin' => '/admin/dashboard'] as $role => $target) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $token = $this->token();
            $sessionId = session()->getId();

            $this->postJson('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true], [
                'X-CSRF-TOKEN' => $token,
                // An older app on the parent domain may have set this cookie.
                'X-XSRF-TOKEN' => 'stale-cookie-from-another-app',
            ])->assertOk()->assertJsonPath('redirect', $target)
                ->assertCookie(Auth::guard()->getRecallerName());

            $this->assertAuthenticatedAs($user);
            $this->assertNotSame($sessionId, session()->getId());
            $this->assertNotSame($token, session()->token());
            if ($role !== 'admin') {
                Auth::forgetGuards();
                $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
                    ->component('Home')->where('auth.user.id', $user->id));
            }
            Auth::logout();
        }
    }

    public function test_stale_token_is_rejected_then_fresh_token_allows_registration(): void
    {
        $payload = ['name' => 'Customer', 'email' => 'new@example.test', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'user'];
        $this->token();
        $this->postJson('/register', $payload, ['X-CSRF-TOKEN' => 'expired'])
            ->assertStatus(419);
        $this->assertDatabaseCount('users', 0);

        $token = $this->token();
        $this->postJson('/register', $payload, ['X-CSRF-TOKEN' => $token])
            ->assertOk()->assertJsonPath('redirect', '/');
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
        $this->assertNotSame($token, session()->token());
    }

    public function test_registration_accepts_fresh_body_token_when_headers_are_replaced(): void
    {
        $token = $this->token();
        $this->postJson('/register', [
            'name' => 'Proxy Customer', 'email' => 'proxy@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            '_token' => $token,
        ], ['X-CSRF-TOKEN' => 'stale-header', 'X-XSRF-TOKEN' => 'stale-cookie'])
            ->assertOk()->assertJsonPath('redirect', '/');
        $this->assertAuthenticated();
    }

    public function test_registration_still_rejects_missing_or_invalid_body_token(): void
    {
        $token = $this->token();
        $this->postJson('/register', [])->assertStatus(419);
        $this->postJson('/register', ['_token' => 'invalid'], ['X-CSRF-TOKEN' => $token])
            ->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_auth_pages_are_not_cacheable_and_session_cookie_is_host_only(): void
    {
        config(['session.domain' => null, 'session.secure' => true]);
        foreach (['/register', '/login', '/auth/session'] as $path) {
            $response = $this->get($path)->assertOk();
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $cookie = $response->getCookie(config('session.cookie'), false);
            $this->assertNotNull($cookie);
            $this->assertNull($cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
        }
    }

    public function test_bad_credentials_and_inactive_reseller_return_json_validation_errors(): void
    {
        $user = User::factory()->create(['role' => 'reseller', 'is_active' => false]);
        $token = $this->token();
        foreach (['incorrect', 'password'] as $password) {
            $this->postJson('/login', ['email' => $user->email, 'password' => $password], [
                'X-CSRF-TOKEN' => $token,
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->assertGuest();
        }
    }

    public function test_free_reseller_registration_logs_in_and_refreshes_token(): void
    {
        Setting::updateOrCreate(['key' => 'reseller_registration_free'], ['value' => '1']);
        $token = $this->token();
        $this->postJson('/register', [
            'name' => 'Reseller', 'email' => 'reseller@example.test', 'role' => 'reseller',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ], ['X-CSRF-TOKEN' => $token])->assertOk()->assertJsonPath('redirect', '/')->assertJsonPath('is_free', true);
        $this->assertAuthenticated();
        $this->assertNotSame($token, session()->token());
    }

    public function test_paid_reseller_registration_still_returns_payment_details_without_login(): void
    {
        Http::fake(['127.0.0.1:4061/*' => Http::response(['data' => [
            'orderId' => 'RES-TEST', 'total_nominal' => 20000, 'url_qris' => 'https://example.test/qr.png',
        ]])]);
        $token = $this->token();
        $this->postJson('/register', [
            'name' => 'Reseller', 'email' => 'paid@example.test', 'role' => 'reseller',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ], ['X-CSRF-TOKEN' => $token])->assertOk()
            ->assertJsonPath('status', 'reseller_payment_required')->assertJsonPath('order_id', 'RES-TEST');
        $this->assertGuest();
    }

    public function test_home_remains_available_when_stock_providers_time_out(): void
    {
        foreach (['premku_api_key', 'preflix_id_user', 'preflix_key_user'] as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => 'test-value']);
        }
        $calls = 0;
        Http::fake(function ($request, $options) use (&$calls) {
            $calls++;
            $this->assertLessThanOrEqual(3, $options['timeout']);
            $this->assertLessThanOrEqual(3, $options['connect_timeout']);
            throw new \Illuminate\Http\Client\ConnectionException('Provider timed out');
        });
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')->where('auth.user.id', $user->id));
        $this->assertSame(2, $calls);
    }
}

class EnforceAuthCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
