<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CertificatePdfService;

class CertificateController extends Controller
{
    /**
     * GET /api/certificates - list all certificates ordered by id desc
     */
    public function index()
    {
        try {
            $rows = DB::table('certificates')
                ->orderByDesc('id')
                ->get();

            return response()->json($rows);
        } catch (\Throwable $e) {
            Log::error('Certificates index error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/certificates/download/{id} - download generated certificate PDF.
     */
    public function download($id)
    {
        try {
            $certificate = DB::table('certificates')->where('id', $id)->first();

            if (! $certificate || empty($certificate->generated_pdf_path)) {
                return response()->json([
                    'message' => 'File sertifikat tidak ditemukan',
                ], 404);
            }

            $relativePath = ltrim($certificate->generated_pdf_path, '/');

            // Coba beberapa kemungkinan lokasi file untuk kompatibilitas
            $candidates = [];

            // Jika path sudah relatif terhadap root project (mis. uploads/certificates/xxx.pdf)
            $candidates[] = base_path($relativePath);

            // Jika ada file lama yang mungkin masih berada di public
            $candidates[] = public_path($relativePath);

            // Jika path yang tersimpan mengandung prefix public/, coba hilangkan
            if (str_starts_with($relativePath, 'public/')) {
                $trimmed = substr($relativePath, strlen('public/'));
                $candidates[] = base_path($trimmed);
                $candidates[] = public_path($trimmed);
            }

            $fullPath = null;
            foreach ($candidates as $path) {
                if ($path && file_exists($path)) {
                    $fullPath = $path;
                    break;
                }
            }

            if (! $fullPath) {
                return response()->json([
                    'message' => 'File sertifikat tidak tersedia di server',
                ], 404);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9\s]/', '', $certificate->name ?? 'sertifikat');
            $safeName = strtolower(preg_replace('/\s+/', '-', trim($safeName)) ?: 'sertifikat');

            $numberPart = $certificate->certificate_number ?? 'no-number';
            $numberPart = explode('/', (string) $numberPart)[0] ?? $numberPart;
            $safeNumber = preg_replace('/[^a-zA-Z0-9]/', '-', $numberPart);

            $downloadName = $safeNumber . '-sertifikat-' . $safeName . '.pdf';

            return response()->download($fullPath, $downloadName);
        } catch (\Throwable $e) {
            Log::error('Certificates download error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/certificates/{id} - get one certificate by id
     */
    public function show($id)
    {
        try {
            $certificate = DB::table('certificates')->where('id', $id)->first();

            if (!$certificate) {
                return response()->json([
                    'message' => 'Sertifikat tidak ditemukan',
                ], 404);
            }

            return response()->json($certificate);
        } catch (\Throwable $e) {
            Log::error('Certificate show error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: generate unique 8-char verify code.
     */
    public function generateUniqueVerifyCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        while (true) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $exists = DB::table('certificates')
                ->where('verify_code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }
    }

    /**
     * Helper: generate certificate number dengan format NNN/C/IB/MMM/YYYY.
     * Contoh: 001/C/IB/XII/2025
     * - NNN : nomor urut 3 digit (per tahun terbit)
     * - C   : huruf tetap
     * - IB  : huruf tetap (tidak lagi memakai nama perusahaan di nomor)
     * - MMM : bulan terbit dalam angka Romawi
     * - YYYY: tahun terbit
     */
    public function generateCertificateNumber(string $companyName, string $issuedDate): string
    {
        $date = new \DateTime($issuedDate);
        $monthNum = (int) $date->format('m');
        $year     = $date->format('Y');

        // Mapping bulan ke angka Romawi
        $romanMonths = [
            1  => 'I',
            2  => 'II',
            3  => 'III',
            4  => 'IV',
            5  => 'V',
            6  => 'VI',
            7  => 'VII',
            8  => 'VIII',
            9  => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        $romanMonth = $romanMonths[$monthNum] ?? 'I';

        try {
            // Cari nomor urut terbesar untuk kombinasi bulan (Romawi) dan tahun tersebut
            // Format nomor: NNN/C/IB/MMM/YYYY
            $row = DB::selectOne(<<<SQL
                SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(certificate_number, '/', 1) AS UNSIGNED)), 0) AS max_number
                FROM certificates
                WHERE certificate_number REGEXP '^[0-9]{3}/C/IB/[IVXLCDM]+/[0-9]{4}$'
                  AND RIGHT(certificate_number, 4) = ?
                  AND SUBSTRING_INDEX(SUBSTRING_INDEX(certificate_number, '/', 4), '/', -1) = ?
            SQL, [$year, $romanMonth]);

            $maxNumber = $row ? (int) $row->max_number : 0;
        } catch (\Throwable $e) {
            Log::error('generateCertificateNumber error, fallback used', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: pakai 3 digit terakhir timestamp sebagai nomor urut
            $fallbackNum = (int) substr((string) time(), -3);
            $formattedFallback = str_pad((string) $fallbackNum, 3, '0', STR_PAD_LEFT);
            return sprintf('%s/C/IB/%s/%s', $formattedFallback, $romanMonth, $year);
        }

        $next = $maxNumber + 1;
        if ($next > 999) {
            $next = 1;
        }

        $formatted = str_pad((string) $next, 3, '0', STR_PAD_LEFT);

        // Bentuk akhir: 001/C/IB/XII/2025
        return sprintf('%s/C/IB/%s/%s', $formatted, $romanMonth, $year);
    }

    /**
     * POST /api/certificates - create certificate.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->only([
                'name',
                'place_of_birth',
                'date_of_birth',
                'certificate_title',
                'category',
                'competency_field',
                'issued_date',
                'place_of_issue',
                'pdf_url',
                'company_name',
                'template_id',
            ]);

            $companyNameTrim = preg_replace('/\s+/', ' ', (string) ($data['company_name'] ?? ''));
            $companyNameTrim = trim($companyNameTrim);

            if ($companyNameTrim === '') {
                return response()->json([
                    'message' => 'Nama perusahaan wajib diisi',
                ], 400);
            }

            // Generate verify code & certificate number
            $verifyCode = $this->generateUniqueVerifyCode();
            $certificateNumber = $this->generateCertificateNumber($companyNameTrim, (string) $data['issued_date']);

            $id = DB::table('certificates')->insertGetId([
                'certificate_number' => $certificateNumber,
                'name'              => $data['name'] ?? null,
                'place_of_birth'    => $data['place_of_birth'] ?? null,
                'date_of_birth'     => $data['date_of_birth'] ?? null,
                'certificate_title' => $data['certificate_title'] ?? null,
                'category'          => $data['category'] ?? null,
                'competency_field'  => $data['competency_field'] ?? null,
                'issued_date'       => $data['issued_date'] ?? null,
                'place_of_issue'    => $data['place_of_issue'] ?? null,
                'pdf_url'           => $data['pdf_url'] ?? null,
                'verify_code'       => $verifyCode,
                'company_name'      => $companyNameTrim,
                'template_id'       => $data['template_id'] ?? null,
            ]);

            $certificate = DB::table('certificates')->where('id', $id)->first();

            // Generate PDF if template_id is provided and template is active
            if (!empty($certificate->template_id)) {
                $template = DB::table('certificate_templates')
                    ->where('id', $certificate->template_id)
                    ->where('is_active', 1)
                    ->first();

                if ($template) {
                    $templateArray = (array) $template;
                    $certificateArray = (array) $certificate;

                    $pdfService = new CertificatePdfService();
                    $relativePath = $pdfService->generate($templateArray, $certificateArray);

                    if ($relativePath) {
                        DB::table('certificates')
                            ->where('id', $certificate->id)
                            ->update(['generated_pdf_path' => $relativePath]);

                        $certificate->generated_pdf_path = $relativePath;
                    }
                }
            }

            return response()->json([
                'message'     => 'Sertifikat berhasil ditambahkan',
                'certificate' => $certificate,
            ]);
        } catch (\Throwable $e) {
            Log::error('Certificates store error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/certificates/{id} - update certificate without changing certificate_number.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->only([
                'name',
                'place_of_birth',
                'date_of_birth',
                'certificate_title',
                'category',
                'issued_date',
                'pdf_url',
                'company_name',
            ]);

            DB::table('certificates')
                ->where('id', $id)
                ->update([
                    'name'              => $data['name'] ?? null,
                    'place_of_birth'    => $data['place_of_birth'] ?? null,
                    'date_of_birth'     => $data['date_of_birth'] ?? null,
                    'certificate_title' => $data['certificate_title'] ?? null,
                    'category'          => $data['category'] ?? null,
                    'issued_date'       => $data['issued_date'] ?? null,
                    'pdf_url'           => $data['pdf_url'] ?? null,
                    'company_name'      => $data['company_name'] ?? null,
                ]);

            return response()->json([
                'message' => 'Sertifikat berhasil diperbarui',
            ]);
        } catch (\Throwable $e) {
            Log::error('Certificates update error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/certificates/{id} - delete certificate.
     */
    public function destroy($id)
    {
        try {
            $deleted = $this->deleteCertificateWithFile((int) $id);

            if ($deleted === 0) {
                return response()->json([
                    'message' => 'Sertifikat tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'message' => 'Sertifikat berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            Log::error('Certificates destroy error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: delete certificate row and its generated PDF file by id.
     */
    public function deleteCertificateWithFile(int $id): int
    {
        $certificate = DB::table('certificates')->where('id', $id)->first();

        if (! $certificate) {
            return 0;
        }

        $this->deleteCertificateFileByRecord($certificate);

        return DB::table('certificates')->where('id', $id)->delete();
    }

    /**
     * Helper: delete generated certificate PDF file for a certificate record.
     */
    protected function deleteCertificateFileByRecord($certificate): void
    {
        if (! $certificate || empty($certificate->generated_pdf_path)) {
            return;
        }

        $relativePath = ltrim($certificate->generated_pdf_path, '/');

        $candidates = [];
        $candidates[] = base_path($relativePath);
        $candidates[] = public_path($relativePath);

        if (str_starts_with($relativePath, 'public/')) {
            $trimmed = substr($relativePath, strlen('public/'));
            $candidates[] = base_path($trimmed);
            $candidates[] = public_path($trimmed);
        }

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
