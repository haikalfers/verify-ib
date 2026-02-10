<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminCompetencyUnitController extends Controller
{
    public function index()
    {
        $units = DB::table('competency_unit_templates')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.competency-units.index', compact('units'));
    }

    public function create()
    {
        return view('admin.competency-units.form', [
            'mode' => 'create',
            'unit' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['nullable', 'string', 'max:255'],
            'file'        => ['required', 'file', 'mimes:pdf'],
        ]);

        try {
            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return back()->withErrors(['file' => 'File upload tidak valid'])->withInput();
            }

            $uploadDir = base_path('uploads/competency-units');
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0775, true);
            }

            $safeName = preg_replace('/[^a-zA-Z0-9\s]/', '', $request->input('name'));
            $safeName = strtolower(preg_replace('/\s+/', '-', trim($safeName)) ?: 'unit-kompetensi');
            $timestamp = time();
            $filename = sprintf('unit-kompetensi-%s-%s.pdf', $safeName, $timestamp);

            $file->move($uploadDir, $filename);

            DB::table('competency_unit_templates')->insert([
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'category'    => $request->input('category'),
                'file_path'   => 'uploads/competency-units/' . $filename,
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return redirect()->route('admin.competency-units.index')
                ->with('status', 'Unit kompetensi berhasil ditambahkan');
        } catch (\Throwable $e) {
            Log::error('Admin competency unit store error', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat menyimpan unit kompetensi'])->withInput();
        }
    }

    public function edit($id)
    {
        $unit = DB::table('competency_unit_templates')->where('id', $id)->first();
        if (!$unit) {
            abort(404);
        }

        return view('admin.competency-units.form', [
            'mode' => 'edit',
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, $id)
    {
        $unit = DB::table('competency_unit_templates')->where('id', $id)->first();
        if (!$unit) {
            abort(404);
        }

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
            'file'        => ['nullable', 'file', 'mimes:pdf'],
        ]);

        try {
            $data = [
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'category'    => $request->input('category'),
                'is_active'   => $request->boolean('is_active', true) ? 1 : 0,
                'updated_at'  => now(),
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                if (!$file->isValid()) {
                    return back()->withErrors(['file' => 'File upload tidak valid'])->withInput();
                }

                $uploadDir = base_path('uploads/competency-units');
                if (!File::exists($uploadDir)) {
                    File::makeDirectory($uploadDir, 0775, true);
                }

                $safeName = preg_replace('/[^a-zA-Z0-9\s]/', '', $request->input('name'));
                $safeName = strtolower(preg_replace('/\s+/', '-', trim($safeName)) ?: 'unit-kompetensi');
                $timestamp = time();
                $filename = sprintf('unit-kompetensi-%s-%s.pdf', $safeName, $timestamp);

                $file->move($uploadDir, $filename);
                $data['file_path'] = 'uploads/competency-units/' . $filename;
            }

            DB::table('competency_unit_templates')->where('id', $id)->update($data);

            return redirect()->route('admin.competency-units.index')
                ->with('status', 'Unit kompetensi berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Admin competency unit update error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat memperbarui unit kompetensi'])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('competency_unit_templates')->where('id', $id)->delete();

            return redirect()->route('admin.competency-units.index')
                ->with('status', 'Unit kompetensi berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('Admin competency unit destroy error', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat menghapus unit kompetensi']);
        }
    }
}
