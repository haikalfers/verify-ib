<?php

namespace App\Http\Controllers;

use App\Mail\AdminOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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
        if (
            $credentials['email'] !== self::ADMIN_EMAIL ||
            $credentials['password'] !== self::ADMIN_PASSWORD
        ) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->withInput(['email' => $credentials['email']]);
        }

        // Jika device sudah pernah verifikasi, langsung login
        if ($request->cookie('admin_email_verified') === '1') {
            session(['admin_user' => [
                'id' => 1,
                'email' => self::ADMIN_EMAIL,
            ]]);

            return redirect()->route('admin.dashboard');
        }

        // Generate OTP
        $code = Str::upper(Str::random(6));

        session([
            'admin_pending_verification_code' => $code,
        ]);

        try {
            // ✅ KIRIM EMAIL DENGAN MAILABLE (HTML)
            Mail::to(self::ADMIN_EMAIL)
                ->send(new AdminOtpMail($code));

            $statusMessage = 'Kode verifikasi telah dikirim ke email admin.';
        } catch (TransportExceptionInterface $e) {
            // ❌ SMTP benar-benar gagal
            \Log::error('SMTP error saat mengirim OTP admin', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Gagal mengirim email verifikasi. Silakan coba lagi.',
            ]);
        }

        return redirect()
            ->route('admin.verify.form')
            ->with('status', $statusMessage);
    }

    public function showVerifyForm(Request $request)
    {
        if ($request->cookie('admin_email_verified') === '1') {
            if (!session()->has('admin_user')) {
                session(['admin_user' => [
                    'id' => 1,
                    'email' => self::ADMIN_EMAIL,
                ]]);
            }

            return redirect()->route('admin.dashboard');
        }

        if (!session()->has('admin_pending_verification_code')) {
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

        if (strtoupper($request->code) !== strtoupper($expectedCode)) {
            return back()
                ->withErrors(['code' => 'Kode verifikasi salah'])
                ->withInput(['code' => $request->code]);
        }

        // Kode benar
        session()->forget('admin_pending_verification_code');

        session(['admin_user' => [
            'id' => 1,
            'email' => self::ADMIN_EMAIL,
        ]]);

        return redirect()
            ->route('admin.dashboard')
            ->withCookie(cookie()->forever('admin_email_verified', '1'));
    }

    public function logout()
    {
        session()->forget('admin_user');

        return redirect()->route('admin.login');
    }
}
