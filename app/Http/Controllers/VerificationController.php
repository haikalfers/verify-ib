<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
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

            $certificate = DB::table('certificates')
                ->where('verify_code', $verifyCode)
                ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$name])
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
