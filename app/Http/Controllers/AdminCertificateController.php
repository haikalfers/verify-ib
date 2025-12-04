<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('certificates')->orderByDesc('id');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%");
            });
        }

        $certificates = $query->paginate(10)->withQueryString();

        return view('admin.certificates.index', compact('certificates', 'search'));
    }

    public function importForm()
    {
        return view('admin.certificates.import');
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

        return view('admin.certificates.form', [
            'mode' => 'create',
            'certificate' => null,
            'categories' => $categories,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'     => ['required', 'string', 'max:255'],
            'template_id'      => ['required', 'integer', 'exists:certificate_templates,id'],
            'name'             => ['required', 'string', 'max:255'],
            'place_of_birth'   => ['required', 'string', 'max:255'],
            'date_of_birth'    => ['required', 'date'],
            'category'         => ['required', 'string', 'max:255'],
            'competency_field' => ['nullable', 'string', 'max:255'],
            'place_of_issue'   => ['required', 'string', 'max:255'],
            'issued_date'      => ['required', 'date'],
            'certificate_title'=> ['required', 'string', 'max:255'],
            'internship_start_date' => ['nullable', 'date'],
            'internship_end_date'   => ['nullable', 'date'],
        ]);

        try {
            $categoryName = $data['category'] ?? '';

            if (trim($categoryName) === 'Sertifikat PKL/Magang' && !empty($data['internship_start_date']) && !empty($data['internship_end_date'])) {
                $data['certificate_title'] = $this->buildInternshipPeriodTitle(
                    $data['internship_start_date'],
                    $data['internship_end_date']
                );
            }

            $apiController = new CertificateController();

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

        return redirect()->route('admin.certificates.index')->with('status', 'Sertifikat berhasil diperbarui');
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

            $startStr = sprintf(
                '%02d %s %04d',
                (int) $start->format('d'),
                $bulanIndo[(int) $start->format('n')] ?? $start->format('m'),
                (int) $start->format('Y')
            );

            $endStr = sprintf(
                '%02d %s %04d',
                (int) $end->format('d'),
                $bulanIndo[(int) $end->format('n')] ?? $end->format('m'),
                (int) $end->format('Y')
            );

            return $startStr . ' - ' . $endStr;
        } catch (\Throwable $e) {
            return $startDate . ' - ' . $endDate;
        }
    }

    public function destroy($id)
    {
        DB::table('certificates')->where('id', $id)->delete();

        return redirect()->route('admin.certificates.index')->with('status', 'Sertifikat berhasil dihapus');
    }

    public function destroyPage(Request $request)
    {
        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 10; // harus sama dengan paginate(10) di index()

        $query = DB::table('certificates')->orderByDesc('id');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('certificate_title', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhere('verify_code', 'like', "%{$search}%");
            });
        }

        $ids = $query->forPage($page, $perPage)->pluck('id');

        if ($ids->isEmpty()) {
            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'page' => $page,
            ])->withErrors(['general' => 'Tidak ada sertifikat di halaman ini untuk dihapus.']);
        }

        try {
            $deleted = DB::table('certificates')->whereIn('id', $ids)->delete();

            if ($deleted === 0) {
                return redirect()->route('admin.certificates.index', [
                    'q'    => $request->input('q'),
                    'page' => $page,
                ])->withErrors(['general' => 'Tidak ada sertifikat yang terhapus. Pastikan data masih ada dan coba lagi.']);
            }

            return redirect()->route('admin.certificates.index', [
                'q'    => $request->input('q'),
                'page' => $page,
            ])->with('status', $deleted . ' sertifikat di halaman ini berhasil dihapus');
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

            $deleted = DB::table('certificates')->whereIn('id', $idInts)->delete();

            if ($deleted === 0) {
                return redirect()->route('admin.certificates.index')
                    ->withErrors(['general' => 'Tidak ada sertifikat yang terhapus. Pastikan data masih ada dan coba lagi.']);
            }

            return redirect()->route('admin.certificates.index')
                ->with('status', "{$deleted} sertifikat terpilih berhasil dihapus");
        } catch (\Throwable $e) {
            Log::error('Bulk destroy certificates error', [
                'error' => $e->getMessage(),
                'ids'   => $ids,
            ]);

            return redirect()->route('admin.certificates.index')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghapus sertifikat terpilih']);
        }
    }
}
