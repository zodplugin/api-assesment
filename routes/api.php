<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('/users', [UserController::class, 'store'])->middleware('checkRole:admin');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('checkRole:admin');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('checkRole:admin');
    Route::get('/users', [UserController::class, 'index'])->middleware('checkRole:admin');
    Route::get('/users/{id}', [UserController::class, 'show']);
});


