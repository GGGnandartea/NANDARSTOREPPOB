<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LocalAccount extends Model {
    protected $fillable = ['product_id', 'email', 'password', 'is_sold'];
}