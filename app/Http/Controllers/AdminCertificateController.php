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
}
