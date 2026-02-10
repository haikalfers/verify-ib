@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">Unit Kompetensi</h1>
        <p class="text-xs md:text-sm text-gray-600">Kelola master file PDF unit kompetensi yang akan digabung dengan sertifikat.</p>
      </div>
      <a href="{{ route('admin.competency-units.create') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">Tambah Unit Kompetensi</a>
    </div>

    @if (session('status'))
      <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs md:text-sm text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->has('general'))
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
        {{ $errors->first('general') }}
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs md:text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 font-medium text-gray-600">Nama</th>
              <th class="px-3 py-2 font-medium text-gray-600">Kategori</th>
              <th class="px-3 py-2 font-medium text-gray-600">File</th>
              <th class="px-3 py-2 font-medium text-gray-600">Status</th>
              <th class="px-3 py-2 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($units as $unit)
              <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                <td class="px-3 py-2 align-top text-gray-900 font-medium">{{ $unit->name }}</td>
                <td class="px-3 py-2 align-top text-gray-700">{{ $unit->category ?? '-' }}</td>
                <td class="px-3 py-2 align-top text-[11px] font-mono text-gray-700">{{ $unit->file_path }}</td>
                <td class="px-3 py-2 align-top">
                  @if ($unit->is_active)
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-[11px]">Aktif</span>
                  @else
                    <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-[11px]">Nonaktif</span>
                  @endif
                </td>
                <td class="px-3 py-2 align-top text-right space-x-1">
                  <a href="{{ route('admin.competency-units.edit', $unit->id) }}" class="inline-flex items-center px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">Edit</a>
                  <form action="{{ route('admin.competency-units.destroy', $unit->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus unit kompetensi ini? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-red-300 text-red-600 hover:bg-red-50">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-6 text-center text-xs md:text-sm text-gray-500">Belum ada unit kompetensi.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
