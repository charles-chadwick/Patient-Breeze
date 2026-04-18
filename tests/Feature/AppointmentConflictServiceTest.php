<?php

use App\Enums\AppointmentRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentConflictService;

it('returns no conflicts when appointments are adjacent (end equals next start)', function () {
    $service = new AppointmentConflictService;
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(0);
});

it('returns conflicting user when times partially overlap', function () {
    $service = new AppointmentConflictService;
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($staff->id);
});

it('returns conflicting user when new appointment fully contains an existing one', function () {
    $service = new AppointmentConflictService;
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '10:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(1);
});

it('excludes the appointment being edited from conflict check', function () {
    $service = new AppointmentConflictService;
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '10:00', [$staff->id], $appointment->id);

    expect($conflicts)->toHaveCount(0);
});

it('only returns conflicting staff, not free staff', function () {
    $service = new AppointmentConflictService;
    $busyStaff = User::factory()->withRole(UserRole::Staff)->create();
    $freeStaff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($busyStaff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$busyStaff->id, $freeStaff->id]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($busyStaff->id);
});

it('returns no conflicts when there are no appointments on that date', function () {
    $service = new AppointmentConflictService;
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '10:00', [$staff->id]);

    expect($conflicts)->toHaveCount(0);
});
