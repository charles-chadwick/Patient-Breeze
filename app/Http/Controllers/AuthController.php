<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function login(Request $request) : Response|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return Inertia::render('Users/Login');
        }

        $credentials = $request->validate([
            'email'    => [
                'required',
                'email'
            ],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()
                ->regenerate();

            return redirect()->intended();
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request) : RedirectResponse
    {
        Auth::logout();

        $request->session()
            ->invalidate();
        $request->session()
            ->regenerateToken();

        return redirect('/');
    }
}
