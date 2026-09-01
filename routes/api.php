<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SpeakingController;
use Illuminate\Support\Facades\Route;

Route::get('/questions', [QuestionController::class, 'index']);
Route::post('/speaking/submit', [SpeakingController::class, 'submit']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/attempts', [AttemptController::class, 'index']);
});
