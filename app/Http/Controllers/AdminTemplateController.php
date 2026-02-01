<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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

    // ===== Variants Management =====
    public function variants($id)
    {
        $template = DB::table('certificate_templates')->where('id', $id)->first();
        if (!$template) abort(404);

        $variants = DB::table('certificate_template_variants')
            ->where('template_id', $id)
            ->orderByDesc('is_default')
            ->orderBy('variant_name', 'asc')
            ->get();

        return view('admin.templates.variants', [
            'template' => $template,
            'variants' => $variants,
        ]);
    }

    public function storeVariant(Request $request, $id)
    {
        $request->validate([
            'variant_name'  => ['required', 'string', 'max:255'],
            'template_file' => ['required', 'file'],
            'coordinates'   => ['nullable', 'string'],
            'is_default'    => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $template = DB::table('certificate_templates')->where('id', $id)->first();
        if (!$template) abort(404);

        $uploadDir = base_path('uploads/templates');
        $file = $request->file('template_file');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['template_file' => 'File upload tidak valid'])->withInput();
        }

        $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowed, true)) {
            return back()->withErrors(['template_file' => 'Only PDF and image files (PNG, JPG, JPEG) are allowed'])->withInput();
        }

        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0775, true);
        }

        $uniqueSuffix = time() . '-' . mt_rand(1, 1_000_000_000);
        $filename = 'template-variant-' . $uniqueSuffix . '.' . $ext;
        $file->move($uploadDir, $filename);

        $fileType = ($ext === 'pdf') ? 'pdf' : 'image';

        $coordsRaw = $request->input('coordinates');
        if ($coordsRaw) {
            try {
                $decoded = json_decode($coordsRaw, true, 512, JSON_THROW_ON_ERROR);
                $coordsRaw = json_encode($decoded);
            } catch (\Throwable $e) {
                return back()->withErrors(['coordinates' => 'Koordinat (JSON) tidak valid'])->withInput();
            }
        }

        $isDefault = (bool) $request->boolean('is_default', false);
        $isActive = (bool) $request->boolean('is_active', true);

        if ($isDefault) {
            DB::table('certificate_template_variants')->where('template_id', $id)->update(['is_default' => 0]);
        }

        DB::table('certificate_template_variants')->insert([
            'template_id' => $id,
            'variant_name' => $request->input('variant_name'),
            'is_default' => $isDefault ? 1 : 0,
            'is_active'  => $isActive ? 1 : 0,
            'file_path'  => 'uploads/templates/' . $filename,
            'file_type'  => $fileType,
            'coordinates'=> $coordsRaw ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.templates.variants', $id)->with('status', 'Varian berhasil ditambahkan');
    }

    public function toggleVariant($variantId)
    {
        $v = DB::table('certificate_template_variants')->where('id', $variantId)->first();
        if (!$v) abort(404);
        DB::table('certificate_template_variants')->where('id', $variantId)->update([
            'is_active' => ($v->is_active ?? 1) ? 0 : 1,
        ]);
        return back()->with('status', 'Status varian diperbarui');
    }

    public function setDefaultVariant($variantId)
    {
        $v = DB::table('certificate_template_variants')->where('id', $variantId)->first();
        if (!$v) abort(404);
        DB::table('certificate_template_variants')->where('template_id', $v->template_id)->update(['is_default' => 0]);
        DB::table('certificate_template_variants')->where('id', $variantId)->update(['is_default' => 1]);
        return back()->with('status', 'Varian default diubah');
    }

    public function editVariant($variantId)
    {
        $variant = DB::table('certificate_template_variants')->where('id', $variantId)->first();
        if (!$variant) abort(404);
        $template = DB::table('certificate_templates')->where('id', $variant->template_id)->first();
        return view('admin.templates.edit-variant', compact('template', 'variant'));
    }

    public function updateVariant(Request $request, $variantId)
    {
        $variant = DB::table('certificate_template_variants')->where('id', $variantId)->first();
        if (!$variant) abort(404);

        $request->validate([
            'variant_name'  => ['required', 'string', 'max:255'],
            'template_file' => ['nullable', 'file'],
            'coordinates'   => ['nullable', 'string'],
            'is_default'    => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data = [
            'variant_name' => $request->input('variant_name'),
            'is_active'    => $request->boolean('is_active', true) ? 1 : 0,
            'updated_at'   => now(),
        ];

        $coordsRaw = $request->input('coordinates');
        if ($coordsRaw !== null) {
            if ($coordsRaw === '') {
                $data['coordinates'] = null;
            } else {
                try {
                    $decoded = json_decode($coordsRaw, true, 512, JSON_THROW_ON_ERROR);
                    $data['coordinates'] = json_encode($decoded);
                } catch (\Throwable $e) {
                    return back()->withErrors(['coordinates' => 'Koordinat (JSON) tidak valid'])->withInput();
                }
            }
        }

        if ($request->hasFile('template_file')) {
            $file = $request->file('template_file');
            if (!$file->isValid()) {
                return back()->withErrors(['template_file' => 'File upload tidak valid'])->withInput();
            }
            $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowed, true)) {
                return back()->withErrors(['template_file' => 'Only PDF and image files (PNG, JPG, JPEG) are allowed'])->withInput();
            }
            $uploadDir = base_path('uploads/templates');
            if (!File::exists($uploadDir)) File::makeDirectory($uploadDir, 0775, true);
            $filename = 'template-variant-' . (time() . '-' . mt_rand(1, 1_000_000_000)) . '.' . $ext;
            $file->move($uploadDir, $filename);
            $data['file_path'] = 'uploads/templates/' . $filename;
            $data['file_type'] = ($ext === 'pdf') ? 'pdf' : 'image';
        }

        if ($request->boolean('is_default', false)) {
            DB::table('certificate_template_variants')->where('template_id', $variant->template_id)->update(['is_default' => 0]);
            $data['is_default'] = 1;
        }

        DB::table('certificate_template_variants')->where('id', $variantId)->update($data);
        return redirect()->route('admin.templates.variants', $variant->template_id)->with('status', 'Varian berhasil diperbarui');
    }

    public function destroyVariant($variantId)
    {
        $variant = DB::table('certificate_template_variants')->where('id', $variantId)->first();
        if (!$variant) abort(404);
        DB::table('certificate_template_variants')->where('id', $variantId)->delete();
        return back()->with('status', 'Varian dihapus');
    }
}
