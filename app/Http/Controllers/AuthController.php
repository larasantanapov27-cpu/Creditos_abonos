<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|alpha_dash:ascii|min:3|max:50|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:72|confirmed',
        ]);
        User::create($d);

        return redirect()->route('login')->with('success', 'Cuenta creada. Ya puedes iniciar sesión.');
    }

    public function authenticate(Request $r)
    {
        $d = $r->validate(['username' => 'required|string|max:50', 'password' => 'required|string']);
        if (! Auth::attempt($d)) {
            return back()->withErrors(['username' => 'El usuario o la contraseña no son correctos.'])->onlyInput('username');
        }
        $r->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
