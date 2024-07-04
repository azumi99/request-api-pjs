<?php

namespace App\Http\Controllers;

use App\Models\FcmTokenModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\RequestModel;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FirebaseController;
use App\Models\NotifikasiModel;
use DB;
class RequestController extends Controller
{
    protected $modelRequest;
    protected $firebaseNotification;
    protected $tokenModel;
    protected $notifikasiModel;
    public function __construct(FirebaseController $firebaseController)
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->modelRequest = new RequestModel();
        $this->firebaseNotification = $firebaseController;
        $this->tokenModel = new FcmTokenModel();
        $this->notifikasiModel = new NotifikasiModel();
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
             ->whereIn('status', ['progress', 'waiting'])
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
     public function getAllRequest(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10); 
        $pageNumber = (int) $request->input('page', 1);

        $validator = Validator::make($request->all(), [
            'per_page' => 'integer|min:1',
            'page' => 'integer|min:1',
            'userId' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

       $dataRequest = $this->modelRequest->whereIn('status', ['progress', 'waiting'])->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $pageNumber);
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
            ->whereIn('status', ['progress', 'waiting'])
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
    public function byID($id) 
    {
        $dataRequest = $this->modelRequest->orderBy('created_at', 'desc')->where('id', $id)->get();
        $dataRequest->transform(function($request) {
            $memberIds = json_decode($request->member);
            $members = DB::table('users')
                        ->whereIn('id', $memberIds)
                        ->select('id', 'name', 'email', 'url')
                        ->get();
            $request->member = $members;
            return $request;
        });
         
        return response()->json(['status' =>  true, 'data' => $dataRequest], 200);   
    }
    public function createRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_job' => 'required',
            'status' => 'required',
            'by_request' => 'required',
            'do_date' => 'required',
            'member' => 'required',
            'item_request' => 'required',
            'notes' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
       
        $notifikasiModel = $this->notifikasiModel;
        $members = $request->input('member');
        $memberJson = json_encode($members);
  
        foreach ($members as $value) {
            $modelToken = $this->tokenModel->where('id_user', $value)->get();
            foreach ($modelToken as $item) {
                // print_r($item->token);
                $messageData = [
                        'token' => $item->token,
                        'title' => 'Created '.$request->title_job. ' '.$request->status,
                        'body' => 'Status created '. $request->status,
                        'data' => [
                            
                            'message' => '',
                            'param' => '0',
                            '_id' => 0,
                            'user' => 'ananda',
                            'id_chat' => 0
                        ]
                    ];
                $wrappedRequest = new Request($messageData);
                 $notifikasiModel->create([
                    'title' => $wrappedRequest->title,
                    'body' => $wrappedRequest->body,
                    'id_user' => $item->id_user
                ]);
                $this->firebaseNotification->sendMessage($wrappedRequest);
               
                
            }
            
        }
        $newRequest = $this->modelRequest->create([
            'title_job' => $request->input('title_job'),
            'status' => $request->input('status'),
            'by_request' => $request->input('by_request'),
            'do_date' => $request->input('do_date'),
            'member' => $memberJson,
            'item_request' => $request->input('item_request'),
            'notes' => $request->input('notes'),
        ]);
        return response()->json(['status' => true, 'message' => 'Request created successfully', 'data' => $newRequest], 201);
    }
    public function updateRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title_job' => 'required',
            'status' => 'required',
            'by_request' => 'required',
            'do_date' => 'required',
            'member' => 'required',
            'item_request' => 'required',
            'notes' => 'min:0',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $requestModel = $this->modelRequest->find($id);
         $notifikasiModel = $this->notifikasiModel;

        if (!$requestModel) {
            return response()->json(['message' => 'Category not found'], 200);
        }
        $member = json_decode($requestModel->member);
        foreach ($member as $value) {
            $modelToken = $this->tokenModel->where('id_user', $value)->get();
            foreach ($modelToken as $item) {
                $messageData = [
                        'token' => $item->token,
                        'title' => 'Edited '.$requestModel->title_job. ' '.$request->status,
                        'body' => 'Status edited '. $request->status,
                        'data' => [
                            
                            'message' => '',
                            'param' => '0',
                            '_id' => 0,
                            'user' => 'ananda',
                            'id_chat' => 0
                        ]
                    ];
                $wrappedRequest = new Request($messageData);
                 $notifikasiModel->create([
                    'title' => $wrappedRequest->title,
                    'body' => $wrappedRequest->body,
                    'id_user' => $item->id_user
                ]);
                $this->firebaseNotification->sendMessage($wrappedRequest);
               
                
            }
            
        }
        $requestModel->update([
            'title_job' => $request->input('title_job'),
            'status' => $request->input('status'),
            'by_request' => $request->input('by_request'),
            'do_date' => $request->input('do_date'),
            'member' => $request->input('member'),
            'item_request' => $request->input('item_request'),
            'notes' => $request->input('notes'),
        ]);
        return response()->json(['status' => true, 'message' => 'Request updated successfully', 'data' => $requestModel], 200);
    }
    public function updateItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'item_request' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $requestModel = $this->modelRequest->find($id);

        if (!$requestModel) {
            return response()->json(['message' => 'Category not found'], 200);
        }
        $requestModel->update([
            'item_request' => $request->input('item_request'),
        ]);
        return response()->json(['status' => true, 'message' => 'Item available cheked', 'data' => $requestModel], 200);
    }
    public function updateStatus(Request $request, $id)
    {
       $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $requestModel = $this->modelRequest->find($id);
        $notifikasiModel = $this->notifikasiModel;

        if (!$requestModel) {
            return response()->json(['message' => 'Category not found'], 200);
        }
       
        $member = json_decode($requestModel->member);
        foreach ($member as $value) {
            $modelToken = $this->tokenModel->where('id_user', $value)->get();
            foreach ($modelToken as $item) {
                // print_r($item->token);
                $messageData = [
                        'token' => $item->token,
                        'title' => $requestModel->title_job. ' '.$request->status,
                        'body' => 'Status updated '. $request->status,
                        'data' => [
                            
                            'message' => '',
                            'param' => '0',
                            '_id' => 0,
                            'user' => 'ananda',
                            'id_chat' => 0
                        ]
                    ];
                $wrappedRequest = new Request($messageData);
                 $notifikasiModel->create([
                    'title' => $wrappedRequest->title,
                    'body' => $wrappedRequest->body,
                    'id_user' => $item->id_user
                ]);
                $this->firebaseNotification->sendMessage($wrappedRequest);
               
                
            }
            
        }
       
        $requestModel->update([
            'status' => $request->input('status'),
        ]);
       
        return response()->json(['status' => true, 'message' => 'Request status updated', 'data' => $requestModel], 200);
    }
    
    public function deleteRequest($id)
    {
        $request = $this->modelRequest->find($id);
        $notifikasiModel = $this->notifikasiModel;
        if (!$request) {
            return response()->json(['message' => 'Request not found']);
        }
        $member = json_decode($request->member);
        foreach ($member as $value) {
            $modelToken = $this->tokenModel->where('id_user', $value)->get();
            foreach ($modelToken as $item) {
                $messageData = [
                        'token' => $item->token,
                        'title' => 'Deleted '.$request->title_job. ' '.$request->status,
                        'body' => 'Status deleted '. $request->status,
                        'data' => [
                            
                            'message' => '',
                            'param' => '0',
                            '_id' => 0,
                            'user' => 'ananda',
                            'id_chat' => 0
                        ]
                    ];
                $wrappedRequest = new Request($messageData);
                 $notifikasiModel->create([
                    'title' => $wrappedRequest->title,
                    'body' => $wrappedRequest->body,
                    'id_user' => $item->id_user
                ]);
                $this->firebaseNotification->sendMessage($wrappedRequest);
               
                
            }
            
        }
        $request->delete();
        return response()->json(['status' => true, 'message' => 'Request deleted successfully'], 201);
    }
}