<?php

use App\Enums\AppointmentRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
});

it('renders the user show page', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Show')
            ->has('user')
            ->has('user.roles')
            ->has('appointments')
            ->has('appointment_search')
        );
});

it('includes patient_id on appointments in the user show payload', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()
        ->withProvider($user, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id]);

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Show')
            ->has('appointments.data', 1)
            ->where('appointments.data.0.patient_id', $patient->id)
        );
});

it('loads patient media on appointments for the user show page', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()
        ->withProvider($user, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id]);

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Show')
            ->has('appointments.data.0.patient')
        );
});

it('shows no appointments when the user has none', function (): void {
    $user = User::factory()->withRole(UserRole::Nurse)->create();

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Show')
            ->has('appointments.data', 0)
        );
});

it('filters appointments by reason on the user show page', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()->withProvider($user)->create(['patient_id' => $patient->id, 'reason' => 'Annual checkup']);
    Appointment::factory()->withProvider($user)->create(['patient_id' => $patient->id, 'reason' => 'Follow-up visit']);

    $this->get(route('users.show', [$user, 'search' => 'checkup']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.reason', 'Annual checkup')
        );
});

it('paginates appointments on the user show page', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()->withProvider($user)->count(12)->create(['patient_id' => $patient->id]);

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('appointments.data', 10)
            ->where('appointments.total', 12)
        );
});

it('renders the users index page', function (): void {
    User::factory()->count(3)->create();

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users')
            ->has('search')
            ->has('sort_by')
            ->has('direction')
            ->has('filters')
            ->has('role_options')
        );
});

it('filters the users index by a single role', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $nurse = User::factory()->withRole(UserRole::Nurse)->create();

    $this->get(route('users.index', ['roles' => [UserRole::Doctor->value]]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($doctor->id)
                && ! collect($users)->pluck('id')->contains($nurse->id)
            )
            ->where('filters.roles', [UserRole::Doctor->value])
        );
});

it('filters the users index by multiple roles using OR', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $nurse = User::factory()->withRole(UserRole::Nurse)->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $this->get(route('users.index', ['roles' => [UserRole::Doctor->value, UserRole::Nurse->value]]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($doctor->id)
                && collect($users)->pluck('id')->contains($nurse->id)
                && ! collect($users)->pluck('id')->contains($staff->id)
            )
        );
});

it('combines the role filter with search', function (): void {
    $matching = User::factory()->withRole(UserRole::Doctor)->create(['last_name' => 'Zylinski']);
    $wrongRole = User::factory()->withRole(UserRole::Nurse)->create(['last_name' => 'Zylinski']);
    $wrongName = User::factory()->withRole(UserRole::Doctor)->create(['last_name' => 'Anderson']);

    $this->get(route('users.index', ['roles' => [UserRole::Doctor->value], 'search' => 'Zylinski']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($matching->id)
                && ! collect($users)->pluck('id')->contains($wrongRole->id)
                && ! collect($users)->pluck('id')->contains($wrongName->id)
            )
        );
});

it('ignores empty role filter values', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $nurse = User::factory()->withRole(UserRole::Nurse)->create();

    $this->get(route('users.index', ['roles' => ['']]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($doctor->id)
                && collect($users)->pluck('id')->contains($nurse->id)
            )
            ->where('filters.roles', [])
        );
});

it('includes super admins in the users index listing', function (): void {
    $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($doctor->id)
                && collect($users)->pluck('id')->contains($superAdmin->id)
            )
        );
});

it('renders the create user page', function (): void {
    $this->get(route('users.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Form')
            ->has('role_options')
            ->missing('user')
        );
});

it('creates a new user with the given password', function (): void {
    $this->post(route('users.store'), [
        'first_name' => 'Owen',
        'last_name' => 'Bennett',
        'email' => 'owen.bennett@example.com',
        'role' => UserRole::Doctor->value,
        'password' => 'secret-pass1',
        'password_confirmation' => 'secret-pass1',
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'owen.bennett@example.com')->first();

    expect($user)
        ->not->toBeNull()
        ->and($user->first_name)->toBe('Owen')
        ->and($user->hasRole(UserRole::Doctor->value))->toBeTrue()
        ->and(Hash::check('secret-pass1', $user->password))->toBeTrue();
});

it('requires a password on store', function (): void {
    $this->post(route('users.store'), [
        'first_name' => 'Owen',
        'last_name' => 'Bennett',
        'email' => 'owen.bennett@example.com',
        'role' => UserRole::Doctor->value,
    ])->assertSessionHasErrors(['password']);
});

it('validates required fields on store', function (): void {
    $this->post(route('users.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'role', 'password']);
});

it('rejects a duplicate email on store', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('users.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
        'role' => UserRole::Staff->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors(['email']);
});

it('renders the edit user page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Nurse->value);

    $this->get(route('users.edit', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Form')
            ->has('user')
            ->has('role_options')
        );
});

it('updates a user without changing the password when left blank', function (): void {
    $user = User::factory()->create(['password' => Hash::make('original-password')]);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Nurse->value,
    ])->assertRedirect(route('users.index'));

    $fresh = $user->fresh();
    expect(Hash::check('original-password', $fresh->password))->toBeTrue()
        ->and($fresh->hasRole(UserRole::Nurse->value))->toBeTrue();
});

it('updates the password when provided on edit', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Staff->value,
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertRedirect(route('users.index'));

    expect(Hash::check('new-password1', $user->fresh()->password))->toBeTrue();
});

it('allows the same email when updating the same user', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'same@example.com',
        'role' => UserRole::Staff->value,
    ])->assertRedirect(route('users.index'));
});

it('uploads an avatar when creating a user', function (): void {
    Storage::fake('public');

    $this->post(route('users.store'), [
        'first_name' => 'Owen',
        'last_name' => 'Bennett',
        'email' => 'owen.bennett@example.com',
        'role' => UserRole::Doctor->value,
        'password' => 'secret-pass1',
        'password_confirmation' => 'secret-pass1',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'owen.bennett@example.com')->first();
    expect($user->getFirstMedia('avatar'))->not->toBeNull();
});

it('uploads an avatar when updating a user', function (): void {
    Storage::fake('public');

    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Doctor->value,
        'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
    ])->assertRedirect(route('users.index'));

    expect($user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('removes an avatar when remove_avatar is true', function (): void {
    Storage::fake('public');

    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Doctor->value,
        'remove_avatar' => true,
    ])->assertRedirect(route('users.index'));

    expect($user->fresh()->getFirstMedia('avatar'))->toBeNull();
});

it('falls back to the local default avatar when no media is set', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    expect($user->avatar_url)->toBe(asset('storage/default-avatar.png'));
});

it('rejects an email belonging to another user on update', function (): void {
    User::factory()->create(['email' => 'other@example.com']);
    $user = User::factory()->create();
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'other@example.com',
        'role' => UserRole::Staff->value,
    ])->assertSessionHasErrors(['email']);
});
