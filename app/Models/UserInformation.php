<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserInformation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'primary_contact', 'secondary_contact', 'complete_address'
    ];
    protected $table = 'user_informations';
    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $hidden = [
        'pivot'
    ];
}
