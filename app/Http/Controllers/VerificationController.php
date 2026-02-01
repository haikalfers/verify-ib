<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected function normalizeNameForSearch(?string $name): string
    {
        $name = preg_replace('/\s+/', ' ', (string) $name);
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $base = $name;
        if (str_contains($base, ',')) {
            $parts = explode(',', $base);
            $base = trim((string) ($parts[0] ?? ''));
        }

        $baseLower = strtolower($base);
        $baseLower = preg_replace('/[^a-z\s\-\-\]/u', ' ', $baseLower);
        $baseLower = preg_replace('/\s+/', ' ', $baseLower);
        $baseLower = trim($baseLower);

        $prefixes = ['dr', 'drg', 'prof', 'ir', 'h', 'hj'];
        foreach ($prefixes as $p) {
            if (str_starts_with($baseLower, $p . ' ')) {
                $baseLower = trim(substr($baseLower, strlen($p) + 1));
                break;
            }
        }

        return $baseLower;
    }

    /**
     * Mirror Node.js /api/verify endpoint.
     */
    public function verify(Request $request)
    {
        try {
            $verifyCode = trim((string) $request->input('verify_code', ''));
            $name = trim((string) $request->input('name', ''));
            $dateOfBirth = trim((string) $request->input('date_of_birth', ''));

            if ($verifyCode === '' || $name === '' || $dateOfBirth === '') {
                return response()->json([
                    'message' => 'verify_code, name, dan date_of_birth wajib diisi',
                ], 400);
            }

            $nameSearch = $this->normalizeNameForSearch($name);
            if ($nameSearch === '') {
                return response()->json([
                    'message' => 'name wajib diisi',
                ], 400);
            }

            $certificate = DB::table('certificates')
                ->whereNull('deleted_at')
                ->where('verify_code', $verifyCode)
                ->whereRaw(
                    "LOWER(TRIM(COALESCE(name_search, TRIM(SUBSTRING_INDEX(name, ',', 1))))) = LOWER(TRIM(?))",
                    [$nameSearch]
                )
                ->whereRaw('DATE(date_of_birth) = DATE(?)', [$dateOfBirth])
                ->first();

            if (!$certificate) {
                return response()->json([
                    'message' => 'Sertifikat tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'message' => 'Sertifikat valid',
                'certificate' => $certificate,
            ]);
        } catch (\Throwable $e) {
            Log::error('Verify certificate error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
