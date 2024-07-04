<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatModel;
use App\Models\ChatDetailModel;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FirebaseController;
use App\Models\FcmTokenModel;
use App\Models\User;
use DB;

class ChatController extends Controller
{
    protected $modelChat;
    protected $modelDetailChat;
    protected $firebaseNotification;
    protected $tokenModel;

    protected $modelUser;
    public function __construct(FirebaseController $firebaseController)
    {
        $this->modelChat = new ChatModel();
        $this->modelDetailChat = new ChatDetailModel();
        $this->firebaseNotification = $firebaseController;
        $this->tokenModel = new FcmTokenModel();
        $this->modelUser = new User();
    }

   public function getChat(Request $request)
    {
        $userId = (int) $request->input('userId', 0);
 
        $validator = Validator::make($request->all(), [
            'userId' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        
        $chat = $this->modelChat
            ->whereRaw('JSON_CONTAINS(chat.id_user, ?)', [json_encode($userId)])->orderBy('updated_at', 'desc')
            ->get();
        $chat->transform(function ($item) use ($userId) {
            $users = json_decode($item->id_user);
            $user = DB::table('users')
                ->whereIn('id', $users)
                ->where('id', '!=', $userId)
                ->select('id', 'name', 'email', 'url', 'role')
                ->get();
            $item->users = $user;
            return $item;
        });
        return response()->json(['status' => true, 'data' => $chat]);
    }
    public function getAllChat () 
    {
        $chat = $this->modelChat->get();

        return response()->json(['status' => true, 'data' => $chat]);
    }

    public function getDetail (Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'id_chat' => 'integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $chat = $this->modelDetailChat->where('id_chat', $request->id_chat)->orderBy('created_at', 'desc')->get();

        return response()->json(['status' => true, 'data' => $chat]);
    }
    public function saveChat (Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'min:0',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
       
        $chat = $this->modelChat;
        $inputUsers = is_array($request->id_user) ? $request->id_user : json_decode($request->id_user, true);
        
        if (!is_array($inputUsers)) {
            return response()->json(['status' => false, 'message' => 'Invalid id_user format']);
        }
        sort($inputUsers); 
    
        $inputUsersJson = json_encode($inputUsers);
    
        $existingChat = $chat->whereJsonContains('id_user', $inputUsers[0])
                        ->whereJsonContains('id_user', $inputUsers[1])
                        ->first();
        if ($existingChat) {
            return response()->json(['status' => true,  'found' => true, 'message' => 'User has found']);
        }
        $chatSave = $chat->create([
            'id_user' => $inputUsersJson,
        ]);

        return response()->json(['status' => true, 'found' => false, 'data' =>  $chatSave]);
    }
     public function saveDetail (Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'id_chat' => 'integer|min:0',
            'id_user' => 'integer|min:0',
            '_id' => 'integer|min:0',
            'message' => 'min:0'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $detailChat = $this->modelDetailChat;
        $user = $this->modelUser->where('id', $request->id_user)->first();
        $chat = $this->modelChat->where('id', $request->id_chat)->first();
        if ($chat) {
            $usersInChat = json_decode($chat->id_user, true);
            $otherUsers = array_filter($usersInChat, function ($id) use ($request) {
                return $id != $request->id_user;
            });

            foreach ($otherUsers as $userId) {
                $modelToken = $this->tokenModel->where('id_user', $userId)->get();
                
                foreach ($modelToken as $token) {
                    $messageData = [
                        'token' => $token->token,
                        'title' => 'Pesan Baru'. ' '.$user->name,
                        'body' => $request->message,
                        'data' => [
                            'message' => $request->message,
                            'param' => '1',
                            '_id' => 0,
                            'user' => 'ananda',
                            'id_chat' => 0
                        ]
                    ];

                    $wrappedRequest = new Request($messageData);
                    $this->firebaseNotification->sendMessage($wrappedRequest);
                }
            }
        }


       
        $detail = $detailChat->create([
            'id_chat' => $request->id_chat,
            'id_user' => $request->id_user,
            '_id' => $request->_id,
            'message' => $request->message
        ]);

        return response()->json(['status' => true, 'data' => $detail]);
    }
    public function deleteChat ($id)
    {
        $chat = $this->modelChat->find($id);
        $detail = $this->modelDetailChat->find($id);
        if (!$chat) {
            return response()->json(['message' => 'Chat not found']);
        }
         if (!$detail) {
            return response()->json(['message' => 'Chat detail not found']);
        }
        $chat->delete();
        $detail->delete();
         return response()->json(['status' => true, 'message' => 'Chat deleted successfully'], 201);
    }
}