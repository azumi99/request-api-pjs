<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ChangePasswordRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
class UserController extends Controller
{
    protected $modelUser;
    public function __construct() 
    {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->modelUser = new User();
    }

    public function index() 
    {
        $users = $this->modelUser->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => true,'data'=> $users], 200);
    }
     public function byId($id) 
    {
        $users = $this->modelUser->find($id);
        return response()->json(['status' => true,'data'=> $users], 200);
    }
    public function changePassword(ChangePasswordRequest $request)
    {
       $user = auth()->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 400);
        }
  
        $user->password = Hash::make($request->new_password);
        $user->save();
        JWTAuth::invalidate(JWTAuth::getToken());
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => true,
            'token' => $token
        ], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'min:0',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => "min:0"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); 
        }
        $emailExists = User::where('email', $request->input('email'))
                        ->where('id', '!=', $id)
                        ->exists();
        if ($emailExists) {
            return response()->json(['message' => 'Email already exists'], 422);
        }

        $userModel = $this->modelUser->find($id);

        if (!$userModel) {
            return response()->json(['message' => 'User not found'], 404); 
        }
        $oldUrl = $userModel->url;
        
        $image_base64 = $request->input('url');
        if ($oldUrl == $image_base64 ) {
            $userModel->update([
            'url' =>  $oldUrl,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'role' => $request->input('role'),
        ]);
        } else if ($image_base64 != null) {
            $image_parts = explode(";base64,", $image_base64);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = uniqid() . '.' . $image_type;
            $filePath = 'images/' . $fileName;
            Storage::disk('public')->put($filePath, $image_base64);
            $userModel->update([
                'url' =>  Storage::url($filePath),
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'role' => $request->input('role'),
            ]);
            if ($userModel->wasChanged('url')) {
                $oldFilePath = str_replace(Storage::url(''), '', $oldUrl);
                Storage::disk('public')->delete($oldFilePath);
            }
        }
       
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $userModel,
        ], 200);
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $this->modelUser->where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['status' => true, 'message' => 'Password successfully reset']);
    }
     public function deleteAccount($id)
    {
        $account = $this->modelUser->find($id);
        if (!$account) {
            return response()->json(['message' => 'Request not found']);
        }
        $account->delete();

        return response()->json(['status' => true, 'message' => 'Account successfully deleted']);
    }
        
}