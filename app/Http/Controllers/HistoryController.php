<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\RequestModel;
use Illuminate\Support\Facades\Validator;
use DB;

class HistoryController extends Controller
{
     protected $modelRequest;
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->modelRequest = new RequestModel();
    }
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10); 
        $pageNumber = (int) $request->input('page', 1);
        $userId = (int) $request->input('userId', 0); 

        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1',
            'page' => 'integer|min:1',
            'userId' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

       $dataRequest = $this->modelRequest->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $pageNumber);
       $dataRequest = $this->modelRequest
            ->whereRaw('JSON_CONTAINS(member, ?)', [json_encode([$userId])])
             ->whereIn('status', ['done', 'cancel'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $pageNumber);
       $dataRequest->getCollection()->transform(function($request) {
            $memberIds = json_decode($request->member); 
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'url')
                        ->get();
            $request->member = $members;
            return $request;
        });
       return response()->json([
            'status' => true,
            'data' => $dataRequest->items(),
            'current_page' => $dataRequest->currentPage(),
            'total_pages' => $dataRequest->lastPage(),
            'next_page_url' => $dataRequest->nextPageUrl(),
            'previous_page_url' => $dataRequest->previousPageUrl(),
       ], 200);
    }
    public function getAll(Request $request)
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
            'data' => $dataRequest,
       ], 200);
    }
     public function getAllHistory(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10); 
        $pageNumber = (int) $request->input('page', 1);

        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1',
            'page' => 'integer|min:1',
            
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

       $dataRequest = $this->modelRequest->whereIn('status', ['done', 'cancel'])->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $pageNumber);
       $dataRequest->getCollection()->transform(function($request) {
            $memberIds = json_decode($request->member); 
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'url')
                        ->get();
            $request->member = $members;
            return $request;
        });
       return response()->json([
            'status' => true,
            'data' => $dataRequest->items(),
            'current_page' => $dataRequest->currentPage(),
            'total_pages' => $dataRequest->lastPage(),
            'next_page_url' => $dataRequest->nextPageUrl(),
            'previous_page_url' => $dataRequest->previousPageUrl(),
       ], 200);
    }
}