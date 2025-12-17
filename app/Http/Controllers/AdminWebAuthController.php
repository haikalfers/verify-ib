<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminWebAuthController extends Controller
{
    private const ADMIN_EMAIL = 'divisipendidikan@indobismar.org';
    private const ADMIN_PASSWORD = 'Pendidikan123567#';

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

        // Batasi hanya ke satu akun tetap
        if ($credentials['email'] !== self::ADMIN_EMAIL || $credentials['password'] !== self::ADMIN_PASSWORD) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->withInput(['email' => $credentials['email']]);
        }

        // Jika device sudah pernah verifikasi (cookie ada), langsung login
        if ($request->cookie('admin_email_verified') === '1') {
            session(['admin_user' => [
                'id' => 1,
                'email' => self::ADMIN_EMAIL,
            ]]);

            return redirect()->route('admin.dashboard');
        }

        // Jika belum verifikasi di device ini, kirim kode verifikasi ke email admin
        $code = Str::upper(Str::random(6));

        session([
            'admin_pending_verification_code' => $code,
        ]);

        try {
            Mail::raw("Kode verifikasi login admin Anda: {$code}", function ($message) {
                $message->to(self::ADMIN_EMAIL)
                    ->subject('Kode Verifikasi Login Admin');
            });

            $statusMessage = 'Kode verifikasi telah dikirim ke email admin.';
        } catch (\Throwable $e) {
            \Log::error('Gagal mengirim email verifikasi admin', [
                'error' => $e->getMessage(),
            ]);

            // Di lingkungan lokal / saat SMTP belum siap, tetap lanjut ke halaman verifikasi
            $statusMessage = "Gagal mengirim email verifikasi. Gunakan kode berikut secara manual: {$code}";
        }

        return redirect()->route('admin.verify.form')
            ->with('status', $statusMessage);
    }

    public function logout()
    {
        session()->forget('admin_user');

        return redirect()->route('admin.login');
    }

    public function showVerifyForm(Request $request)
    {
        // Jika device sudah memiliki cookie verifikasi, pastikan sesi login ada lalu arahkan ke dashboard
        if ($request->cookie('admin_email_verified') === '1') {
            if (! session()->has('admin_user')) {
                session(['admin_user' => [
                    'id' => 1,
                    'email' => self::ADMIN_EMAIL,
                ]]);
            }

            return redirect()->route('admin.dashboard');
        }

        // Jika tidak ada kode yang menunggu verifikasi, kembali ke halaman login
        if (! session()->has('admin_pending_verification_code')) {
            return redirect()->route('admin.login');
        }

        return view('admin.verify-code');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $expectedCode = session('admin_pending_verification_code');

        if (! $expectedCode) {
            return redirect()->route('admin.login');
        }

        if (strtoupper($request->input('code')) !== strtoupper($expectedCode)) {
            return back()
                ->withErrors(['code' => 'Kode verifikasi salah'])
                ->withInput(['code' => $request->input('code')]);
        }

        // Kode benar: hapus kode dari sesi dan set user + cookie verifikasi device
        session()->forget('admin_pending_verification_code');

        session(['admin_user' => [
            'id' => 1,
            'email' => self::ADMIN_EMAIL,
        ]]);

        return redirect()->route('admin.dashboard')
            ->withCookie(cookie()->forever('admin_email_verified', '1'));
    }
}
