@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6 max-w-3xl">
    <div>
      <h1 class="text-lg md:text-2xl font-bold text-gray-900">
        {{ $mode === 'create' ? 'Tambah Template Sertifikat' : 'Edit Template Sertifikat' }}
      </h1>
      <p class="text-xs md:text-sm text-gray-600">Upload atau perbarui template sertifikat yang akan digunakan untuk generate PDF.</p>
    </div>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.templates.store') : route('admin.templates.update', $template->id) }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-4">
      @csrf

      @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
          <p class="font-semibold mb-1">Periksa kembali data template:</p>
          <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Template *</label>
        <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
        <p class="mt-1 text-[11px] text-gray-500">Nama ini akan muncul di pilihan template saat membuat sertifikat.</p>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
        <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('description', $template->description ?? '') }}</textarea>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Kategori</label>
          <input type="text" name="category" value="{{ old('category', $template->category ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Kompetensi, Pelatihan, Seminar">
        </div>
        @if ($mode === 'edit')
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Status Aktif</label>
            <select name="is_active" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
              <option value="1" @selected(($template->is_active ?? 0) == 1)>Aktif</option>
              <option value="0" @selected(($template->is_active ?? 0) == 0)>Nonaktif</option>
            </select>
          </div>
        @endif
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">File Template {{ $mode === 'create' ? '*' : '' }}</label>
        <input type="file" name="template_file" class="w-full text-xs md:text-sm">
        <p class="mt-1 text-[11px] text-gray-500">Hanya file PDF atau gambar (PNG, JPG, JPEG) yang diperbolehkan.</p>
        @if ($mode === 'edit' && !empty($template->file_path))
          <p class="mt-1 text-[11px] text-gray-500">File saat ini: <span class="font-mono">{{ $template->file_path }}</span></p>
        @endif
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Koordinat (JSON)</label>
        <textarea name="coordinates" rows="4" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-xs font-mono focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder='{"name": {"x": 100, "y": 120, "size": 32, "centered": true}}'>{{ old('coordinates') }}</textarea>
        <p class="mt-1 text-[11px] text-gray-500">Opsional, gunakan key sesuai field sertifikat (misal: <code>name</code>, <code>certificate_title</code>, <code>issued_date</code>) untuk mengatur posisi di PDF. Jika kosong akan menggunakan posisi default.</p>
      </div>

      <div class="flex items-center justify-between pt-2 gap-2">
        <a href="{{ route('admin.templates.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs md:text-sm text-gray-700 hover:bg-gray-100">← Kembali</a>
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">
          {{ $mode === 'create' ? 'Simpan Template' : 'Update Template' }}
        </button>
      </div>
    </form>
  </div>
@endsection
