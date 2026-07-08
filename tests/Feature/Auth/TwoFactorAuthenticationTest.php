<?php

use App\Enums\UserRole;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

function staffWithTwoFactor(): User
{
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $user->enableTwoFactorAuthentication();
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return $user->refresh();
}

function currentOtp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/**
 * @return array<string, int>
 */
function confirmedPasswordSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('renders the two-factor settings page', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Settings/Index')
            ->where('two_factor_enabled', false));
});

it('enables two-factor authentication in a pending state', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->post(route('two-factor.enable'))
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->recoveryCodes())->toHaveCount(8);
    expect($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
    expect($user->hasPendingTwoFactorAuthentication())->toBeTrue();
});

it('confirms two-factor authentication with a valid code', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $user->enableTwoFactorAuthentication();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->post(route('two-factor.confirm'), ['code' => currentOtp($user->two_factor_secret)])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('rejects confirmation with an invalid code', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $user->enableTwoFactorAuthentication();

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->post(route('two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('regenerates recovery codes', function (): void {
    $user = staffWithTwoFactor();
    $original_codes = $user->recoveryCodes();

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->post(route('two-factor.recovery-codes'))
        ->assertRedirect();

    expect($user->refresh()->recoveryCodes())
        ->toHaveCount(8)
        ->not->toEqual($original_codes);
});

it('disables two-factor authentication', function (): void {
    $user = staffWithTwoFactor();

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->delete(route('two-factor.disable'))
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('redirects to the two-factor challenge instead of logging in', function (): void {
    $user = staffWithTwoFactor();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

it('completes login with a valid authentication code', function (): void {
    $user = staffWithTwoFactor();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['code' => currentOtp($user->two_factor_secret)])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid authentication code at the challenge', function (): void {
    $user = staffWithTwoFactor();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('completes login with a recovery code and consumes it', function (): void {
    $user = staffWithTwoFactor();
    $recovery_code = $user->recoveryCodes()[0];

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => $recovery_code])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->recoveryCodes())
        ->toHaveCount(7)
        ->not->toContain($recovery_code);
});

it('redirects to login when visiting the challenge without a pending login', function (): void {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

it('redirects to password confirmation when enabling without a confirmed password', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('confirms the password and records the timestamp', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('settings.index'))
        ->assertSessionHas('auth.password_confirmed_at');
});

it('rejects password confirmation with the wrong password', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    $this->assertFalse(session()->has('auth.password_confirmed_at'));
});

it('hides recovery codes until the password is confirmed', function (): void {
    $user = staffWithTwoFactor();

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('password_confirmed', false)
            ->where('recovery_codes', []));

    $this->actingAs($user)
        ->withSession(confirmedPasswordSession())
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('password_confirmed', true)
            ->has('recovery_codes', 8));
});

it('logs in normally when two-factor is not enabled', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});
