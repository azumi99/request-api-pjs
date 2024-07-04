<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatModel extends Model
{
    use HasFactory;
     protected $table = 'chat';
    protected $fillable = [
        'id',
        'id_user',
        'created_at',
        'updated_at'
    ];
}