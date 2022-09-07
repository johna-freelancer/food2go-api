<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_user_id', 'merchant_user_id', 'user_shops_id', 'mode_of_payment',
        'address', 'contact', 'remarks', 'delivery_charge', 'convenience_fee' , 'proof_url',
        'note', 'total', 'status', 'changed_at_preparing', 'changed_at_delivered', 'changed_at_completed', 'collected_at_completed'
    ];

    protected $table = 'orders';

    public function users(){
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    protected $hidden = [
        'pivot'
    ];
}
