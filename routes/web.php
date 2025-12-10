<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\AdminWebAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminCertificateController;
use App\Http\Controllers\AdminTemplateController;
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

    // Template Sertifikat
    Route::get('/admin/templates', [AdminTemplateController::class, 'index'])->name('admin.templates.index');
    Route::get('/admin/templates/create', [AdminTemplateController::class, 'create'])->name('admin.templates.create');
    Route::post('/admin/templates', [AdminTemplateController::class, 'store'])->name('admin.templates.store');
    Route::get('/admin/templates/{id}/edit', [AdminTemplateController::class, 'edit'])->name('admin.templates.edit');
    Route::post('/admin/templates/{id}', [AdminTemplateController::class, 'update'])->name('admin.templates.update');
    Route::delete('/admin/templates/{id}', [AdminTemplateController::class, 'destroy'])->name('admin.templates.destroy');
    Route::post('/admin/templates/{id}/toggle', [AdminTemplateController::class, 'toggle'])->name('admin.templates.toggle');

    // Laporan
    Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export', [AdminReportController::class, 'exportCsv'])->name('admin.reports.export');
});
