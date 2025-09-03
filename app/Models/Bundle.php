<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $fillable = ['shop_id', 'shopify_product_id', 'title'];

    public function discounts()
    {
        return $this->hasMany(BundleDiscount::class);
    }
}
