<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicVerificationController extends Controller
{
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

        $certificate = DB::table('certificates')
            ->where('verify_code', trim($data['verify_code']))
            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$data['name']])
            ->whereRaw('DATE(date_of_birth) = DATE(?)', [$data['date_of_birth']])
            ->first();

        return view('public.verify', [
            'result'      => $certificate ? 'valid' : 'invalid',
            'certificate' => $certificate,
            'input'       => $data,
        ]);
    }
}
