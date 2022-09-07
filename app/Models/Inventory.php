<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'quantity', 'user_id'
    ];

    protected $table = 'inventories';

    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected $hidden = [
        'pivot'
    ];
}
