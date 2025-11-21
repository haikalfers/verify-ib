<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWebAuthController extends Controller
{
    public function showLoginForm()
    {
        if (session()->has('admin_user')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users')
            ->where('email', $credentials['email'])
            ->where('password', $credentials['password']) // mengikuti implementasi API saat ini
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->withInput(['email' => $credentials['email']]);
        }

        session(['admin_user' => [
            'id' => $user->id,
            'email' => $user->email,
        ]]);

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        session()->forget('admin_user');

        return redirect()->route('admin.login');
    }
}
