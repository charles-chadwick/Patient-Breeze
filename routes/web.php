<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionPostController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    })->name('home');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('patients.appointments', AppointmentController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->scoped();
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('contacts', ContactController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('discussions', DiscussionController::class)->only(['store']);
    Route::resource('discussions.posts', DiscussionPostController::class)->only(['store']);
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Portal\LoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Portal\LoginController::class, 'store']);
    Route::post('/logout', [App\Http\Controllers\Portal\LoginController::class, 'destroy'])->name('logout');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/', App\Http\Controllers\Portal\DashboardController::class)->name('dashboard');
    });
});
