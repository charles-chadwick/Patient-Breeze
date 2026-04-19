<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
Route::resource('patients.appointments', AppointmentController::class)
    ->only(['create', 'store', 'edit', 'update'])
    ->scoped();
Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

Route::middleware(['auth', 'verified'])->group(function () {
    // Authenticated routes (patient, user, etc.) will be registered here.
    // Prefer Route::resource / apiResource with implicit model binding.
});
