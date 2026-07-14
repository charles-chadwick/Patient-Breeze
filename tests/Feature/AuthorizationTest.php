<?php

use App\Enums\ContactType;
use App\Enums\GenderAtBirth;
use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function actingAsRole(UserRole $role): User
{
    $user = User::factory()->withRole($role)->create();
    test()->actingAs($user);

    return $user;
}

it('forbids a role-less user from viewing the patient list', function (): void {
    test()->actingAs(User::factory()->create());

    test()->get(route('patients.index'))->assertForbidden();
});

it('lets front-desk staff view patients but not manage clinical records', function (): void {
    actingAsRole(UserRole::Staff);

    test()->get(route('patients.index'))->assertSuccessful();
    test()->get(route('patients.create'))->assertForbidden();
});

it('forbids staff from creating a patient even with a valid payload', function (): void {
    actingAsRole(UserRole::Staff);

    test()->post(route('patients.store'), [
        'first_name' => 'Grace',
        'last_name' => 'Holloway',
        'email' => 'grace.holloway@example.com',
        'date_of_birth' => '1956-05-12',
        'gender_at_birth' => GenderAtBirth::Female->value,
    ])->assertForbidden();

    expect(Patient::where('email', 'grace.holloway@example.com')->exists())->toBeFalse();
});

it('forbids nurses from creating patients', function (): void {
    actingAsRole(UserRole::Nurse);

    test()->get(route('patients.create'))->assertForbidden();
});

it('forbids clinical and front-desk roles from reaching user management', function (UserRole $role): void {
    actingAsRole($role);

    test()->get(route('users.index'))->assertForbidden();
    test()->get(route('users.create'))->assertForbidden();
})->with([UserRole::Nurse, UserRole::MedicalAssistant, UserRole::Staff]);

it('allows super admins to manage users', function (): void {
    actingAsRole(UserRole::SuperAdmin);

    test()->get(route('users.index'))->assertSuccessful();
});

it('lets doctors manage users like a super admin', function (): void {
    actingAsRole(UserRole::Doctor);

    test()->get(route('users.index'))->assertSuccessful();
    test()->get(route('users.create'))->assertSuccessful();
});

it('lets a doctor edit and delete a non-super-admin user', function (): void {
    $doctor = actingAsRole(UserRole::Doctor);
    $target = User::factory()->withRole(UserRole::Staff)->create();

    expect($doctor->can('update', $target))->toBeTrue()
        ->and($doctor->can('delete', $target))->toBeTrue();
});

it('forbids a doctor from editing or deleting a super admin', function (): void {
    $doctor = actingAsRole(UserRole::Doctor);
    $super_admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

    expect($doctor->can('update', $super_admin))->toBeFalse()
        ->and($doctor->can('delete', $super_admin))->toBeFalse();
});

it('lets a super admin edit and delete another super admin', function (): void {
    $super_admin = actingAsRole(UserRole::SuperAdmin);
    $other_super_admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

    expect($super_admin->can('update', $other_super_admin))->toBeTrue()
        ->and($super_admin->can('delete', $other_super_admin))->toBeTrue();
});

it('lets front-desk staff schedule appointments and manage contacts', function (): void {
    actingAsRole(UserRole::Staff);

    $patient = Patient::factory()->create();

    test()->get(route('appointments.index'))->assertSuccessful();

    test()->post(route('contacts.store'), [
        'name' => 'Jane Doe',
        'type' => ContactType::Emergency->value,
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'contactable_type' => Patient::class,
        'contactable_id' => $patient->id,
    ])->assertRedirect();

    expect($patient->contacts()->count())->toBe(1);
});

it('forbids front-desk staff from deleting a contact', function (): void {
    actingAsRole(UserRole::Staff);

    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();

    test()->delete(route('contacts.destroy', $contact))->assertForbidden();

    expect(Contact::whereKey($contact->id)->exists())->toBeTrue();
});

it('returns a 403 as a non-inertia response so the client can intercept it', function (): void {
    $patient = Patient::factory()->create();

    $user = User::factory()->withRole(UserRole::Staff)->create();
    Role::findByName(UserRole::Staff->value)->revokePermissionTo('create_appointments');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    $response = test()->get(route('patients.appointments.create', $patient));

    $response->assertForbidden();
    $response->assertHeaderMissing('X-Inertia');
});
