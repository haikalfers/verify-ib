@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">Varian Template: {{ $template->name }}</h1>
        <p class="text-xs md:text-sm text-gray-600">Kelola varian tampilan untuk template ini (file background dan koordinat).</p>
      </div>
      <a href="{{ route('admin.templates.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs md:text-sm text-gray-700 hover:bg-gray-100">← Kembali</a>
    </div>

    @if (session('status'))
      <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs md:text-sm text-green-800">
        {{ session('status') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
        <p class="font-semibold mb-1">Periksa kembali data:</p>
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-4">
      <h2 class="text-sm md:text-base font-semibold text-gray-900">Tambah Varian</h2>
      <form method="POST" action="{{ route('admin.templates.variants.store', $template->id) }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
        @csrf
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Nama Varian *</label>
          <input type="text" name="variant_name" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Misal: Indobismar" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">File Template *</label>
          <input type="file" name="template_file" class="w-full text-xs md:text-sm" required>
          <p class="mt-1 text-[11px] text-gray-500">PDF atau gambar (PNG, JPG, JPEG).</p>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 mb-1">Koordinat (JSON)</label>
          <textarea name="coordinates" rows="5" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-xs font-mono focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder='{"_page":{"orientation":"L","width":297,"height":210}, "name":{"x":148.5,"y":90,"size":28,"centered":true}}'></textarea>
          <p class="mt-1 text-[11px] text-gray-500">Opsional. Jika kosong, gunakan koordinat dari template induk.</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Jadikan Default?</label>
          <select name="is_default" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm">
            <option value="0">Tidak</option>
            <option value="1">Ya</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
          <select name="is_active" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">Simpan Varian</button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs md:text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 font-medium text-gray-600">Varian</th>
              <th class="px-3 py-2 font-medium text-gray-600">Default</th>
              <th class="px-3 py-2 font-medium text-gray-600">Status</th>
              <th class="px-3 py-2 font-medium text-gray-600">File</th>
              <th class="px-3 py-2 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($variants as $v)
              <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                <td class="px-3 py-2 align-top text-gray-900 font-medium">{{ $v->variant_name }}</td>
                <td class="px-3 py-2 align-top">{!! $v->is_default ? '<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[11px]">Default</span>' : '-' !!}</td>
                <td class="px-3 py-2 align-top">{!! ($v->is_active ? '<span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-[11px]">Aktif</span>' : '<span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-[11px]">Nonaktif</span>') !!}</td>
                <td class="px-3 py-2 align-top text-[11px] font-mono">{{ $v->file_path }}</td>
                <td class="px-3 py-2 align-top text-right space-x-1">
                  <form action="{{ route('admin.templates.variants.toggle', $v->id) }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">{{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                  </form>
                  @if (!$v->is_default)
                    <form action="{{ route('admin.templates.variants.default', $v->id) }}" method="POST" class="inline-block">
                      @csrf
                      <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Jadikan Default</button>
                    </form>
                  @endif
                  <a href="{{ route('admin.templates.variants.edit', $v->id) }}" class="inline-flex items-center px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">Edit</a>
                  <form action="{{ route('admin.templates.variants.destroy', $v->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus varian ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-red-300 text-red-600 hover:bg-red-50">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-6 text-center text-gray-500">Belum ada varian</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
