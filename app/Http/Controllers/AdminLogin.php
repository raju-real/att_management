<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogin extends Controller
{
    public function __invoke(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $validated = $request->validate([
            'email'    => 'required|email|max:100',
            'password' => 'required|string|max:100',
        ]);

        $credentials = [
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'status'   => 'active',
        ];

        if (Auth::attempt($credentials, $request->boolean('remember_me'))) {
            // New session id after login (prevents session fixation).
            $request->session()->regenerate();

            $user = auth()->user();
            $user->last_login_at = now();
            $user->save();

            return redirect()->intended(route('dashboard'));
        }

        return redirect()
            ->back()
            ->withInput($request->only('email', 'remember_me'))
            ->with(dangerMessage('danger', 'Email or password does not match, or the account is inactive.'));
    }
}
