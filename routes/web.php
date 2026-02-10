<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\AdminWebAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminCertificateController;
use App\Http\Controllers\AdminTemplateController;
use App\Http\Controllers\AdminCompetencyUnitController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\CertificateController;

Route::get('/', [PublicVerificationController::class, 'index'])->name('public.landing');
Route::get('/verifikasi', [PublicVerificationController::class, 'form'])->name('public.verify.form');
Route::post('/verifikasi', [PublicVerificationController::class, 'verify'])->name('public.verify');
Route::get('/sertifikat/{id}/download', [CertificateController::class, 'download'])->name('public.certificate.download');

// Redirect /admin ke halaman yang sesuai
Route::get('/admin', function () {
    if (session()->has('admin_user')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
});

// Admin web auth
Route::get('/admin/login', [AdminWebAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminWebAuthController::class, 'login'])->name('admin.login.submit');
Route::get('/admin/verify-code', [AdminWebAuthController::class, 'showVerifyForm'])->name('admin.verify.form');
Route::post('/admin/verify-code', [AdminWebAuthController::class, 'verifyCode'])->name('admin.verify.submit');
Route::post('/admin/logout', [AdminWebAuthController::class, 'logout'])->name('admin.logout');

// Admin dashboard & modules (protected by admin session)
Route::middleware([\App\Http\Middleware\AdminWebMiddleware::class])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Kelola Sertifikat
    Route::get('/admin/certificates', [AdminCertificateController::class, 'index'])->name('admin.certificates.index');
    Route::get('/admin/certificates/create', [AdminCertificateController::class, 'create'])->name('admin.certificates.create');
    Route::post('/admin/certificates', [AdminCertificateController::class, 'store'])->name('admin.certificates.store');
    Route::post('/admin/certificates/destroy-page', [AdminCertificateController::class, 'destroyPage'])->name('admin.certificates.destroy-page');
    Route::post('/admin/certificates/download-page', [AdminCertificateController::class, 'downloadPage'])->name('admin.certificates.download-page');
    Route::get('/admin/certificates/import', [AdminCertificateController::class, 'importForm'])->name('admin.certificates.import.form');
    Route::post('/admin/certificates/import', [AdminCertificateController::class, 'importProcess'])->name('admin.certificates.import.process');
    Route::get('/admin/certificates/{id}/edit', [AdminCertificateController::class, 'edit'])->name('admin.certificates.edit');
    Route::put('/admin/certificates/{id}', [AdminCertificateController::class, 'update'])->name('admin.certificates.update');
    Route::delete('/admin/certificates/{id}', [AdminCertificateController::class, 'destroy'])->name('admin.certificates.destroy');
    Route::get('/admin/certificates/{id}/download', [CertificateController::class, 'download'])->name('admin.certificates.download');

    // Trash Sertifikat
    Route::get('/admin/certificates/trash', [AdminCertificateController::class, 'trash'])->name('admin.certificates.trash');
    Route::post('/admin/certificates/{id}/restore', [AdminCertificateController::class, 'restore'])->name('admin.certificates.restore');
    Route::delete('/admin/certificates/{id}/force-delete', [AdminCertificateController::class, 'forceDelete'])->name('admin.certificates.force-delete');
    Route::post('/admin/certificates/force-delete-bulk', [AdminCertificateController::class, 'forceDeleteBulk'])->name('admin.certificates.force-delete-bulk');

    // Template Sertifikat
    Route::get('/admin/templates', [AdminTemplateController::class, 'index'])->name('admin.templates.index');
    Route::get('/admin/templates/create', [AdminTemplateController::class, 'create'])->name('admin.templates.create');
    Route::post('/admin/templates', [AdminTemplateController::class, 'store'])->name('admin.templates.store');
    Route::get('/admin/templates/{id}/edit', [AdminTemplateController::class, 'edit'])->name('admin.templates.edit');
    Route::post('/admin/templates/{id}', [AdminTemplateController::class, 'update'])->name('admin.templates.update');
    Route::delete('/admin/templates/{id}', [AdminTemplateController::class, 'destroy'])->name('admin.templates.destroy');
    Route::post('/admin/templates/{id}/toggle', [AdminTemplateController::class, 'toggle'])->name('admin.templates.toggle');

    // Kelola Varian Template
    Route::get('/admin/templates/{id}/variants', [AdminTemplateController::class, 'variants'])->name('admin.templates.variants');
    Route::post('/admin/templates/{id}/variants', [AdminTemplateController::class, 'storeVariant'])->name('admin.templates.variants.store');
    Route::post('/admin/templates/variants/{variantId}/toggle', [AdminTemplateController::class, 'toggleVariant'])->name('admin.templates.variants.toggle');
    Route::post('/admin/templates/variants/{variantId}/default', [AdminTemplateController::class, 'setDefaultVariant'])->name('admin.templates.variants.default');
    Route::get('/admin/templates/variants/{variantId}/edit', [AdminTemplateController::class, 'editVariant'])->name('admin.templates.variants.edit');
    Route::post('/admin/templates/variants/{variantId}/update', [AdminTemplateController::class, 'updateVariant'])->name('admin.templates.variants.update');
    Route::delete('/admin/templates/variants/{variantId}', [AdminTemplateController::class, 'destroyVariant'])->name('admin.templates.variants.destroy');

    // Laporan
    Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export', [AdminReportController::class, 'exportCsv'])->name('admin.reports.export');

    // Unit Kompetensi (master PDF)
    Route::get('/admin/competency-units', [AdminCompetencyUnitController::class, 'index'])->name('admin.competency-units.index');
    Route::get('/admin/competency-units/create', [AdminCompetencyUnitController::class, 'create'])->name('admin.competency-units.create');
    Route::post('/admin/competency-units', [AdminCompetencyUnitController::class, 'store'])->name('admin.competency-units.store');
    Route::get('/admin/competency-units/{id}/edit', [AdminCompetencyUnitController::class, 'edit'])->name('admin.competency-units.edit');
    Route::put('/admin/competency-units/{id}', [AdminCompetencyUnitController::class, 'update'])->name('admin.competency-units.update');
    Route::delete('/admin/competency-units/{id}', [AdminCompetencyUnitController::class, 'destroy'])->name('admin.competency-units.destroy');
    // Route::get('/debug-mail', function () {
    //     return response()->json(config('mail.mailers.smtp'));
    // });
});
