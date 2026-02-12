<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('certificates')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        $search = $request->get('q');
        $categoryFilter = $request->get('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        $certificates = $query->paginate(10)->withQueryString();

        $categories = DB::table('categories')
            ->orderBy('name', 'asc')
            ->pluck('name');

        return view('admin.certificates.index', [
            'certificates' => $certificates,
            'search'       => $search,
            'categories'   => $categories,
            'category'     => $categoryFilter,
        ]);
    }

    public function importForm()
    {
        $competencyUnits = DB::table('competency_unit_templates')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.certificates.import', [
            'competencyUnits' => $competencyUnits,
        ]);
    }

    public function importProcess(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $validated['file'];
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca'])->withInput();
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->withErrors(['file' => 'Gagal membuka file'])->withInput();
        }

        // Gunakan delimiter ';' (format default banyak export Excel di lokal ID)
        $delimiter = ';';
        $headers = fgetcsv($handle, 0, $delimiter);
        if (! $headers) {
            fclose($handle);
            return back()->withErrors(['file' => 'File CSV kosong atau header tidak terbaca'])->withInput();
        }

        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headers);

        $requiredHeaders = [
            'company_name',
            'template_name',
            'name',
            'place_of_birth',
            'date_of_birth',
            'category',
            'competency_field',
            'place_of_issue',
            'issued_date',
            'certificate_title',
            'internship_start_date',
            'internship_end_date',
            'competency_unit_name', // opsional, untuk memilih Unit Kompetensi berdasarkan nama template
        ];

        foreach (['company_name', 'template_name', 'name', 'place_of_birth', 'date_of_birth', 'category', 'place_of_issue', 'issued_date'] as $req) {
            if (! in_array($req, $headers, true)) {
                fclose($handle);
                return back()->withErrors(['file' => "Header CSV wajib mengandung kolom: {$req}"])->withInput();
            }
        }

        $results = [
            'total'   => 0,
            'success' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        // Jika user memilih Unit Kompetensi global dari form import, siapkan sekali di sini
        $globalUnitKompetensiPath = null;
        $selectedUnitId = $request->input('competency_unit_template_id');

        if ($selectedUnitId) {
            $globalUnit = DB::table('competency_unit_templates')
                ->where('id', (int) $selectedUnitId)
                ->where('is_active', 1)
                ->first();

            if (! $globalUnit) {
                fclose($handle);
                return back()->withErrors(['file' => 'Unit kompetensi yang dipilih pada form import tidak ditemukan atau tidak aktif'])->withInput();
            }

            if (empty($globalUnit->file_path)) {
                fclose($handle);
                return back()->withErrors(['file' => 'Unit kompetensi yang dipilih pada form import tidak memiliki file PDF yang valid'])->withInput();
            }

            $globalUnitKompetensiPath = $globalUnit->file_path;
        }

        $line = 1; // sudah baca header
        $apiController = new CertificateController();

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($row === [null] || count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) {
                continue; // skip baris kosong
            }

            $results['total']++;

            // Normalisasi panjang row terhadap header
            if (count($row) < count($headers)) {
                $results['failed']++;
                $results['errors'][] = "Baris {$line}: jumlah kolom kurang dari header";
                continue;
            }

            $assoc = [];
            foreach ($headers as $idx => $name) {
                $assoc[$name] = isset($row[$idx]) ? trim($row[$idx]) : null;
            }

            try {
                $templateName = $assoc['template_name'] ?? '';
                $template = DB::table('certificate_templates')
                    ->where('name', $templateName)
                    ->where('is_active', 1)
                    ->first();

                if (! $template) {
                    $results['failed']++;
                    $results['errors'][] = "Baris {$line}: template dengan nama '{$templateName}' tidak ditemukan atau tidak aktif";
                    continue;
                }

                $category = $assoc['category'] ?? '';
                $certificateTitle = $assoc['certificate_title'] ?? '';

                if (trim($category) === 'Sertifikat PKL/Magang'
                    && ! empty($assoc['internship_start_date'])
                    && ! empty($assoc['internship_end_date'])) {
                    $certificateTitle = $this->buildInternshipPeriodTitle(
                        $assoc['internship_start_date'],
                        $assoc['internship_end_date']
                    );
                }

                // Default: gunakan Unit Kompetensi global jika diset di form import
                $unitKompetensiPath = $globalUnitKompetensiPath;
                $unitName = $assoc['competency_unit_name'] ?? '';

                // Jika kolom competency_unit_name di CSV diisi, override unit global per-baris
                if ($unitName !== '') {
                    $unit = DB::table('competency_unit_templates')
                        ->where('name', $unitName)
                        ->where('is_active', 1)
                        ->first();

                    if (! $unit) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$line}: unit kompetensi dengan nama '{$unitName}' tidak ditemukan atau tidak aktif";
                        continue;
                    }

                    if (empty($unit->file_path)) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$line}: unit kompetensi '{$unitName}' tidak memiliki file_path yang valid";
                        continue;
                    }

                    $unitKompetensiPath = $unit->file_path;
                }

                $payload = [
                    'company_name'     => $assoc['company_name'] ?? '',
                    'template_id'      => $template->id,
                    'name'             => $assoc['name'] ?? '',
                    'place_of_birth'   => $assoc['place_of_birth'] ?? '',
                    'date_of_birth'    => $this->normalizeDateInput($assoc['date_of_birth'] ?? null),
                    'category'         => $category,
                    'competency_field' => $assoc['competency_field'] ?? null,
                    'place_of_issue'   => $assoc['place_of_issue'] ?? '',
                    'issued_date'      => $this->normalizeDateInput($assoc['issued_date'] ?? null),
                    'certificate_title'=> $certificateTitle,
                    'internship_start_date' => $this->normalizeDateInput($assoc['internship_start_date'] ?? null),
                    'internship_end_date'   => $this->normalizeDateInput($assoc['internship_end_date'] ?? null),
                    'unit_kompetensi'  => $unitKompetensiPath,
                ];

                $apiRequest = Request::create('/api/certificates', 'POST', $payload);
                $response = $apiController->store($apiRequest);

                if (method_exists($response, 'getStatusCode') && $response->getStatusCode() >= 400) {
                    $json = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
                    $message = $json['message'] ?? $json['error'] ?? 'Gagal menambahkan sertifikat';

                    $results['failed']++;
                    $results['errors'][] = "Baris {$line}: {$message}";
                    continue;
                }

                $results['success']++;
            } catch (\Throwable $e) {
                Log::error('Admin import certificate error', [
                    'line'  => $line,
                    'error' => $e->getMessage(),
                ]);

                $results['failed']++;
                $results['errors'][] = "Baris {$line}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return back()->with('import_result', $results);
    }

    protected function normalizeDateInput(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Coba format lokal umum: d/m/Y atau d-m-Y
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        // Jika gagal diparse, kembalikan apa adanya agar validator yang menangkap
        return $value;
    }

    public function create()
    {
        $categories = DB::table('categories')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $templates = DB::table('certificate_templates')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get();

        $variants = DB::table('certificate_template_variants')
            ->where('is_active', 1)
            ->orderBy('variant_name', 'asc')
            ->get();

        $competencyUnits = DB::table('competency_unit_templates')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.certificates.form', [
            'mode' => 'create',
            'certificate' => null,
            'categories' => $categories,
            'templates' => $templates,
            'variants' => $variants,
            'competencyUnits' => $competencyUnits,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'     => ['required', 'string', 'max:255'],
            'template_id'      => ['required', 'integer', 'exists:certificate_templates,id'],
            'variant_id'       => ['nullable', 'integer', 'exists:certificate_template_variants,id'],
            'name'             => ['required', 'string', 'max:255'],
            'place_of_birth'   => ['required', 'string', 'max:255'],
            'date_of_birth'    => ['required', 'date'],
            'category'         => ['required', 'string', 'max:255'],
            'competency_field' => ['nullable', 'string', 'max:255'],
            'place_of_issue'   => ['required', 'string', 'max:255'],
            'issued_date'      => ['required', 'date'],
            // Untuk kategori selain Upskilling Reskilling, judul sertifikat tetap wajib.
            // Untuk "Sertifikat Upskilling Reskilling" kolom ini boleh kosong.
            'certificate_title'=> ['required_unless:category,Sertifikat Upskilling Reskilling', 'nullable', 'string', 'max:255'],
            'internship_start_date' => ['nullable', 'date'],
            'internship_end_date'   => ['nullable', 'date'],
            'competency_unit_template_id' => ['nullable', 'integer', 'exists:competency_unit_templates,id'],
        ]);

        try {
            $categoryName = $data['category'] ?? '';

            if (trim($categoryName) === 'Sertifikat PKL/Magang' && !empty($data['internship_start_date']) && !empty($data['internship_end_date'])) {
                $data['certificate_title'] = $this->buildInternshipPeriodTitle(
                    $data['internship_start_date'],
                    $data['internship_end_date']
                );
            }

            // Untuk kategori Upskilling Reskilling, pastikan certificate_title tidak null
            if (trim($categoryName) === 'Sertifikat Upskilling Reskilling' && empty($data['certificate_title'])) {
                $data['certificate_title'] = '';
            }

            $apiController = new CertificateController();

            $unitKompetensiPath = null;
            if (!empty($data['competency_unit_template_id'])) {
                $unitTpl = DB::table('competency_unit_templates')
                    ->where('id', $data['competency_unit_template_id'])
                    ->where('is_active', 1)
                    ->first();

                if ($unitTpl && !empty($unitTpl->file_path)) {
                    $unitKompetensiPath = $unitTpl->file_path;
                }
            }

            $payload = [
                'name'             => $data['name'],
                'place_of_birth'   => $data['place_of_birth'],
                'date_of_birth'    => $data['date_of_birth'],
                'certificate_title'=> $data['certificate_title'],
                'category'         => $data['category'],
                'competency_field' => $data['competency_field'] ?? null,
                'issued_date'      => $data['issued_date'],
                'place_of_issue'   => $data['place_of_issue'],
                'pdf_url'          => null,
                'company_name'     => $data['company_name'],
                'template_id'      => $data['template_id'],
                'variant_id'       => $data['variant_id'] ?? null,
                'unit_kompetensi'  => $unitKompetensiPath,
            ];

            // Reuse the same logic as API to insert and generate PDF
            $apiRequest = Request::create('/api/certificates', 'POST', $payload);
            $response = $apiController->store($apiRequest);

            if (method_exists($response, 'getStatusCode') && $response->getStatusCode() >= 400) {
                $json = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
                $message = $json['message'] ?? $json['error'] ?? 'Gagal menambahkan sertifikat';

                return back()
                    ->withErrors(['general' => $message])
                    ->withInput();
            }

            return redirect()->route('admin.certificates.index')->with('status', 'Sertifikat berhasil ditambahkan');
        } catch (\Throwable $e) {
            Log::error('Admin create certificate error', ['error' => $e->getMessage()]);

            return back()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan sertifikat'])
                ->withInput();
        }
    }

    public function edit($id)
    {
        $certificate = DB::table('certificates')->where('id', $id)->first();

        if (! $certificate) {
            abort(404);
        }

        // Ambil daftar kategori dan template seperti di halaman create
        $categories = DB::table('categories')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $templates = DB::table('certificate_templates')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.certificates.form', [
            'mode' => 'edit',
            'certificate' => $certificate,
            'categories' => $categories,
            'templates' => $templates,
            'variants' => DB::table('certificate_template_variants')->where('is_active', 1)->orderBy('variant_name','asc')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'certificate_title'=> ['required', 'string', 'max:255'],
            'company_name'     => ['required', 'string', 'max:255'],
            'category'         => ['nullable', 'string', 'max:255'],
            'competency_field' => ['nullable', 'string', 'max:255'],
            'place_of_birth'   => ['nullable', 'string', 'max:255'],
            'date_of_birth'    => ['nullable', 'date'],
            'issued_date'      => ['nullable', 'date'],
            'place_of_issue'   => ['nullable', 'string', 'max:255'],
            'internship_start_date' => ['nullable', 'date'],
            'internship_end_date'   => ['nullable', 'date'],
        ]);

        $categoryName = $data['category'] ?? '';

        if (trim($categoryName) === 'Sertifikat PKL/Magang' && !empty($data['internship_start_date']) && !empty($data['internship_end_date'])) {
            $data['certificate_title'] = $this->buildInternshipPeriodTitle(
                $data['internship_start_date'],
                $data['internship_end_date']
            );
        }

        try {
            $certificate = DB::table('certificates')->where('id', $id)->first();

            if (! $certificate) {
                abort(404);
            }

            if (!empty($certificate->generated_pdf_path)) {
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

            DB::table('certificates')->where('id', $id)->update([
                'name'               => $data['name'],
                'place_of_birth'     => $data['place_of_birth'] ?? null,
                'date_of_birth'      => $data['date_of_birth'] ?? null,
                'certificate_title'  => $data['certificate_title'],
                'category'           => $data['category'] ?? null,
                'competency_field'   => $data['competency_field'] ?? null,
                'issued_date'        => $data['issued_date'] ?? null,
                'place_of_issue'     => $data['place_of_issue'] ?? null,
                'company_name'       => $data['company_name'],
            ]);

            $updated = DB::table('certificates')->where('id', $id)->first();

            if ($updated && !empty($updated->template_id)) {
                $template = DB::table('certificate_templates')
                    ->where('id', $updated->template_id)
                    ->where('is_active', 1)
                    ->first();

                if ($template) {
                    $templateArray = (array) $template;
                    $certificateArray = (array) $updated;

                    $pdfService = new CertificatePdfService();
                    $relativePath = $pdfService->generate($templateArray, $certificateArray);

                    if ($relativePath) {
                        DB::table('certificates')
                            ->where('id', $updated->id)
                            ->update(['generated_pdf_path' => $relativePath]);
                    }
                }
            }

            return redirect()->route('admin.certificates.index')->with('status', 'Sertifikat berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Admin update certificate error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['general' => 'Terjadi kesalahan saat memperbarui sertifikat'])
                ->withInput();
        }
    }

    protected function buildInternshipPeriodTitle(string $startDate, string $endDate): string
    {
        try {
            $bulanIndo = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];

            $start = new \DateTime($startDate);
            $end   = new \DateTime($endDate);

            $startMonthName = $bulanIndo[(int) $start->format('n')] ?? $start->format('m');
            $endMonthName   = $bulanIndo[(int) $end->format('n')] ?? $end->format('m');
            $startYear      = (int) $start->format('Y');
            $endYear        = (int) $end->format('Y');

            if ($startYear === $endYear) {
                // Contoh: Januari - Maret 2024
                return $startMonthName . ' - ' . $endMonthName . ' ' . $startYear;
            }

            // Contoh: Desember 2024 - Januari 2025
            $startStr = $startMonthName . ' ' . $startYear;
            $endStr   = $endMonthName . ' ' . $endYear;

            return $startStr . ' - ' . $endStr;
        } catch (\Throwable $e) {
            return $startDate . ' - ' . $endDate;
        }
    }

    public function destroy($id)
    {
        try {
            $api = new CertificateController();
            $deleted = $api->deleteCertificateWithFile((int) $id);

            if ($deleted === 0) {
                return redirect()->route('admin.certificates.index')
                    ->withErrors(['general' => 'Sertifikat tidak ditemukan atau sudah dihapus.']);
            }

            return redirect()->route('admin.certificates.index')->with('status', 'Sertifikat berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('Admin destroy certificate error', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);

            return redirect()->route('admin.certificates.index')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghapus sertifikat']);
        }
    }

    public function destroyPage(Request $request)
    {
        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 10; // harus sama dengan paginate(10) di index()

        $query = DB::table('certificates')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        $search = $request->input('q');
        $categoryFilter = $request->input('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%");
            });
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        $ids = $query->forPage($page, $perPage)->pluck('id');

        if ($ids->isEmpty()) {
            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'page' => $page,
            ])->withErrors(['general' => 'Tidak ada sertifikat di halaman ini untuk dihapus.']);
        }

        try {
            $api = new CertificateController();
            $deletedTotal = 0;

            foreach ($ids as $cid) {
                $deletedTotal += $api->deleteCertificateWithFile((int) $cid);
            }

            if ($deletedTotal === 0) {
                return redirect()->route('admin.certificates.index', [
                    'q'    => $request->input('q'),
                    'page' => $page,
                ])->withErrors(['general' => 'Tidak ada sertifikat yang terhapus. Pastikan data masih ada dan coba lagi.']);
            }

            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'page' => $page,
            ])->with('status', $deletedTotal . ' sertifikat di halaman ini berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('Destroy page certificates error', [
                'error' => $e->getMessage(),
                'page'  => $page,
                'q'     => $request->input('q'),
            ]);

            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'page' => $page,
            ])->withErrors(['general' => 'Terjadi kesalahan saat menghapus sertifikat di halaman ini']);
        }
    }

    public function downloadPage(Request $request)
    {
        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 10; // harus sama dengan paginate(10) di index()

        $query = DB::table('certificates')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($categoryFilter = $request->input('category')) {
            $query->where('category', $categoryFilter);
        }

        $certificates = $query->forPage($page, $perPage)->get();

        if ($certificates->isEmpty()) {
            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'category' => $request->input('category'),
                'page' => $page,
            ])->withErrors(['general' => 'Tidak ada sertifikat di halaman ini untuk didownload.']);
        }

        try {
            $zip = new \ZipArchive();

            $zipFileName = 'sertifikat-halaman-' . $page . '-' . time() . '.zip';
            $zipPath = storage_path('app/' . $zipFileName);

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return redirect()->route('admin.certificates.index', [
                    'q'    => $request->input('q'),
                    'category' => $request->input('category'),
                    'page' => $page,
                ])->withErrors(['general' => 'Gagal membuat file ZIP untuk sertifikat.']);
            }

            $addedFiles = 0;
            $zipNames = [];

            foreach ($certificates as $certificate) {
                if (empty($certificate->generated_pdf_path)) {
                    continue;
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

                $fullPath = null;
                foreach ($candidates as $path) {
                    if ($path && file_exists($path)) {
                        $fullPath = $path;
                        break;
                    }
                }

                if (! $fullPath) {
                    continue;
                }

                if (! is_file($fullPath) || ! is_readable($fullPath) || filesize($fullPath) < 10) {
                    continue;
                }

                $fh = @fopen($fullPath, 'rb');
                if ($fh === false) {
                    continue;
                }

                $header = @fread($fh, 4);
                @fclose($fh);

                if ($header !== '%PDF') {
                    continue;
                }

                // Samakan format nama file dengan download manual:
                // nomor-surat-sertifikat-nama.pdf
                $safeName = preg_replace('/[^a-zA-Z0-9\s]/', '', $certificate->name ?? 'sertifikat');
                $safeName = strtolower(preg_replace('/\s+/', '-', trim($safeName)) ?: 'sertifikat');

                $numberPart = $certificate->certificate_number ?? 'no-number';
                $numberPart = explode('/', (string) $numberPart)[0] ?? $numberPart;
                $safeNumber = preg_replace('/[^a-zA-Z0-9]/', '-', $numberPart);

                $fileNameInZip = $safeNumber . '-sertifikat-' . $safeName . '.pdf';

                if (isset($zipNames[$fileNameInZip])) {
                    $fileNameInZip = $safeNumber . '-sertifikat-' . $safeName . '-' . ((int) ($certificate->id ?? 0)) . '.pdf';
                }

                $zipNames[$fileNameInZip] = true;

                $zip->addFile($fullPath, $fileNameInZip);
                $addedFiles++;
            }

            if ($addedFiles === 0) {
                $zip->close();
                if (file_exists($zipPath)) {
                    @unlink($zipPath);
                }

                return redirect()->route('admin.certificates.index', [
                    'q'    => $request->input('q'),
                    'category' => $request->input('category'),
                    'page' => $page,
                ])->withErrors(['general' => 'Tidak ada file sertifikat yang tersedia untuk didownload di halaman ini.']);
            }

            $zip->close();

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (isset($zip) && $zip instanceof \ZipArchive) {
                $zip->close();
            }

            if (isset($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }

            Log::error('Download page certificates error', [
                'error' => $e->getMessage(),
                'page'  => $page,
                'q'     => $request->input('q'),
            ]);

            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'category' => $request->input('category'),
                'page' => $page,
            ])->withErrors(['general' => 'Terjadi kesalahan saat menyiapkan download sertifikat di halaman ini']);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.certificates.index')
                ->withErrors(['general' => 'Pilih minimal satu sertifikat untuk dihapus']);
        }

        try {
            // Pastikan ID berupa integer untuk menghindari masalah tipe data
            $idInts = array_values(array_unique(array_map('intval', $ids)));

            if (count($idInts) === 0) {
                return redirect()->route('admin.certificates.index')
                    ->withErrors(['general' => 'ID sertifikat tidak valid']);
            }

            $api = new CertificateController();
            $deletedTotal = 0;

            foreach ($idInts as $cid) {
                $deletedTotal += $api->deleteCertificateWithFile((int) $cid);
            }

            if ($deletedTotal === 0) {
                return redirect()->route('admin.certificates.index')
                    ->withErrors(['general' => 'Tidak ada sertifikat yang terhapus. Pastikan data masih ada dan coba lagi.']);
            }

            return redirect()->route('admin.certificates.index')
                ->with('status', "{$deletedTotal} sertifikat terpilih berhasil dihapus");
        } catch (\Throwable $e) {
            Log::error('Bulk destroy certificates error', [
                'error' => $e->getMessage(),
                'ids'   => $ids,
            ]);

            return redirect()->route('admin.certificates.index')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghapus sertifikat terpilih']);
        }
    }

    public function trash(Request $request)
    {
        $baseQuery = DB::table('certificates')
            ->whereNotNull('deleted_at');

        // Kumpulan kategori untuk dropdown filter
        $categories = (clone $baseQuery)
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $query = clone $baseQuery;

        $search   = $request->get('q');
        $category = $request->get('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        $certificates = $query
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.certificates.trash', [
            'certificates' => $certificates,
            'search'       => $search,
            'category'     => $category,
            'categories'   => $categories,
        ]);
    }

    public function restore($id)
    {
        try {
            $certificate = DB::table('certificates')->where('id', $id)->first();

            if (! $certificate || $certificate->deleted_at === null) {
                return redirect()->route('admin.certificates.trash')
                    ->withErrors(['general' => 'Sertifikat tidak ditemukan di trash.']);
            }

            $newGeneratedPath = null;

            if (! empty($certificate->trashed_pdf_path)) {
                $relativePath = ltrim($certificate->trashed_pdf_path, '/');

                $candidates = [];
                $candidates[] = base_path($relativePath);
                $candidates[] = public_path($relativePath);

                $sourcePath = null;
                foreach ($candidates as $path) {
                    if ($path && file_exists($path)) {
                        $sourcePath = $path;
                        break;
                    }
                }

                if ($sourcePath) {
                    $activeDir = base_path('uploads/certificates');
                    if (! is_dir($activeDir)) {
                        @mkdir($activeDir, 0775, true);
                    }

                    $originalName = basename($sourcePath);
                    $newName = ($certificate->id ?? 'cert') . '-' . time() . '-' . $originalName;
                    $targetPath = $activeDir . DIRECTORY_SEPARATOR . $newName;

                    if (@rename($sourcePath, $targetPath)) {
                        $newGeneratedPath = '/uploads/certificates/' . $newName;
                    }
                }
            }

            DB::table('certificates')
                ->where('id', $id)
                ->update([
                    'deleted_at'         => null,
                    'trashed_pdf_path'   => null,
                    'generated_pdf_path' => $newGeneratedPath,
                ]);

            return redirect()->route('admin.certificates.trash')
                ->with('status', 'Sertifikat berhasil direstore');
        } catch (\Throwable $e) {
            Log::error('Admin restore certificate error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.certificates.trash')
                ->withErrors(['general' => 'Terjadi kesalahan saat merestore sertifikat']);
        }
    }

    public function forceDelete($id)
    {
        try {
            $certificate = DB::table('certificates')->where('id', $id)->first();

            if (! $certificate) {
                return redirect()->route('admin.certificates.trash')
                    ->withErrors(['general' => 'Sertifikat tidak ditemukan.']);
            }

            // Hapus file di trash jika masih ada
            if (! empty($certificate->trashed_pdf_path)) {
                $relativePath = ltrim($certificate->trashed_pdf_path, '/');

                $candidates = [];
                $candidates[] = base_path($relativePath);
                $candidates[] = public_path($relativePath);

                foreach ($candidates as $path) {
                    if ($path && file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            DB::table('certificates')->where('id', $id)->delete();

            return redirect()->route('admin.certificates.trash')
                ->with('status', 'Sertifikat berhasil dihapus permanen');
        } catch (\Throwable $e) {
            Log::error('Admin force delete certificate error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.certificates.trash')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghapus permanen sertifikat']);
        }
    }

    public function forceDeleteBulk(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (! is_array($ids) || count($ids) === 0) {
                return redirect()->route('admin.certificates.trash')
                    ->withErrors(['general' => 'Tidak ada sertifikat yang dipilih.']);
            }

            $ids = array_unique(array_map('intval', $ids));

            foreach ($ids as $id) {
                if (! $id) {
                    continue;
                }

                $certificate = DB::table('certificates')->where('id', $id)->first();

                if (! $certificate) {
                    continue;
                }

                if (! empty($certificate->trashed_pdf_path)) {
                    $relativePath = ltrim($certificate->trashed_pdf_path, '/');

                    $candidates = [];
                    $candidates[] = base_path($relativePath);
                    $candidates[] = public_path($relativePath);

                    foreach ($candidates as $path) {
                        if ($path && file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }

                DB::table('certificates')->where('id', $id)->delete();
            }

            return redirect()->route('admin.certificates.trash')
                ->with('status', 'Beberapa sertifikat berhasil dihapus permanen');
        } catch (\Throwable $e) {
            Log::error('Admin force delete bulk certificates error', [
                'ids'   => $request->input('ids', []),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.certificates.trash')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghapus permanen sertifikat terpilih']);
        }
    }
}
