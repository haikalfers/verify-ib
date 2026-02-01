<?php

namespace App\Http\Controllers;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CertificatePdfService
{
    /**
     * Generate certificate PDF based on template and certificate data.
     *
     * @param array $template    Row from certificate_templates
     * @param array $certificate Row from certificates
     * @return string|null       Relative path (e.g. /uploads/certificates/xxx.pdf) or null on failure
     */
    public function generate(array $template, array $certificate): ?string
    {
        try {
            if (empty($template['file_path']) || empty($certificate['certificate_number'])) {
                return null;
            }

            $relativePath = ltrim($template['file_path'], '/');

            // Coba cari di lokasi baru (root uploads) dan lokasi lama (public/uploads) untuk kompatibilitas
            $candidatePaths = [
                base_path($relativePath),
                public_path($relativePath),
            ];

            $templatePath = null;
            foreach ($candidatePaths as $path) {
                if (File::exists($path)) {
                    $templatePath = $path;
                    break;
                }
            }

            if (!$templatePath) {
                Log::warning('Template file not found for PDF generation', [
                    'candidates' => $candidatePaths,
                    'file_path'  => $template['file_path'],
                ]);
                return null;
            }

            if (!defined('FPDF_FONTPATH')) {
                // Gunakan folder font bawaan FPDF (berisi helvetica.php, arial.php, dll.)
                define('FPDF_FONTPATH', base_path('vendor/setasign/fpdf/font/'));
            }

            // Coordinates: try from template JSON, else use simple defaults (A4, unit mm)
            $coords = [];
            if (!empty($template['coordinates'])) {
                $decoded = json_decode($template['coordinates'], true);
                if (is_array($decoded)) {
                    $coords = $decoded;
                }
            }

            $pageW = 210.0;
            $pageH = 297.0;
            $orientation = 'P';

            if (!empty($coords['_page']) && is_array($coords['_page'])) {
                $pageConf = $coords['_page'];
                $w = $pageConf['width'] ?? null;
                $h = $pageConf['height'] ?? null;
                $ori = $pageConf['orientation'] ?? null;

                if (is_numeric($w) && is_numeric($h) && (float) $w > 0 && (float) $h > 0) {
                    $pageW = (float) $w;
                    $pageH = (float) $h;
                }

                if (is_string($ori) && ($ori === 'L' || $ori === 'P')) {
                    $orientation = $ori;
                } else {
                    $orientation = $pageW > $pageH ? 'L' : 'P';
                }
            } else {
                $orientation = $pageW > $pageH ? 'L' : 'P';
            }

            $pdf = new Fpdi();

            $pdf->AddFont('GeoSlab703', '', 'GeoSlab703-MdCnBT.php');
            $pdf->AddFont('GeoSlab703', 'B', 'GeoSlab703-MdCnBT-Bold.php');

            // Handle template background based on file_type
            $fileType = $template['file_type'] ?? 'pdf';
            try {
                if ($fileType === 'pdf') {
                    // Import first page of PDF as background
                    $pdf->setSourceFile($templatePath);
                    $tplId = $pdf->importPage(1);
                    $tplSize = $pdf->getTemplateSize($tplId);

                    $pdf->AddPage($tplSize['orientation'] ?? 'P', [$tplSize['width'], $tplSize['height']]);
                    $pdf->useTemplate($tplId, 0, 0, $tplSize['width'], $tplSize['height'], true);
                } else {
                    // Treat as image (png/jpg/jpeg) and draw full-page background
                    $pdf->AddPage($orientation, [$pageW, $pageH]);
                    $pdf->Image($templatePath, 0, 0, $pageW, $pageH);
                }
            } catch (\Throwable $bgError) {
                // If background fails, log but continue to draw text on blank page
                if ($pdf->PageNo() < 1) {
                    $pdf->AddPage($orientation, [$pageW, $pageH]);
                }

                Log::error('Failed to apply template background for certificate PDF', [
                    'error' => $bgError->getMessage(),
                    'file_type' => $fileType,
                    'path' => $templatePath,
                ]);
            }

            // Default font setup (GeoSlab703 as primary font)
            $pdf->SetFont('GeoSlab703', '', 12);
            $pdf->SetTextColor(0, 0, 0);

            // Defaults approximated from Node pdfGenerator.js (A4, pdf-lib points -> mm)
            // These match visual layout used sebelumnya.
            $defaults = [
                // Nomor Sertifikat - tengah halaman, sekitar 30% dari atas (abu-abu gelap)
                'certificate_number' => [
                    'x'        => 103,
                    'y'        => 90,
                    'size'     => 15,
                    'centered' => true,
                    'semibold' => true,
                    'color'    => ['r' => 0.1, 'g' => 0.1, 'b' => 0.1],
                ],
                // Nama - tengah halaman, lebih ke tengah, merah (tanpa bold agar sedikit lebih ringan)
                'name' => [
                    'x'        => 103,
                    'y'        => 120,
                    'size'     => 40,
                    'centered' => true,
                    'bold'     => true,
                    'color'    => ['r' => 142 / 255, 'g' => 0, 'b' => 0],
                ],
                // Nama sekolah/perusahaan - sedikit di bawah nama, merah
                'company_name' => [
                    'x'        => 103,
                    'y'        => 135,
                    'size'     => 18,
                    'centered' => true,
                    'color'    => ['r' => 139 / 255, 'g' => 0, 'b' => 0],
                ],
                // Bidang kompetensi - tengah, merah
                'competency_field' => [
                    'x'        => 103,
                    'y'        => 164,
                    'size'     => 18,
                    'centered' => true,
                    'color'    => ['r' => 139 / 255, 'g' => 0, 'b' => 0],
                ],
                // Topik kompetensi - sedikit di bawah bidang kompetensi, merah
                'certificate_title' => [
                    'x'        => 103,
                    'y'        => 191,
                    'size'     => 18,
                    'centered' => true,
                    'color'    => ['r' => 139 / 255, 'g' => 0, 'b' => 0],
                ],
                // Tempat & tanggal terbit - bagian bawah, center, abu-abu gelap
                'issued_date' => [
                    'x'        => 103,
                    'y'        => 228,
                    'size'     => 12,
                    'centered' => true,
                    'color'    => ['r' => 0.1, 'g' => 0.1, 'b' => 0.1],
                ],
                // Kode verifikasi - dekat bawah kanan, abu-abu
                'verify_code' => [
                    'x'        => 163,
                    'y'        => 271,
                    'size'     => 10,
                    'centered' => false,
                    'color'    => ['r' => 0.3, 'g' => 0.3, 'b' => 0.3],
                ],
            ];

            $drawText = function (?string $text, string $field) use ($pdf, $coords, $defaults): void {
                $text = (string)($text ?? '');
                if ($text === '') {
                    return;
                }

                // Ambil koordinat dari template->coordinates atau default
                $coord = $coords[$field] ?? $defaults[$field] ?? null;
                if (!$coord) {
                    return;
                }

                $size     = $coord['size']      ?? 12;
                $centered = $coord['centered']  ?? false;
                $x        = $coord['x']         ?? 10;
                $y        = $coord['y']         ?? 10;

                $bold  = !empty($coord['bold']);
                $color = $coord['color'] ?? ['r' => 0, 'g' => 0, 'b' => 0];

                // Gunakan Arial Narrow khusus untuk nomor sertifikat, tanggal terbit, & kode verifikasi; lainnya GeoSlab703
                $arialFields = ['certificate_number', 'issued_date', 'verify_code'];
                $family = in_array($field, $arialFields, true) ? 'Arial' : 'GeoSlab703';


                $pdf->SetFont($family, $bold ? 'B' : '', $size);
                $pdf->SetTextColor(
                    (int) round(($color['r'] ?? 0) * 255),
                    (int) round(($color['g'] ?? 0) * 255),
                    (int) round(($color['b'] ?? 0) * 255)
                );

                if ($centered) {
                    $width = $pdf->GetStringWidth($text);
                    $x = $x - ($width / 2);
                }

                $pdf->SetXY($x, $y);
                $pdf->Write(0, $text);
            };

            // Draw fields
            $drawText('NO : ' . ($certificate['certificate_number'] ?? ''), 'certificate_number');
            $drawText($certificate['name'] ?? '', 'name');
            $drawText($certificate['company_name'] ?? '', 'company_name');
            $drawText($certificate['competency_field'] ?? '', 'competency_field');
            $drawText($certificate['certificate_title'] ?? '', 'certificate_title');

            if (!empty($certificate['issued_date'])) {
                try {
                    $dt = new \DateTime($certificate['issued_date']);

                    // Format tanggal dengan nama bulan Indonesia (contoh: 03 Desember 2025)
                    $bulanIndo = [
                        1  => 'Januari',
                        2  => 'Februari',
                        3  => 'Maret',
                        4  => 'April',
                        5  => 'Mei',
                        6  => 'Juni',
                        7  => 'Juli',
                        8  => 'Agustus',
                        9  => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ];

                    $day   = (int) $dt->format('d');
                    $month = (int) $dt->format('m');
                    $year  = $dt->format('Y');

                    $namaBulan = $bulanIndo[$month] ?? $dt->format('F');
                    $formatted = sprintf('%02d %s %s', $day, $namaBulan, $year);

                    $place = $certificate['place_of_issue'] ?? null;
                    $placeAndDate = $place ? ($place . ', ' . $formatted) : $formatted;
                    $drawText($placeAndDate, 'issued_date');
                } catch (\Throwable $e) {
                    // ignore date formatting errors
                }
            }

            if (!empty($certificate['verify_code'])) {
                $drawText($certificate['verify_code'], 'verify_code');
            }

            // Build safe filename
            $safeName = preg_replace('/[^a-zA-Z0-9\s]/', '', $certificate['name'] ?? '');
            $safeName = strtolower(preg_replace('/\s+/', '-', trim($safeName)));

            $safeNumber = preg_replace('/[^a-zA-Z0-9]/', '-', $certificate['certificate_number'] ?? '');
            $timestamp = time();

            $filename = sprintf('cert-%s-%s-%s.pdf', $safeNumber, $safeName, $timestamp);

            $dir = base_path('uploads/certificates');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0775, true);
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
            $relativePath = '/uploads/certificates/' . $filename;

            $pdf->Output($fullPath, 'F');

            return $relativePath;
        } catch (\Throwable $e) {
            Log::error('Certificate PDF generation failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

