<?php

use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\PanelController;
use App\Http\Controllers\Web\PasswordRecoveryController;
use App\Http\Controllers\Web\RegisterController;
use App\Http\Controllers\Web\TaskController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::get('/recuperar-cuenta', [PasswordRecoveryController::class, 'create'])->name('password.recovery');
Route::post('/recuperar-cuenta', [PasswordRecoveryController::class, 'store'])->name('password.recovery.store');

Route::get('/reset-password', [PasswordRecoveryController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordRecoveryController::class, 'update'])->name('password.reset.update');

Route::get('/registro', [RegisterController::class, 'create'])->name('register');
Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('backend.auth')->name('logout');

Route::get('/panel', [PanelController::class, 'index'])
    ->middleware(['backend.auth', 'no-cache'])
    ->name('panel');

Route::middleware(['backend.auth', 'no-cache'])
    ->prefix('/tareas')
    ->name('tasks.')
    ->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
    });
