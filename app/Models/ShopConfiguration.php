<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'mop_gcash',
        'mop_cod',
        'gcash_number',
        'minimum_delivery_charge',
        'delivery_amount_threshold',
        'delivery_items_threshold',
        'delivery_distance_threshold',
        'rate_per_km',
    ];

    /**
     * BelongsTo relationship.
     * Get the shop that owns the configuration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
