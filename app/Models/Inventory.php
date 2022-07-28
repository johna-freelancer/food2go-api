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
        'product_id', 'quantity'
    ];

    protected $table = 'products';

    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $hidden = [
        'pivot'
    ];
}
