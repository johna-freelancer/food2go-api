<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'description', 'image_url', 'price', 'tags', 'status'
    ];

    protected $table = 'products';

    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inventories(){
        return $this->hasOne(Inventory::class);
    }

    protected $hidden = [
        'pivot'
    ];
}
