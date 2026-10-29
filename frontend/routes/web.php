<?php

use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\PanelController;
use App\Http\Controllers\Web\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::get('/registro', [RegisterController::class, 'create'])->name('register');
Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('backend.auth')->name('logout');

Route::get('/panel', [PanelController::class, 'index'])
    ->middleware(['backend.auth', 'no-cache'])
    ->name('panel');
