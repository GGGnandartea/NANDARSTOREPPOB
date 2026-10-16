<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    protected $fillable = ['name', 'description', 'price', 'reseller_price', 'type', 'process_type', 'api_product_id', 'api_price', 'image', 'stock', 'status', 'category', 'is_promo'];
    
    public function accounts() {
        return $this->hasMany(LocalAccount::class);
    }
}