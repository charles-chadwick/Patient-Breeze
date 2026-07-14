<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('renders the audit log for a super admin', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
    Patient::factory()->create();

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AuditLog/Index')
            ->has('activities.data')
            ->has('causer_options')
            ->has('subject_options')
            ->has('event_options')
        );
});

it('renders the audit log for doctors and staff', function (UserRole $role): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('AuditLog/Index'));
})->with([
    'doctor' => [UserRole::Doctor],
    'staff' => [UserRole::Staff],
]);

it('forbids the audit log for roles without access', function (UserRole $role): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('audit-log.index'))->assertForbidden();
})->with([
    'nurse' => [UserRole::Nurse],
    'medical assistant' => [UserRole::MedicalAssistant],
]);

it('scopes the audit log to a single causer', function (): void {
    $actor = User::factory()->withRole(UserRole::SuperAdmin)->create(['first_name' => 'Aria', 'last_name' => 'Vance']);
    $other_actor = User::factory()->withRole(UserRole::SuperAdmin)->create(['first_name' => 'Bram', 'last_name' => 'Okafor']);

    $this->actingAs($other_actor);
    Patient::factory()->create(); // caused by the other actor

    $this->actingAs($actor);
    Patient::factory()->create(); // caused by the acting user

    $this->get(route('audit-log.index', ['causer_id' => $actor->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.causer_id', $actor->id)
            ->where('activities.data', fn ($rows) => collect($rows)->isNotEmpty()
                && collect($rows)->every(fn ($row) => $row['causer_name'] === 'Aria Vance'))
            ->where('activities.data', fn ($rows) => collect($rows)->doesntContain('causer_name', 'Bram Okafor'))
        );
});

it('filters the audit log by event', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();      // logs a "created" event
    $patient->update(['first_name' => 'Renamed']); // logs an "updated" event

    $this->get(route('audit-log.index', ['event' => 'updated']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['event'] === 'updated'))
        );
});

it('filters the audit log by subject type', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
    Patient::factory()->create();

    $this->get(route('audit-log.index', ['subject_type' => Patient::class]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['subject_type'] === Patient::class))
        );
});

it('scopes the audit log to a single patient and exposes their name', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();
    $other = Patient::factory()->create();

    $this->get(route('audit-log.index', ['patient_id' => $patient->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('patient.id', $patient->id)
            ->where('patient.name', trim("{$patient->first_name} {$patient->last_name}"))
            ->where('filters.patient_id', $patient->id)
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['subject_id'] === $patient->id))
            ->where('activities.data', fn ($rows) => collect($rows)->doesntContain('subject_id', $other->id))
        );
});

it('exports the audit log as a pdf for permitted roles', function (UserRole $role): void {
    Pdf::fake();
    $this->actingAs(User::factory()->withRole($role)->create());
    Patient::factory()->create();

    $this->get(route('audit-log.export'))->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.audit-log'
        && $pdf->downloadName === 'audit-log.pdf');
})->with([
    'super admin' => [UserRole::SuperAdmin],
    'doctor' => [UserRole::Doctor],
    'staff' => [UserRole::Staff],
]);

it('forbids exporting the audit log for roles without access', function (UserRole $role): void {
    Pdf::fake();
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('audit-log.export'))->assertForbidden();
})->with([
    'nurse' => [UserRole::Nurse],
    'medical assistant' => [UserRole::MedicalAssistant],
]);

it('scopes the pdf export to a patient and names the file accordingly', function (): void {
    Pdf::fake();
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();
    $other = Patient::factory()->create();

    $this->get(route('audit-log.export', ['patient_id' => $patient->id]))->assertOk();

    Pdf::assertRespondedWithPdf(function ($pdf) use ($patient, $other) {
        $activities = collect($pdf->viewData['activities']);

        return $pdf->viewName === 'pdf.audit-log'
            && $pdf->downloadName === "audit-log-patient-{$patient->id}.pdf"
            && $pdf->viewData['patient']['name'] === trim("{$patient->first_name} {$patient->last_name}")
            && $activities->every(fn ($row) => $row['subject_id'] === $patient->id)
            && $activities->doesntContain('subject_id', $other->id);
    });
});

it('applies the event filter to the pdf export', function (): void {
    Pdf::fake();
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();       // logs a "created" event
    $patient->update(['first_name' => 'Renamed']);  // logs an "updated" event

    $this->get(route('audit-log.export', ['event' => 'updated']))->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf) => collect($pdf->viewData['activities'])
        ->every(fn ($row) => $row['event'] === 'updated'));
});
