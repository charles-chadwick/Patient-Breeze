<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared two-factor enrollment endpoints for an opt-in settings page.
 *
 * Concrete controllers bind these to a specific guard and Inertia component,
 * so the staff and portal experiences reuse the same logic. Sensitive actions
 * (and viewing recovery codes) require a recent password confirmation.
 */
abstract class ManagesTwoFactorAuthentication extends Controller
{
    /**
     * The authentication guard these endpoints operate on.
     */
    abstract protected function guardName(): string;

    /**
     * The Inertia component rendered for the settings page.
     */
    abstract protected function settingsComponent(): string;

    /**
     * The route to the password confirmation form for this guard.
     */
    abstract protected function passwordConfirmationRoute(): string;

    /**
     * Render the two-factor settings page for the authenticated user.
     */
    public function show(Request $request): Response
    {
        $account = $this->account($request);
        $password_confirmed = $this->hasRecentlyConfirmedPassword($request);

        return Inertia::render($this->settingsComponent(), [
            'two_factor_enabled' => $account->hasEnabledTwoFactorAuthentication(),
            'two_factor_pending' => $account->hasPendingTwoFactorAuthentication(),
            'password_confirmed' => $password_confirmed,
            'qr_code_svg' => $account->hasPendingTwoFactorAuthentication()
                ? $account->twoFactorQrCodeSvg()
                : null,
            'recovery_codes' => ($account->two_factor_secret && $password_confirmed)
                ? $account->recoveryCodes()
                : [],
            ...$this->additionalSettingsProps($request),
        ]);
    }

    /**
     * Extra props merged into the settings page. Guard-specific controllers
     * override this to surface preferences their account type supports.
     *
     * @return array<string, mixed>
     */
    protected function additionalSettingsProps(Request $request): array
    {
        return [];
    }

    /**
     * Begin enrollment by generating a secret and recovery codes.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensurePasswordConfirmed($request)) {
            return $redirect;
        }

        $account = $this->account($request);

        if (! $account->hasEnabledTwoFactorAuthentication()) {
            $account->enableTwoFactorAuthentication();
        }

        return back();
    }

    /**
     * Confirm enrollment by verifying the first code from the authenticator app.
     */
    public function confirm(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensurePasswordConfirmed($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $this->account($request)->confirmTwoFactorAuthentication($validated['code'])) {
            return back()->withErrors(['code' => __('two_factor.invalid_code')]);
        }

        return back()->with('success', __('two_factor.enabled'));
    }

    /**
     * Generate a fresh set of recovery codes.
     */
    public function recoveryCodes(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensurePasswordConfirmed($request)) {
            return $redirect;
        }

        $this->account($request)->regenerateRecoveryCodes();

        return back();
    }

    /**
     * Disable two-factor authentication entirely.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensurePasswordConfirmed($request)) {
            return $redirect;
        }

        $this->account($request)->disableTwoFactorAuthentication();

        return back()->with('success', __('two_factor.disabled'));
    }

    /**
     * Resolve the currently authenticated account for the configured guard.
     */
    protected function account(Request $request): Authenticatable
    {
        return $request->user($this->guardName());
    }

    /**
     * Redirect to the password confirmation form when confirmation is stale.
     */
    protected function ensurePasswordConfirmed(Request $request): ?RedirectResponse
    {
        if ($this->hasRecentlyConfirmedPassword($request)) {
            return null;
        }

        return redirect()->route($this->passwordConfirmationRoute());
    }

    /**
     * Determine if the user confirmed their password within the timeout window.
     */
    protected function hasRecentlyConfirmedPassword(Request $request): bool
    {
        $confirmed_at = $request->session()->get('auth.password_confirmed_at', 0);

        return (time() - $confirmed_at) < config('auth.password_timeout', 10800);
    }
}
