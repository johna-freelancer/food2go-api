<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class OrderList extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orders_id', 'product_id', 'product_name', 'product_price', 'quantity'
    ];

    protected $table = 'order_lists';

    protected $hidden = [
        'pivot'
    ];
}
