<?php

namespace App\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }
    /**
    * @unauthenticated
    */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Check email and password have missing'], 200);
        }
        return $this->createNewToken($token);
    }
   
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'role' => 'required|string|between:2,100',
            'password' => 'required|string|confirmed|min:6',
        
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 201);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
        return response()->json([
            'status' => true,
            'message' => 'User account created',
            'user' => $user
        ], 201);
    }
    public function logout()
    {
        auth()->logout();
        return response()->json(['status' => true, 'message' => 'User successfully signed out']);
    }
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile(Request $request)
    {
        return response()->json(['status' => true, 'data' => auth()->user()]);
    }
    public function checkLogin()
    {
        return response()->json(['status' => true], 200);
    }
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 31536000,
            'user' => auth()->user()
        ]);
    }
}