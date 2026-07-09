<?php

use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserSetting;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

it('stores preferences for the authenticated user', function (): void {
    $this->actingAs($this->staff)
        ->put(route('settings.update'), [
            'settings' => [
                SettingKey::Theme->value => 'Dark',
                SettingKey::ItemsPerPage->value => '25',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->staff->fresh()->getSetting(SettingKey::Theme))->toBe('Dark')
        ->and($this->staff->fresh()->getSetting(SettingKey::ItemsPerPage))->toBe('25');
});

it('updates an existing preference instead of duplicating it', function (): void {
    $this->staff->setSetting(SettingKey::Theme, 'Light');

    $this->actingAs($this->staff)
        ->put(route('settings.update'), [
            'settings' => [SettingKey::Theme->value => 'Dark'],
        ])
        ->assertSessionHas('success');

    expect(UserSetting::where('user_id', $this->staff->id)->where('key', SettingKey::Theme->value)->count())->toBe(1)
        ->and($this->staff->fresh()->getSetting(SettingKey::Theme))->toBe('Dark');
});

it('falls back to the default when a preference is unset', function (): void {
    expect($this->staff->getSetting(SettingKey::Theme))->toBe(SettingKey::Theme->default());
});

it('rejects a value outside the allowed options for a setting', function (): void {
    $this->actingAs($this->staff)
        ->put(route('settings.update'), [
            'settings' => [SettingKey::ItemsPerPage->value => '999'],
        ])
        ->assertSessionHasErrors('settings.'.SettingKey::ItemsPerPage->value);

    expect(UserSetting::where('user_id', $this->staff->id)->count())->toBe(0);
});

it('paginates listings by the user\'s items-per-page preference', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $doctor->setSetting(SettingKey::ItemsPerPage, '25');

    Patient::factory()->count(30)->create();

    $this->actingAs($doctor)
        ->get(route('patients.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('patients.data', 25)
            ->where('patients.per_page', 25)
        );
});

it('shares the user theme preference on every page', function (): void {
    $this->staff->setSetting(SettingKey::Theme, 'Dark');

    $this->actingAs($this->staff)
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page->where('theme', 'Dark'));
});

it('defaults the shared theme to System when unset', function (): void {
    $this->actingAs($this->staff)
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page->where('theme', 'System'));
});

it('exposes resolved preferences on the settings page', function (): void {
    $this->staff->setSetting(SettingKey::Theme, 'Dark');

    $this->actingAs($this->staff)
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('preferences.'.SettingKey::Theme->value, 'Dark')
            ->where('preferences.'.SettingKey::ItemsPerPage->value, SettingKey::ItemsPerPage->default())
            ->has('preference_options.'.SettingKey::Theme->value, 3)
        );
});
