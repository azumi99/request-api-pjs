<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotifikasiModel;
use Illuminate\Support\Facades\Validator;

class NotifikasiController extends Controller
{
  
    protected $modelNotif;
    public function __construct()
    {
       $this->modelNotif = new NotifikasiModel();
    }

    public function getNotif(Request $request) 
    {
         $validator = Validator::make($request->all(), [
            'id_user' => 'integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $notif = $this->modelNotif->where('id_user', $request->id_user)->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => true, 'data' => $notif], 200);
    }
    public function deleteNotif($id) 
    {
        $notif = $this->modelNotif->find($id);
        if (!$notif) {
            return response()->json(['message' => 'Notif not found']);
        }
        $notif->delete();

        return response()->json(['status' => true, 'message' => 'Notif deleted successfully'], 201);
    }
}