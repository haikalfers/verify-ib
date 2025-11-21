<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Statistik sederhana untuk dashboard awal
        $totalCertificates = DB::table('certificates')->count();
        $totalTemplates = DB::table('certificate_templates')->count();

        // Sertifikat terbaru untuk panel Aktivitas Terbaru
        $latestCertificates = DB::table('certificates')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalCertificates' => $totalCertificates,
            'totalTemplates' => $totalTemplates,
            'latestCertificates' => $latestCertificates,
        ]);
    }
}
