@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6 max-w-3xl">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">
          {{ $mode === 'create' ? 'Tambah Unit Kompetensi' : 'Edit Unit Kompetensi' }}
        </h1>
        <p class="text-xs md:text-sm text-gray-600">Upload file PDF unit kompetensi yang akan digabungkan dengan sertifikat.</p>
      </div>
      <a href="{{ route('admin.competency-units.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs md:text-sm text-gray-700 hover:bg-gray-100">← Kembali</a>
    </div>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.competency-units.store') : route('admin.competency-units.update', $unit->id) }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-4">
      @csrf
      @if ($mode === 'edit')
        @method('PUT')
      @endif

      @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
          <p class="font-semibold mb-1">Periksa kembali data yang diisi:</p>
          <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Unit Kompetensi *</label>
        <input type="text" name="name" value="{{ old('name', $unit->name ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
        <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('description', $unit->description ?? '') }}</textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Kategori</label>
        <input type="text" name="category" value="{{ old('category', $unit->category ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Magang, Kompetensi">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">File Unit Kompetensi {{ $mode === 'create' ? '*' : '' }}</label>
        <input type="file" name="file" accept="application/pdf" class="w-full text-xs md:text-sm">
        <p class="mt-1 text-[11px] text-gray-500">Hanya file PDF yang diperbolehkan.</p>
        @if ($mode === 'edit' && !empty($unit->file_path))
          <p class="mt-1 text-[11px] text-gray-500">File saat ini: <span class="font-mono">{{ $unit->file_path }}</span></p>
        @endif
      </div>

      @if ($mode === 'edit')
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
          <select name="is_active" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <option value="1" @selected(old('is_active', $unit->is_active ?? 1) == 1)>Aktif</option>
            <option value="0" @selected(old('is_active', $unit->is_active ?? 1) == 0)>Nonaktif</option>
          </select>
        </div>
      @endif

      <div class="flex items-center justify-end pt-2 gap-2">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">
          {{ $mode === 'create' ? 'Simpan Unit Kompetensi' : 'Update Unit Kompetensi' }}
        </button>
      </div>
    </form>
  </div>
@endsection
