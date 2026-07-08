<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared "confirm your password" flow used to gate sensitive actions.
 *
 * On success the confirmation timestamp is stored in the session under the
 * standard `auth.password_confirmed_at` key, which callers compare against
 * `config('auth.password_timeout')`.
 */
abstract class ConfirmsPassword extends Controller
{
    /**
     * The authentication guard whose password is being confirmed.
     */
    abstract protected function guardName(): string;

    /**
     * The Inertia component rendered for the confirmation form.
     */
    abstract protected function confirmComponent(): string;

    /**
     * The route to return to once the password is confirmed.
     */
    abstract protected function redirectRoute(): string;

    /**
     * Show the password confirmation form.
     */
    public function show(Request $request): Response
    {
        return Inertia::render($this->confirmComponent());
    }

    /**
     * Validate the password and record the confirmation timestamp.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $account = $request->user($this->guardName());

        if (! Hash::check($validated['password'], $account->password)) {
            return back()->withErrors(['password' => __('auth.password')]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->route($this->redirectRoute());
    }
}
