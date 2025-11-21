<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateCategoryController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Contoh bawaan Laravel (boleh dibiarkan)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth admin (mirror POST /api/admin/login)
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Sertifikat (mirror /api/certificates)
Route::get('/certificates', [CertificateController::class, 'index']);

// Kategori sertifikat (mirror /api/certificates/categories)
Route::get('/certificates/categories', [CertificateCategoryController::class, 'index']);
Route::post('/certificates/categories', [CertificateCategoryController::class, 'store']);

// GET satu sertifikat by id
Route::get('/certificates', [CertificateController::class, 'index']);
Route::post('/certificates', [CertificateController::class, 'store']);
Route::put('/certificates/{id}', [CertificateController::class, 'update']);
Route::delete('/certificates/{id}', [CertificateController::class, 'destroy']);
Route::get('/certificates/download/{id}', [CertificateController::class, 'download']);
Route::get('/certificates/{id}', [CertificateController::class, 'show']);

// Templates (mirror /api/templates)
Route::get('/templates', [TemplateController::class, 'index']);
Route::get('/templates/active', [TemplateController::class, 'active']);
Route::get('/templates/{id}', [TemplateController::class, 'show']);
Route::post('/templates', [TemplateController::class, 'store']);
Route::put('/templates/{id}', [TemplateController::class, 'update']);
Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);
Route::patch('/templates/{id}/toggle', [TemplateController::class, 'toggle']);

// Verifikasi publik (mirror POST /api/verify)
Route::post('/verify', [VerificationController::class, 'verify']);