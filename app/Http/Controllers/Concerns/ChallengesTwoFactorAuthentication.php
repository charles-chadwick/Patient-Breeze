<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared two-factor login challenge for a guard.
 *
 * The originating login controller stashes the pending account id in the
 * session; this controller verifies a TOTP code or recovery code before
 * completing the login.
 */
abstract class ChallengesTwoFactorAuthentication extends Controller
{
    /**
     * The authentication guard being challenged.
     */
    abstract protected function guardName(): string;

    /**
     * The Inertia component rendered for the challenge form.
     */
    abstract protected function challengeComponent(): string;

    /**
     * The route name to fall back to when no challenge is pending.
     */
    abstract protected function loginRoute(): string;

    /**
     * The route name to redirect to once the login completes.
     */
    abstract protected function intendedRoute(): string;

    /**
     * Show the challenge form, or bounce back to login if none is pending.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has($this->pendingIdKey())) {
            return redirect()->route($this->loginRoute());
        }

        return Inertia::render($this->challengeComponent());
    }

    /**
     * Verify the submitted code and complete the login.
     */
    public function store(Request $request): RedirectResponse
    {
        $account = $this->challengedAccount($request);

        if (is_null($account)) {
            return redirect()->route($this->loginRoute());
        }

        $code = $request->input('code');
        $recovery_code = $request->input('recovery_code');

        if (filled($code)) {
            if (! app(TwoFactorAuthenticationProvider::class)->verify($account->two_factor_secret, $code)) {
                return back()->withErrors(['code' => __('two_factor.invalid_code')]);
            }
        } elseif (filled($recovery_code)) {
            $matched_code = collect($account->recoveryCodes())
                ->first(fn (string $stored_code) => hash_equals($stored_code, $recovery_code));

            if (is_null($matched_code)) {
                return back()->withErrors(['recovery_code' => __('two_factor.invalid_recovery_code')]);
            }

            $account->replaceRecoveryCode($matched_code);
        } else {
            return back()->withErrors(['code' => __('two_factor.code_required')]);
        }

        $remember = (bool) $request->session()->pull($this->rememberKey(), false);
        $request->session()->forget($this->pendingIdKey());

        Auth::guard($this->guardName())->login($account, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route($this->intendedRoute()));
    }

    /**
     * The session key holding the pending account id.
     */
    protected function pendingIdKey(): string
    {
        return $this->guardName().'.login.id';
    }

    /**
     * The session key holding the pending remember-me preference.
     */
    protected function rememberKey(): string
    {
        return $this->guardName().'.login.remember';
    }

    /**
     * Resolve the account awaiting a challenge from the session.
     */
    protected function challengedAccount(Request $request): ?Authenticatable
    {
        $account_id = $request->session()->get($this->pendingIdKey());

        if (is_null($account_id)) {
            return null;
        }

        return Auth::guard($this->guardName())->getProvider()->retrieveById($account_id);
    }
}
