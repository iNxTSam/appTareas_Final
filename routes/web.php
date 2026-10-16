<?php

use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/registro', [RegisterController::class, 'create'])->name('register');
Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');
