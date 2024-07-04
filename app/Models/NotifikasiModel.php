<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifikasiModel extends Model
{
    use HasFactory;
    protected $table = 'notifikasi';
    protected $fillable = [
        'id',
        'title',
        'id_user',
        'body',
        'created_at'
    ];

}