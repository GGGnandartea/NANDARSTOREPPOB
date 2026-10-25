<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public function getValueAttribute($value)
    {
        if (is_string($value) && stripos($value, 'nandarstore') !== false) {
            return str_ireplace(['NANDARSTORE', 'NandarStore', 'nandarstore'], ['PREMIUMKITA', 'PremiumKita', 'premiumkita'], $value);
        }
        if (($this->attributes['key'] ?? null) === 'store_name' && is_string($value) && stripos($value, 'nandar') !== false) {
            return 'PremiumKita';
        }
        return $value;
    }
}
