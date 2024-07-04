<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\RequestModel;
use Illuminate\Support\Facades\Validator;
use DB;

class DashboardReportController extends Controller
{
     protected $modelRequest;
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->modelRequest = new RequestModel();
    }
    public function getCountStatus(Request $request)
    {
        $userId = (int) $request->input('userId', 0); 
        $status =  $request->input('status'); 
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|min:1',
            'status' => 'required|min:1',
         ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

       $dataRequest = $this->modelRequest->whereRaw('JSON_CONTAINS(member, ?)', [json_encode([$userId])])->orderBy('created_at', 'desc')->where('status', '=', $status )->get();
       $dataRequest->transform(function($request) {
            $memberIds = json_decode($request->member, true); 
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'url')
                        ->get();
            $request->member = $members;
            return $request;
        });
       return response()->json([
            'status' => true,
            'data' => count($dataRequest),
       ], 200);
    }
    public function getCountRequest(Request $request)
    {
        $userId = (int) $request->input('userId', 0); 
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|min:1',
         ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

       $dataRequest = $this->modelRequest->whereRaw('JSON_CONTAINS(member, ?)', [json_encode([$userId])])->orderBy('created_at', 'desc')->get();
       $dataRequest->transform(function($request) {
            $memberIds = json_decode($request->member, true); 
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'url')
                        ->get();
            $request->member = $members;
            return $request;
        });
       return response()->json([
            'status' => true,
            'data' => count($dataRequest),
       ], 200);
    }
     public function getHistoryCount(Request $request)
    {
       $userId = (int) $request->input('userId', 0); 
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|min:1',
            
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
       $dataRequest = $this->modelRequest
            ->whereRaw('JSON_CONTAINS(member, ?)', [json_encode([$userId])])
            ->whereIn('status', ['done', 'cancel'])
            ->orderBy('created_at', 'desc')->get();
        $dataRequest->transform(function($request) {
        $memberIds = json_decode($request->member, true); 
        $members = DB::table('users')
                    ->whereIn('id', $memberIds)
                    ->select('id', 'name', 'email', 'url')
                    ->get();
        $request->member = $members;
        return $request;
    });
       return response()->json([
            'status' => true,
            'data' => count($dataRequest),
       ], 200);
    }
}