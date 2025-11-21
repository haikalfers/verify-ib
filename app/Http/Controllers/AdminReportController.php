<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        // Statistik utama
        $totalCertificates = DB::table('certificates')->count();
        $totalTemplates    = DB::table('certificate_templates')->count();

        // Sertifikat per kategori
        $byCategory = DB::table('certificates')
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // Sertifikat terbit per bulan (12 bulan terakhir)
        $byMonth = DB::table('certificates')
            ->select(
                DB::raw("DATE_FORMAT(issued_date, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('issued_date')
            ->groupBy('ym')
            ->orderBy('ym', 'desc')
            ->limit(12)
            ->get()
            ->reverse(); // urut kronologis

        return view('admin.reports.index', [
            'totalCertificates' => $totalCertificates,
            'totalTemplates'    => $totalTemplates,
            'byCategory'        => $byCategory,
            'byMonth'           => $byMonth,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'laporan-sertifikat-' . date('Y-m-d') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM untuk kompatibilitas Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header kolom (pakai delimiter ; supaya lebih rapi di Excel locale Indonesia)
            fputcsv($handle, [
                'Nomor Sertifikat',
                'Nama',
                'Perusahaan',
                'Kategori',
                'Judul Sertifikat',
                'Tanggal Terbit',
                'Kode Verifikasi',
                'Template',
                'PDF',
            ], ';');

            $rows = DB::table('certificates')
                ->leftJoin('certificate_templates', 'certificates.template_id', '=', 'certificate_templates.id')
                ->select(
                    'certificates.certificate_number',
                    'certificates.name',
                    'certificates.company_name',
                    'certificates.category',
                    'certificates.certificate_title',
                    'certificates.issued_date',
                    'certificates.verify_code',
                    'certificate_templates.name as template_name',
                    'certificates.generated_pdf_path'
                )
                ->orderByDesc('certificates.id')
                ->cursor();

            foreach ($rows as $row) {
                $issuedDate = $row->issued_date
                    ? \Illuminate\Support\Carbon::parse($row->issued_date)->format('d-m-Y')
                    : '';

                fputcsv($handle, [
                    $row->certificate_number,
                    $row->name,
                    $row->company_name,
                    $row->category,
                    $row->certificate_title,
                    $issuedDate,
                    $row->verify_code,
                    $row->template_name,
                    $row->generated_pdf_path ? 'YA' : '-',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
