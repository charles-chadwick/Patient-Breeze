<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

it('backfills patient_id for existing activity rows', function (): void {
    $patient = Patient::factory()->create();

    // Simulate a legacy row with no patient_id.
    Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)
        ->update(['patient_id' => null]);

    $this->artisan('audit:backfill-patient-id')->assertSuccessful();

    expect(Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)->value('patient_id'))
        ->toBe($patient->id);
});
