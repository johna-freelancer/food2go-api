<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;


class Shop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'main_photo',
        'cover_photo',
        'address_id',
        'contact_number',
        'status',
        'temp_closed',
        'tags',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'temp_closed' => 'boolean',
    ];

    /**
     * BelongsTo relationship.
     * Get the user that owns the shop.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * BelongsTo relationship.
     * Get the address associated with the shop.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function address(): BelongsTo {
        return $this->belongsTo(Address::class);
    }

    /**
     * Polymorphic relationship.
     * Get all addresses for the shop.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function addresses(): MorphOne {
        return $this->morphOne(Address::class, 'addressable');
    }

    /**
     * One-to-One relationship.
     * Get the shop's configuration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function configuration(): HasOne {
        return $this->hasOne(ShopConfiguration::class);
    }

    /**
     * One-to-Many relationship.
     * Get the operating hours for the shop.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function operatingHours()
    {
        return $this->hasMany(ShopOperatingHour::class);
    }

}
