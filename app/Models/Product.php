<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    protected $fillable = ['name', 'description', 'price', 'reseller_price', 'type', 'process_type', 'api_product_id', 'api_price', 'image', 'stock', 'status', 'category', 'is_promo'];
    
    // Automatic local products can only sell account rows that are still unsold.
    // The legacy numeric stock column is not proof that deliverable stock exists.
    public function getStockAttribute($value): int
    {
        if ($this->type !== 'local' || $this->process_type === 'manual') {
            return max(0, (int) $value);
        }

        return $this->availableAccountStock();
    }

    public function availableAccountStock(): int
    {
        if (array_key_exists('available_accounts_count', $this->attributes)) {
            return (int) $this->attributes['available_accounts_count'];
        }
        if ($this->relationLoaded('accounts')) {
            return $this->accounts->where('is_sold', false)->count();
        }
        return $this->exists ? $this->accounts()->where('is_sold', false)->count() : 0;
    }

    public function scopeWithAvailableAccountStock($query)
    {
        return $query->withCount(['accounts as available_accounts_count' => fn ($accounts) => $accounts->where('is_sold', false)]);
    }

    public function accounts() {
        return $this->hasMany(LocalAccount::class);
    }
}