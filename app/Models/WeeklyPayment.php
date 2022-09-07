<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class WeeklyPayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount', 'date_from', 'date_to', 'status', 'merchant_name', 'merchant_id', 'admin_id', 'merchant_agreed_at', 'admin_agreed_at', 'proof_url'
    ];

    protected $table = 'weekly_payments';

    protected $hidden = [
        'pivot'
    ];
}
