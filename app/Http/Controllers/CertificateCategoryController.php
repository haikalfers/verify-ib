<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificateCategoryController extends Controller
{
    /**
     * GET /api/certificates/categories - list categories ordered by name.
     */
    public function index()
    {
        try {
            $rows = DB::table('categories')
                ->select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json($rows);
        } catch (\Throwable $e) {
            Log::error('Categories index error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/certificates/categories - create category (idempotent on duplicate).
     */
    public function store(Request $request)
    {
        try {
            $name = preg_replace('/\s+/', ' ', (string) $request->input('name', ''));
            $name = trim($name);

            if ($name === '') {
                return response()->json([
                    'message' => 'Nama kategori wajib diisi',
                ], 400);
            }

            try {
                $id = DB::table('categories')->insertGetId([
                    'name' => $name,
                ]);

                $category = DB::table('categories')
                    ->select('id', 'name')
                    ->where('id', $id)
                    ->first();

                return response()->json([
                    'message' => 'Kategori berhasil dibuat',
                    'category' => $category,
                ]);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'Duplicate') || str_contains($msg, 'ER_DUP_ENTRY')) {
                    $category = DB::table('categories')
                        ->select('id', 'name')
                        ->where('name', $name)
                        ->first();

                    return response()->json([
                        'message' => 'Kategori sudah ada',
                        'category' => $category,
                    ], 200);
                }

                throw $e;
            }
        } catch (\Throwable $e) {
            Log::error('Categories store error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
