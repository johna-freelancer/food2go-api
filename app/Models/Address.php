<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'line_1',
        'line_2',
        'city',
        'state',
        'zipcode',
        'country',
        'latitude',
        'longitude',
        'is_primary',
    ];

    /**
     * Get the owning addressable model.
     */
    public function addressable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user associated with the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'addressable_id');
    }

    /**
     * Get the shop associated with the address.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'addressable_id');
    }
}
