<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicVerificationController extends Controller
{
    protected function normalizeNameForSearch(?string $name): string
    {
        $name = preg_replace('/\s+/', ' ', (string) $name);
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $base = $name;
        if (str_contains($base, ',')) {
            $parts = explode(',', $base);
            $base = trim((string) ($parts[0] ?? ''));
        }

        $baseLower = strtolower($base);
        $baseLower = preg_replace('/[^a-z\s\-\-\]/u', ' ', $baseLower);
        $baseLower = preg_replace('/\s+/', ' ', $baseLower);
        $baseLower = trim($baseLower);

        $prefixes = ['dr', 'drg', 'prof', 'ir', 'h', 'hj'];
        foreach ($prefixes as $p) {
            if (str_starts_with($baseLower, $p . ' ')) {
                $baseLower = trim(substr($baseLower, strlen($p) + 1));
                break;
            }
        }

        return $baseLower;
    }

    public function index()
    {
        return view('public.landing');
    }

    public function form()
    {
        return view('public.verify', [
            'result'      => null,
            'certificate' => null,
            'input'       => [],
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'verify_code'   => ['required', 'string'],
            'name'          => ['required', 'string'],
            'date_of_birth' => ['required', 'date'],
        ]);

        $nameSearch = $this->normalizeNameForSearch($data['name']);

        $certificate = DB::table('certificates')
            ->whereNull('deleted_at')
            ->where('verify_code', trim($data['verify_code']))
            ->whereRaw(
                "LOWER(TRIM(COALESCE(name_search, TRIM(SUBSTRING_INDEX(name, ',', 1))))) = LOWER(TRIM(?))",
                [$nameSearch]
            )
            ->whereRaw('DATE(date_of_birth) = DATE(?)', [$data['date_of_birth']])
            ->first();

        return view('public.verify', [
            'result'      => $certificate ? 'valid' : 'invalid',
            'certificate' => $certificate,
            'input'       => $data,
        ]);
    }
}
