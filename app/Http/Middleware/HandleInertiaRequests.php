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
        $adminWa = \App\Models\Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
        $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
        if (str_starts_with($adminWaClean, '0')) {
            $adminWaClean = '62' . substr($adminWaClean, 1);
        }

        return [
            ...parent::share($request),
            'favoriteProductIds' => fn () => $request->user()?->favoriteProducts()->pluck('products.id')->all() ?? [],
            'store' => [
                'name'                => \App\Models\Setting::where('key', 'store_name')->value('value') ?: 'NandarStore',
                'logo'                => \App\Models\Setting::where('key', 'store_logo')->value('value') ?: null,
                'tagline'             => \App\Models\Setting::where('key', 'store_tagline')->value('value') ?: 'Platform Digital & Akun Premium Otomatis',
                'admin_whatsapp'      => $adminWaClean,
                'whatsapp_group_link' => \App\Models\Setting::where('key', 'whatsapp_group_link')->value('value') ?: 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H',
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
