<?php

namespace App\Http\Controllers;

use App\Models\TrustedDevice;
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
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'status' => 'active',
        ];

        if (Auth::attempt($credentials, $request->remember)) {
            $user = auth()->user();
            $user->last_login_at = now();
            $user->save();
            return redirect()->intended(route('dashboard'));
        }

        return redirect()
            ->back()
            ->withInput($request->only('email', 'remember'))
            ->with('message', 'Email or Password not matched!');
    }
}
