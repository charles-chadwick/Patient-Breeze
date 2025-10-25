<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::match([
    'get',
    'post',
], '/login', [
    AuthController::class,
    'login',
])
    ->name('login');
Route::post('/logout', [
    AuthController::class,
    'logout',
])
    ->name('logout');

Route::middleware('auth')
    ->get('/', function () {
        return Inertia::render('Dashboard');
    })
    ->name('dashboard');

Route::middleware('auth')
    ->prefix('users')
    ->group(function () {
        Route::post('/{user}/avatar/remove', [
            UserController::class,
            'removeAvatar',
        ])
            ->name('users.avatar.remove');

        Route::post('/{user}/avatar/upload', [
            UserController::class,
            'uploadAvatar',
        ])
            ->name('users.avatar.upload');
        Route::get('/', [
            UserController::class,
            'index',
        ])
            ->name('users.index');
        Route::get('/{user}/profile', [
            UserController::class,
            'profile',
        ])
            ->name('users.profile');
        Route::get('/create', [
            UserController::class,
            'create',
        ])
            ->name('users.create');
        Route::post('/store', [
            UserController::class,
            'store',
        ])
            ->name('users.store');
        Route::get('/edit/{user}', [
            UserController::class,
            'edit',
        ])
            ->name('users.edit');
        Route::post('/update/{user}', [
            UserController::class,
            'update',
        ])
            ->name('users.update');
        Route::get('/delete/{user}', [
            UserController::class,
            'destroy',
        ])
            ->name('users.delete');
    });

Route::middleware('auth')
    ->prefix('patients')
    ->group(function () {
        Route::post('/{patient}/avatar/remove', [
            PatientController::class,
            'removeAvatar',
        ])
            ->name('patients.avatar.remove');

        Route::post('/{patient}/avatar/upload', [
            PatientController::class,
            'uploadAvatar',
        ])
            ->name('patients.avatar.upload');
        Route::get('/', [
            PatientController::class,
            'index',
        ])
            ->name('patients.index');
        Route::get('/{patient}/profile', [
            PatientController::class,
            'profile',
        ])
            ->name('patients.profile');
        Route::get('/create', [
            PatientController::class,
            'create',
        ])
            ->name('patients.create');
        Route::post('/store', [
            PatientController::class,
            'store',
        ])
            ->name('patients.store');
        Route::get('/edit/{patient}', [
            PatientController::class,
            'edit',
        ])
            ->name('patients.edit');
        Route::post('/update/{patient}', [
            PatientController::class,
            'update',
        ])
            ->name('patients.update');
        Route::get('/delete/{patient}', [
            PatientController::class,
            'destroy',
        ])
            ->name('patients.delete');
    });
