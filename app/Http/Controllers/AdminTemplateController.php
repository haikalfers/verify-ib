<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminTemplateController extends Controller
{
    public function index()
    {
        $templates = DB::table('certificate_templates')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.templates.form', [
            'mode' => 'create',
            'template' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['nullable', 'string', 'max:255'],
            'template_file' => ['required', 'file'],
        ]);

        try {
            $api = new TemplateController();
            $apiResponse = $api->store($request);

            if (method_exists($apiResponse, 'getStatusCode') && $apiResponse->getStatusCode() >= 400) {
                $json = method_exists($apiResponse, 'getData') ? (array) $apiResponse->getData(true) : [];
                $message = $json['message'] ?? $json['error'] ?? 'Gagal menyimpan template';

                return back()->withErrors(['general' => $message])->withInput();
            }

            return redirect()->route('admin.templates.index')->with('status', 'Template berhasil ditambahkan');
        } catch (\Throwable $e) {
            Log::error('Admin template store error', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat menyimpan template'])->withInput();
        }
    }

    public function edit($id)
    {
        $template = DB::table('certificate_templates')->where('id', $id)->first();
        if (! $template) {
            abort(404);
        }

        return view('admin.templates.form', [
            'mode' => 'edit',
            'template' => $template,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['nullable', 'string', 'max:255'],
            'template_file' => ['nullable', 'file'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        try {
            $api = new TemplateController();
            $apiResponse = $api->update($request, $id);

            if (method_exists($apiResponse, 'getStatusCode') && $apiResponse->getStatusCode() >= 400) {
                $json = method_exists($apiResponse, 'getData') ? (array) $apiResponse->getData(true) : [];
                $message = $json['message'] ?? $json['error'] ?? 'Gagal memperbarui template';

                return back()->withErrors(['general' => $message])->withInput();
            }

            return redirect()->route('admin.templates.index')->with('status', 'Template berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Admin template update error', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat memperbarui template'])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $api = new TemplateController();
            $apiResponse = $api->destroy($id);

            if (method_exists($apiResponse, 'getStatusCode') && $apiResponse->getStatusCode() >= 400) {
                $json = method_exists($apiResponse, 'getData') ? (array) $apiResponse->getData(true) : [];
                $message = $json['message'] ?? $json['error'] ?? 'Gagal menghapus template';

                return back()->withErrors(['general' => $message]);
            }

            return redirect()->route('admin.templates.index')->with('status', 'Template berhasil dihapus');
        } catch (\Throwable $e) {
            Log::error('Admin template destroy error', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat menghapus template']);
        }
    }

    public function toggle($id)
    {
        try {
            $api = new TemplateController();
            $apiResponse = $api->toggle($id);

            if (method_exists($apiResponse, 'getStatusCode') && $apiResponse->getStatusCode() >= 400) {
                $json = method_exists($apiResponse, 'getData') ? (array) $apiResponse->getData(true) : [];
                $message = $json['message'] ?? $json['error'] ?? 'Gagal mengubah status template';

                return back()->withErrors(['general' => $message]);
            }

            return redirect()->route('admin.templates.index')->with('status', 'Status template berhasil diubah');
        } catch (\Throwable $e) {
            Log::error('Admin template toggle error', ['error' => $e->getMessage()]);

            return back()->withErrors(['general' => 'Terjadi kesalahan saat mengubah status template']);
        }
    }
}
