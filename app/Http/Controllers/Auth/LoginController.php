<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = $credentials['login'];
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        $attemptData = [
            'password' => $credentials['password'],
            $isEmail ? 'email' : 'username' => $login,
        ];

        if (Auth::attempt($attemptData)) {
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        return back()->with('error', 'Invalid username/email or password for this tenant.');
    }
}
