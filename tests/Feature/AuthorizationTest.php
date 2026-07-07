<?php

use App\Enums\ContactType;
use App\Enums\GenderAtBirth;
use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\Patient;
use App\Models\User;

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

it('forbids non-super-admins from reaching user management', function (): void {
    actingAsRole(UserRole::Doctor);

    test()->get(route('users.index'))->assertForbidden();
    test()->get(route('users.create'))->assertForbidden();
});

it('allows super admins to manage users', function (): void {
    actingAsRole(UserRole::SuperAdmin);

    test()->get(route('users.index'))->assertSuccessful();
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
