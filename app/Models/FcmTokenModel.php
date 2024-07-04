<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmTokenModel extends Model
{
    use HasFactory;
    protected $table = 'fcm_token';
    protected $fillable = [
        'id',
        'id_user',
        'token',
    ];

}