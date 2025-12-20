<?php

namespace App\Http\Controllers;

use App\Mail\AdminOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

        if (
            $credentials['email'] !== self::ADMIN_EMAIL ||
            $credentials['password'] !== self::ADMIN_PASSWORD
        ) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->withInput(['email' => $credentials['email']]);
        }

        if ($request->cookie('admin_email_verified') === '1') {
            session(['admin_user' => [
                'id' => 1,
                'email' => self::ADMIN_EMAIL,
            ]]);

            return redirect()->route('admin.dashboard');
        }

        // Generate OTP
        $code = self::generateVerificationCode();

        session([
            'admin_pending_verification_code' => $code,
        ]);

        try {
            // ★ KONTEN KEAMANAN EMAIL
            $context = self::buildOtpSecurityContext($request);

            Mail::to(self::ADMIN_EMAIL)
                ->send(new AdminOtpMail($code, $context));

            $statusMessage = 'Kode verifikasi telah dikirim ke email admin.';
        } catch (TransportExceptionInterface $e) {
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

    /**
     * ===============================
     * ★ BAGIAN YANG DIPERBAIKI
     * ===============================
     */
    private static function buildOtpSecurityContext(Request $request): array
    {
        $ip = $request->ip();
        $rawUa = (string) $request->header('User-Agent', '');
        $userAgent = self::parseUserAgent($rawUa);

        $location = self::resolveLocationFromIp($ip);

        // ★ WAKTU LOGIN DALAM WIB (UTC+7)
        $loginTimeWib = now()
            ->setTimezone('Asia/Jakarta')
            ->format('d M Y, H:i:s') . ' WIB';

        return [
            'ip' => $ip,
            'user_agent' => $userAgent !== '' ? $userAgent : 'Tidak diketahui',
            'location' => $location,
            'login_time' => $loginTimeWib, // ★ SUDAH WIB
        ];
    }

    private static function resolveLocationFromIp(string $ip): string
    {
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,country");
            if (! $response) {
                return 'Tidak tersedia';
            }

            $data = json_decode($response, true);
            if (($data['status'] ?? null) !== 'success') {
                return 'Tidak tersedia';
            }

            $parts = array_filter([
                $data['city'] ?? null,
                $data['country'] ?? null,
            ]);

            return $parts ? implode(', ', $parts) : 'Tidak tersedia';
        } catch (\Throwable $e) {
            return 'Tidak tersedia';
        }
    }

    private static function parseUserAgent(string $ua): string
    {
        $browser = 'Browser tidak diketahui';
        $os = 'OS tidak diketahui';

        if (stripos($ua, 'Edg/') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($ua, 'Brave/') !== false || stripos($ua, 'Brave') !== false) {
            $browser = 'Brave';
        } elseif (stripos($ua, 'Chrome/') !== false) {
            $browser = 'Google Chrome';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Mozilla Firefox';
        } elseif (stripos($ua, 'Safari/') !== false) {
            $browser = 'Safari';
        }

        if (stripos($ua, 'Windows NT 10') !== false) {
            $os = 'Windows 10';
        } elseif (stripos($ua, 'Windows NT 11') !== false) {
            $os = 'Windows 11';
        } elseif (stripos($ua, 'Mac OS X') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false) {
            $os = 'iOS';
        }

        return "{$browser} · {$os}";
    }

    private static function generateVerificationCode(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';

        $chars = [];

        for ($i = 0; $i < 3; $i++) {
            $chars[] = $letters[random_int(0, strlen($letters) - 1)];
        }

        for ($i = 0; $i < 3; $i++) {
            $chars[] = $digits[random_int(0, strlen($digits) - 1)];
        }

        shuffle($chars);

        return implode('', $chars);
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
