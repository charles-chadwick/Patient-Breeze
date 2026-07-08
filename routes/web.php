<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionPostController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\Portal\DocumentController as PortalDocumentController;
use App\Http\Controllers\Portal\MessageController;
use App\Http\Controllers\PortalQueueController;
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
    Route::resource('patients.documents', DocumentController::class)
        ->only(['store', 'destroy'])
        ->scoped();
    Route::get('/patients/{patient}/documents/{document}/download', [DocumentController::class, 'download'])
        ->scopeBindings()
        ->name('patients.documents.download');
    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('contacts', ContactController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('discussions', DiscussionController::class)->only(['store']);
    Route::resource('discussions.posts', DiscussionPostController::class)->only(['store']);

    Route::get('/portal-queue', [PortalQueueController::class, 'index'])->name('portal-queue.index');
    Route::post('/portal-queue/{notification}/read', [PortalQueueController::class, 'markRead'])->name('portal-queue.read');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Portal\LoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Portal\LoginController::class, 'store'])->middleware('throttle:6,1');
    Route::post('/logout', [App\Http\Controllers\Portal\LoginController::class, 'destroy'])->name('logout');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/', App\Http\Controllers\Portal\DashboardController::class)->name('dashboard');
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/messages/{discussion}/replies', [MessageController::class, 'reply'])->name('messages.reply');

        Route::post('/documents', [PortalDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [PortalDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/{document}/download', [PortalDocumentController::class, 'download'])->name('documents.download');
    });
});
