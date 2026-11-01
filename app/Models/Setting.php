<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public function getValueAttribute($value)
    {
        if (is_string($value) && stripos($value, 'nandar') !== false) {
            $replaced = str_replace(
                ['NANDARSTORE 2.0', 'NandarStore 2.0', 'NANDARSTORE', 'NandarStore', 'nandarstore', 'NANDAR STORE', 'Nandar Store'],
                ['PREMIUMKITA', 'PremiumKita', 'PREMIUMKITA', 'PremiumKita', 'premiumkita', 'PREMIUMKITA', 'PremiumKita'],
                $value
            );
            return str_ireplace(
                ['nandarstore 2.0', 'nandarstore', 'nandar store'],
                ['PremiumKita', 'PremiumKita', 'PremiumKita'],
                $replaced
            );
        }
        return $value;
    }
}
