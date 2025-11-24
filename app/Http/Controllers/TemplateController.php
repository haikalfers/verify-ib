<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class TemplateController extends Controller
{
    /**
     * GET /api/templates - list all templates.
     */
    public function index()
    {
        try {
            $rows = DB::table('certificate_templates')
                ->orderByDesc('created_at')
                ->get();

            return response()->json($rows);
        } catch (\Throwable $e) {
            Log::error('Templates index error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/templates/active - list active templates.
     */
    public function active()
    {
        try {
            $rows = DB::table('certificate_templates')
                ->where('is_active', 1)
                ->orderBy('name', 'asc')
                ->get();

            return response()->json($rows);
        } catch (\Throwable $e) {
            Log::error('Templates active error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/templates/{id} - get one template.
     */
    public function show($id)
    {
        try {
            $template = DB::table('certificate_templates')->where('id', $id)->first();

            if (! $template) {
                return response()->json([
                    'message' => 'Template tidak ditemukan',
                ], 404);
            }

            return response()->json($template);
        } catch (\Throwable $e) {
            Log::error('Templates show error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/templates - create template with file upload.
     */
    public function store(Request $request)
    {
        try {
            if (! $request->hasFile('template_file')) {
                return response()->json([
                    'message' => 'File template wajib diupload',
                ], 400);
            }

            $file = $request->file('template_file');

            if (! $file->isValid()) {
                return response()->json([
                    'message' => 'File upload tidak valid',
                ], 400);
            }

            $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, $allowed, true)) {
                return response()->json([
                    'message' => 'Only PDF and image files (PNG, JPG, JPEG) are allowed',
                ], 400);
            }

            $name = (string) $request->input('name', '');
            if (trim($name) === '') {
                return response()->json([
                    'message' => 'Nama template wajib diisi',
                ], 400);
            }

            $uploadDir = base_path('uploads/templates');
            if (! File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0775, true);
            }

            $uniqueSuffix = time() . '-' . mt_rand(1, 1_000_000_000);
            $filename = 'template-' . $uniqueSuffix . '.' . $ext;
            $file->move($uploadDir, $filename);

            $fileType = $ext === 'pdf' ? 'pdf' : 'image';
            $filePath = '/uploads/templates/' . $filename; // relative path like Node

            $coordinatesRaw = $request->input('coordinates');
            $coordsJson = null;
            if ($coordinatesRaw) {
                if (is_string($coordinatesRaw)) {
                    $coordsJson = json_decode($coordinatesRaw, true);
                } else {
                    $coordsJson = $coordinatesRaw;
                }

                if (! $coordsJson && $coordinatesRaw) {
                    // cleanup file if coordinates invalid
                    File::delete($uploadDir . DIRECTORY_SEPARATOR . $filename);

                    return response()->json([
                        'message' => 'Format coordinates tidak valid',
                    ], 400);
                }
            }

            $id = DB::table('certificate_templates')->insertGetId([
                'name'        => trim($name),
                'description' => $request->input('description') ?: null,
                'file_path'   => $filePath,
                'file_type'   => $fileType,
                'category'    => $request->input('category') ?: null,
                'coordinates' => $coordsJson ? json_encode($coordsJson) : null,
            ]);

            $template = DB::table('certificate_templates')->where('id', $id)->first();

            return response()->json([
                'message'  => 'Template berhasil diupload',
                'template' => $template,
            ]);
        } catch (\Throwable $e) {
            Log::error('Templates store error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/templates/{id} - update template, optionally replacing file.
     */
    public function update(Request $request, $id)
    {
        try {
            $existing = DB::table('certificate_templates')->where('id', $id)->first();
            if (! $existing) {
                return response()->json([
                    'message' => 'Template tidak ditemukan',
                ], 404);
            }

            $filePath = $existing->file_path;
            $fileType = $existing->file_type;

            $uploadDir = public_path('uploads/templates');
            if ($request->hasFile('template_file')) {
                $file = $request->file('template_file');
                if (! $file->isValid()) {
                    return response()->json([
                        'message' => 'File upload tidak valid',
                    ], 400);
                }

                $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, $allowed, true)) {
                    return response()->json([
                        'message' => 'Only PDF and image files (PNG, JPG, JPEG) are allowed',
                    ], 400);
                }

                if (! File::exists($uploadDir)) {
                    File::makeDirectory($uploadDir, 0775, true);
                }

                $uniqueSuffix = time() . '-' . mt_rand(1, 1_000_000_000);
                $filename = 'template-' . $uniqueSuffix . '.' . $ext;
                $file->move($uploadDir, $filename);

                $fileType = $ext === 'pdf' ? 'pdf' : 'image';
                $newPath = '/uploads/templates/' . $filename;

                // delete old file
                if ($existing->file_path) {
                    $oldPath = base_path(ltrim($existing->file_path, '/'));
                    File::delete($oldPath);
                }

                $filePath = $newPath;
            }

            // coordinates
            $coordsJson = $existing->coordinates;
            if ($request->has('coordinates')) {
                $coordinatesRaw = $request->input('coordinates');
                if ($coordinatesRaw) {
                    $decoded = is_string($coordinatesRaw)
                        ? json_decode($coordinatesRaw, true)
                        : $coordinatesRaw;
                    if (! $decoded && $coordinatesRaw) {
                        return response()->json([
                            'message' => 'Format coordinates tidak valid',
                        ], 400);
                    }
                    $coordsJson = $decoded ? json_encode($decoded) : null;
                } else {
                    $coordsJson = null;
                }
            }

            $name = $request->input('name');
            $description = $request->input('description');
            $category = $request->input('category');
            $isActive = $request->input('is_active');

            DB::table('certificate_templates')
                ->where('id', $id)
                ->update([
                    'name'        => $name !== null ? trim($name) : $existing->name,
                    'description' => $description !== null ? $description : $existing->description,
                    'file_path'   => $filePath,
                    'file_type'   => $fileType,
                    'category'    => $category !== null ? $category : $existing->category,
                    'coordinates' => $coordsJson,
                    'is_active'   => $isActive !== null ? $isActive : $existing->is_active,
                ]);

            $template = DB::table('certificate_templates')->where('id', $id)->first();

            return response()->json([
                'message'  => 'Template berhasil diperbarui',
                'template' => $template,
            ]);
        } catch (\Throwable $e) {
            Log::error('Templates update error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/templates/{id} - delete template if unused.
     */
    public function destroy($id)
    {
        try {
            $existing = DB::table('certificate_templates')->where('id', $id)->first();
            if (! $existing) {
                return response()->json([
                    'message' => 'Template tidak ditemukan',
                ], 404);
            }

            $usage = DB::table('certificates')->where('template_id', $id)->count();
            if ($usage > 0) {
                return response()->json([
                    'message' => "Template tidak dapat dihapus karena sedang digunakan oleh {$usage} sertifikat",
                ], 400);
            }

            if ($existing->file_path) {
                $filePath = base_path(ltrim($existing->file_path, '/'));
                File::delete($filePath);
            }

            DB::table('certificate_templates')->where('id', $id)->delete();

            return response()->json([
                'message' => 'Template berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            Log::error('Templates destroy error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/templates/{id}/toggle - toggle active status.
     */
    public function toggle($id)
    {
        try {
            $existing = DB::table('certificate_templates')
                ->select('id', 'is_active')
                ->where('id', $id)
                ->first();

            if (! $existing) {
                return response()->json([
                    'message' => 'Template tidak ditemukan',
                ], 404);
            }

            $newStatus = $existing->is_active ? 0 : 1;

            DB::table('certificate_templates')
                ->where('id', $id)
                ->update(['is_active' => $newStatus]);

            return response()->json([
                'message'   => 'Template ' . ($newStatus ? 'diaktifkan' : 'dinonaktifkan'),
                'is_active' => $newStatus,
            ]);
        } catch (\Throwable $e) {
            Log::error('Templates toggle error', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
