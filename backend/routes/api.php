<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('api.password.email');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('api.password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::get('/tasks', [TaskController::class, 'index'])->name('api.tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('api.tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->whereNumber('task')->name('api.tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->whereNumber('task')->name('api.tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('api.tasks.destroy');
});
