<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Authenticated routes (patient, user, etc.) will be registered here.
    // Prefer Route::resource / apiResource with implicit model binding.
});
