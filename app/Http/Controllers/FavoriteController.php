<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'available', 404);
        DB::table('product_favorites')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back();
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()->favoriteProducts()->detach($product->id);
        return back();
    }
}
