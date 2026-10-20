<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderAccessService
{
    public static function grantGuestSession(Request $request, Order $order): void
    {
        if (!$order->user_id && $order->guest_access_token) {
            $request->session()->put('guest_orders.'.$order->id, hash('sha256', $order->guest_access_token));
        }
    }

    public static function canView(Request $request, Order $order): bool
    {
        $user = $request->user();
        if ($user && ($user->role === 'admin' || ($order->user_id && (string) $order->user_id === (string) $user->id))) {
            return true;
        }
        if ($order->user_id || !$order->guest_access_token) {
            return false;
        }

        if ($request->query->has('token')) {
            $token = $request->query('token');
            if (!is_string($token) || !hash_equals($order->guest_access_token, $token)) {
                return false;
            }
            self::grantGuestSession($request, $order);

            return true;
        }

        $grant = $request->session()->get('guest_orders.'.$order->id);

        return is_string($grant) && hash_equals(hash('sha256', $order->guest_access_token), $grant);
    }
}
