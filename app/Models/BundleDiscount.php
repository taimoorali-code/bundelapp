<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleDiscount extends Model
{
    protected $fillable = ['bundle_id', 'min_qty', 'discount_type', 'discount_value'];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }
}
