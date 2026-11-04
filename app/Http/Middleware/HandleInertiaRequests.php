<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $allSettings = \Illuminate\Support\Facades\Cache::remember('inertia_shared_settings', 15, function () {
            try {
                return \App\Models\Setting::pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        $adminWa = $allSettings['admin_whatsapp'] ?? '082317458496';
        $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
        if (str_starts_with($adminWaClean, '0')) {
            $adminWaClean = '62' . substr($adminWaClean, 1);
        }

        $ppobStatus = (new \App\Services\KiosbankService())->getCutoffStatus();

        return [
            ...parent::share($request),
            'favoriteProductIds' => fn () => $request->user()?->favoriteProducts()->pluck('products.id')->all() ?? [],
            'store' => [
                'name'                => ($allSettings['store_name'] ?? null) ?: 'PremiumKita',
                'logo'                => ($allSettings['store_logo'] ?? null) ?: null,
                'tagline'             => ($allSettings['store_tagline'] ?? null) ?: 'Platform Digital & Akun Premium Otomatis',
                'admin_whatsapp'      => $adminWaClean,
                'whatsapp_group_link' => ($allSettings['whatsapp_group_link'] ?? null) ?: 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H',
                'announcement'        => [
                    'enabled' => ($allSettings['announcement_enabled'] ?? '0') === '1',
                    'text'    => ($allSettings['announcement_text'] ?? null) ?: '',
                    'badge'   => ($allSettings['announcement_badge'] ?? null) ?: 'INFO',
                    'theme'   => ($allSettings['announcement_theme'] ?? null) ?: 'promo',
                    'speed'   => ($allSettings['announcement_speed'] ?? null) ?: 'normal',
                    'link'    => ($allSettings['announcement_link'] ?? null) ?: '',
                ],
                'ppob_status'         => $ppobStatus,
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id'       => $request->user()->id,
                    'name'     => $request->user()->name,
                    'email'    => $request->user()->email,
                    'role'     => $request->user()->role,
                    'balance'  => ($request->user()->role === 'admin') ? 0 : ($request->user()->balance ?? 0),
                    'is_active'=> $request->user()->is_active ?? true,
                    'created_at' => $request->user()->created_at,
                    'whatsapp' => $request->user()->whatsapp,
                    'is_whatsapp_verified' => (bool) ($request->user()->is_whatsapp_verified ?? false),
                ] : null,
            ],
            'flash' => [
                'success'      => fn () => $request->session()->get('success'),
                'error'        => fn () => $request->session()->get('error'),
                'step'         => fn () => $request->session()->get('step'),
                'whatsapp'     => fn () => $request->session()->get('whatsapp'),
                'user_name'    => fn () => $request->session()->get('user_name'),
                'trigger_code' => fn () => $request->session()->get('trigger_code'),
                'bot_number'   => fn () => $request->session()->get('bot_number'),
                'bot_wa_url'   => fn () => $request->session()->get('bot_wa_url'),
            ],
        ];
    }
}
