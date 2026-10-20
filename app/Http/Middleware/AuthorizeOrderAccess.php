<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Services\OrderAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeOrderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = Order::where('invoice_number', $request->route('orderId'))->first();
        // Same response for unknown invoices and invoices belonging to others.
        abort_unless($order && OrderAccessService::canView($request, $order), 404);
        $request->attributes->set('authorized_order', $order);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
