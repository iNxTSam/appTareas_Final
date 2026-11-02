<?php

use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\PanelController;
use App\Http\Controllers\Web\PasswordRecoveryController;
use App\Http\Controllers\Web\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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
