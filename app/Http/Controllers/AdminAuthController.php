<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    /**
     * Mirror Node.js /api/admin/login endpoint (plain email/password check).
     */
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        try {
            $user = DB::table('users')
                ->where('email', $email)
                ->where('password', $password)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Email atau password salah',
                ], 401);
            }

            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin login error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
