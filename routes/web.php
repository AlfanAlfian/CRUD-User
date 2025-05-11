<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('/user', UserController::class);

Route::get('/user-crud', function () {
    return view('user-crud');
});
