<?php

use App\Models\Patient;
use PragmaRX\Google2FA\Google2FA;

function portalPatientWithTwoFactor(): Patient
{
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);
    $patient->enableTwoFactorAuthentication();
    $patient->forceFill(['two_factor_confirmed_at' => now()])->save();

    return $patient->refresh();
}

function portalOtp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/**
 * @return array<string, int>
 */
function portalConfirmedPasswordSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('renders the portal two-factor settings page', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.settings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Portal/Settings')
            ->where('two_factor_enabled', false));
});

it('enables portal two-factor authentication in a pending state', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->withSession(portalConfirmedPasswordSession())
        ->post(route('portal.two-factor.enable'))
        ->assertRedirect();

    $patient->refresh();
    expect($patient->two_factor_secret)->not->toBeNull();
    expect($patient->recoveryCodes())->toHaveCount(8);
    expect($patient->hasPendingTwoFactorAuthentication())->toBeTrue();
});

it('confirms portal two-factor authentication with a valid code', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);
    $patient->enableTwoFactorAuthentication();
    $patient->refresh();

    $this->actingAs($patient, 'portal')
        ->withSession(portalConfirmedPasswordSession())
        ->post(route('portal.two-factor.confirm'), ['code' => portalOtp($patient->two_factor_secret)])
        ->assertSessionHasNoErrors();

    expect($patient->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('disables portal two-factor authentication', function (): void {
    $patient = portalPatientWithTwoFactor();

    $this->actingAs($patient, 'portal')
        ->withSession(portalConfirmedPasswordSession())
        ->delete(route('portal.two-factor.disable'))
        ->assertRedirect();

    expect($patient->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('redirects to portal password confirmation when enabling without a confirmed password', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.two-factor.enable'))
        ->assertRedirect(route('portal.password.confirm'));

    expect($patient->refresh()->two_factor_secret)->toBeNull();
});

it('confirms the portal password with the correct password', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('portal.settings.index'))
        ->assertSessionHas('auth.password_confirmed_at');
});

it('rejects portal password confirmation with the wrong password', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.password.confirm.store'), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');
});

it('redirects to the portal challenge instead of logging in', function (): void {
    $patient = portalPatientWithTwoFactor();

    $this->post(route('portal.login'), [
        'email' => $patient->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.two-factor.login'));

    $this->assertGuest('portal');
});

it('completes portal login with a valid authentication code', function (): void {
    $patient = portalPatientWithTwoFactor();

    $this->post(route('portal.login'), ['email' => $patient->email, 'password' => 'password']);

    $this->post(route('portal.two-factor.login.store'), ['code' => portalOtp($patient->two_factor_secret)])
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($patient, 'portal');
});

it('rejects an invalid code at the portal challenge', function (): void {
    $patient = portalPatientWithTwoFactor();

    $this->post(route('portal.login'), ['email' => $patient->email, 'password' => 'password']);

    $this->post(route('portal.two-factor.login.store'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest('portal');
});

it('completes portal login with a recovery code and consumes it', function (): void {
    $patient = portalPatientWithTwoFactor();
    $recovery_code = $patient->recoveryCodes()[0];

    $this->post(route('portal.login'), ['email' => $patient->email, 'password' => 'password']);

    $this->post(route('portal.two-factor.login.store'), ['recovery_code' => $recovery_code])
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($patient, 'portal');
    expect($patient->refresh()->recoveryCodes())
        ->toHaveCount(7)
        ->not->toContain($recovery_code);
});

it('logs in a portal patient normally when two-factor is not enabled', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->post(route('portal.login'), ['email' => $patient->email, 'password' => 'password'])
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($patient, 'portal');
});
