<?php

use App\Events\MessageCreated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});