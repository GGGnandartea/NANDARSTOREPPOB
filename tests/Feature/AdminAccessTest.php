<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Branding queries are unrelated to authorization and require a database.
        $this->withoutMiddleware(HandleInertiaRequests::class);
    }

    public function test_guests_are_redirected_to_login_for_admin_reads_and_writes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->post('/admin/settings', [])->assertRedirect('/login');
    }

    public function test_every_admin_route_rejects_non_admin_users(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(
            fn ($route) => str_starts_with($route->uri(), 'admin/')
                || str_starts_with($route->getActionName(), AdminController::class.'@')
        );

        $this->assertNotEmpty($routes);

        foreach (['user', 'reseller', null] as $role) {
            $this->actingAs(new User(['id' => 1, 'role' => $role, 'is_active' => true]));

            foreach ($routes as $route) {
                $this->assertContains(EnsureUserIsAdmin::class, $route->gatherMiddleware());
                $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

                $this->call($route->methods()[0], $uri)->assertForbidden();
            }
        }
    }

    public function test_admin_can_reach_read_and_write_controller_actions(): void
    {
        $this->actingAs(new User(['id' => 1, 'role' => 'admin']));

        $this->partialMock(AdminController::class, function ($mock) {
            $mock->shouldReceive('dashboard')->once()->andReturn(response('Admin dashboard'));
            $mock->shouldReceive('saveSettings')->once()->andReturn(response('Settings saved'));
        });

        $this->get('/admin/dashboard')->assertOk()->assertSee('Admin dashboard');
        $this->post('/admin/settings', [])->assertOk()->assertSee('Settings saved');
    }
}
