<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatDetailModel extends Model
{
    use HasFactory;
    protected $table = 'detail_chat';
    protected $fillable = [
        'id',
        'id_chat',
        'id_user',
        '_id',
        'created_at',
        'message'
    ];
}