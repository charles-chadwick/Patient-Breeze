<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionPostController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientMedicationController;
use App\Http\Controllers\Portal\DocumentController as PortalDocumentController;
use App\Http\Controllers\Portal\MessageController;
use App\Http\Controllers\PortalQueueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    })->name('home');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('two-factor.login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('patients.appointments', AppointmentController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->scoped();
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/staff/search', [AppointmentController::class, 'staffSearch'])->name('appointments.staff.search');
    Route::resource('patients.documents', DocumentController::class)
        ->only(['store', 'destroy'])
        ->scoped();
    Route::get('/patients/{patient}/documents/{document}/download', [DocumentController::class, 'download'])
        ->scopeBindings()
        ->name('patients.documents.download');
    Route::get('/medications/search', [MedicationController::class, 'search'])->name('medications.search');
    Route::post('/patients/{patient}/medications', [PatientMedicationController::class, 'store'])
        ->name('patients.medications.store');
    Route::delete('/patients/{patient}/medications/{patient_medication}', [PatientMedicationController::class, 'destroy'])
        ->scopeBindings()
        ->name('patients.medications.destroy');
    Route::prefix('admin')->group(function () {
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });
    Route::resource('contacts', ContactController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('notes', NoteController::class)->only(['store', 'update', 'destroy']);
    Route::resource('discussions', DiscussionController::class)->only(['store']);
    Route::resource('discussions.posts', DiscussionPostController::class)->only(['store']);

    Route::get('/portal-queue', [PortalQueueController::class, 'index'])->name('portal-queue.index');
    Route::post('/portal-queue/{notification}/read', [PortalQueueController::class, 'markRead'])->name('portal-queue.read');

    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/confirm-password', [ConfirmPasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmPasswordController::class, 'store'])->name('password.confirm.store');

    Route::get('/settings', [TwoFactorAuthenticationController::class, 'show'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
    Route::post('/settings/two-factor-authentication/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/settings/two-factor-authentication/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::delete('/settings/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Portal\LoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Portal\LoginController::class, 'store'])->middleware('throttle:6,1');
    Route::post('/logout', [App\Http\Controllers\Portal\LoginController::class, 'destroy'])->name('logout');

    Route::get('/two-factor-challenge', [App\Http\Controllers\Portal\TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [App\Http\Controllers\Portal\TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('two-factor.login.store');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/confirm-password', [App\Http\Controllers\Portal\ConfirmPasswordController::class, 'show'])->name('password.confirm');
        Route::post('/confirm-password', [App\Http\Controllers\Portal\ConfirmPasswordController::class, 'store'])->name('password.confirm.store');

        Route::get('/settings', [App\Http\Controllers\Portal\TwoFactorAuthenticationController::class, 'show'])->name('settings.index');
        Route::post('/settings/two-factor-authentication', [App\Http\Controllers\Portal\TwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
        Route::post('/settings/two-factor-authentication/confirm', [App\Http\Controllers\Portal\TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
        Route::post('/settings/two-factor-authentication/recovery-codes', [App\Http\Controllers\Portal\TwoFactorAuthenticationController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
        Route::delete('/settings/two-factor-authentication', [App\Http\Controllers\Portal\TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');

        Route::get('/', App\Http\Controllers\Portal\DashboardController::class)->name('dashboard');
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/recipients/search', [MessageController::class, 'recipientSearch'])->name('messages.recipients.search');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/messages/{discussion}/replies', [MessageController::class, 'reply'])->name('messages.reply');

        Route::post('/documents', [PortalDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [PortalDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/{document}/download', [PortalDocumentController::class, 'download'])->name('documents.download');
    });
});
