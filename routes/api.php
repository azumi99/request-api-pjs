<?php

use App\Http\Controllers\DashboardReportController;
use App\Http\Controllers\HistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ChatController;





Route::group(
    [
        'middleware' => 'api',
        'prefix' => 'auth'
    ],
    function ($router) {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/check', [AuthController::class, 'checkLogin']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);
    } );


Route::group(
    [
        'middleware' => 'api',
 
    ],
    function ($router) {
      
        Route::get('/request', [RequestController::class, 'index']);
        Route::get('/requestall', [RequestController::class, 'getAll']);
        Route::get('/allRequest', [RequestController::class, 'getAllRequest']);
        Route::get('/request/{id}', [RequestController::class, 'byID']);
        Route::post('/request', [RequestController::class, 'createRequest']);
        Route::post('/request/{id}', [RequestController::class, 'updateRequest']);
        Route::post('/requestitem/{id}', [RequestController::class, 'updateItem']);
        Route::post('/requeststatus/{id}', [RequestController::class, 'updateStatus']);
        Route::delete('/request/{id}', [RequestController::class, 'deleteRequest']);

        Route::get('/history', [HistoryController::class, 'index']);
        Route::get('/historyall', [HistoryController::class, 'getAll']);
        Route::get('/allHistory', [HistoryController::class, 'getAllHistory']);
       
        Route::get('/user', [UserController::class, 'index']);
        Route::get('/user/{id}', [UserController::class, 'byId']);
        Route::post('/user/{id}', [UserController::class, 'updateUser']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/passwordReset', [UserController::class, 'resetPassword']);
        Route::delete('/user/{id}', [UserController::class, 'deleteAccount']);
        
        Route::post('/dashboard', [DashboardReportController::class, 'getCountStatus']);
        Route::post('/dashboardRequestCount', [DashboardReportController::class, 'getCountRequest']);
        Route::post('/dashboardHistoryCount', [DashboardReportController::class, 'getHistoryCount']);

        Route::post('/send-message', [FirebaseController::class, 'sendMessage']);
        Route::post('/saveFcmToken', [FirebaseController::class, 'SaveToken']);

        Route::get('/notif', [NotifikasiController::class, 'getNotif']);
        Route::delete('/notif/{id}', [NotifikasiController::class, 'deleteNotif']);

        Route::get('/chat', [ChatController::class, 'getChat']);
        Route::get('/detailChat', [ChatController::class, 'getDetail']);
        Route::post('/chat', [ChatController::class, 'saveChat']);
        Route::post('/detailChat', [ChatController::class, 'saveDetail']);
        Route::delete('/chat/{id}', [ChatController::class, 'deleteChat']);
    } );
// Route::middleware('auth:sanctum')->get('/user-profiles', [AuthController::class, 'userProfile']);