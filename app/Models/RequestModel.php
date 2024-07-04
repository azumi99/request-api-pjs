<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use DB;

class RequestModel extends Model
{
    use HasFactory;
    protected $table = 'request_list';
    protected $fillable = [
        'id',
        'title_job',
        'status',
        'by_request',
        'do_date',
        'member',
        'item_request',
        'notes',
    ];

    public function joinRequestUser()
    {
      $requests = RequestModel::all();

        $requests->transform(function($request) {
            $memberIds = json_decode($request->member);
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'role')
                        ->get();
            $request->member = $members;
            return $request;
        });

        return $requests;
    }
    
}