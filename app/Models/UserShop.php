<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserShop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'address', 'contact', 'open_hour', 'close_hour', 'status', 'monday',
        'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
        'pm_cod', 'pm_gcash', 'is_active'
    ];
    protected $table = 'user_shops';
    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $hidden = [
        'pivot'
    ];
}
