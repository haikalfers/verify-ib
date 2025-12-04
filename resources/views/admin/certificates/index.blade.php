@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">Kelola Sertifikat</h1>
        <p class="text-xs md:text-sm text-gray-600">Lihat dan kelola semua data sertifikat yang tersimpan di sistem.</p>
      </div>
      <div class="flex flex-col md:flex-row gap-2 md:items-center">
        <form method="GET" action="{{ route('admin.certificates.index') }}" class="flex items-center gap-2">
          <input
            type="text"
            name="q"
            value="{{ $search ?? '' }}"
            placeholder="Cari nama, judul, nomor, atau kode"
            class="w-full md:w-64 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs md:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
          >
          <button type="submit" class="hidden md:inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-100 text-xs font-medium text-gray-700 hover:bg-gray-200">Cari</button>
        </form>
        <div class="flex gap-2">
          <a
            href="{{ route('admin.certificates.import.form') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs md:text-sm font-semibold shadow-sm"
          >
            <span>⬇️</span>
            <span>Import CSV</span>
          </a>
          <a href="{{ route('admin.certificates.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">
            + Tambah Sertifikat
          </a>
        </div>
      </div>
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
              <th class="px-3 py-2 font-medium text-gray-600">No</th>
                <th class="px-3 py-2 font-medium text-gray-600">Nama</th>
              <th class="px-3 py-2 font-medium text-gray-600">Judul Sertifikat</th>
              <th class="px-3 py-2 font-medium text-gray-600">Nomor Sertifikat</th>
              <th class="px-3 py-2 font-medium text-gray-600">Kode Verifikasi</th>
              <th class="px-3 py-2 font-medium text-gray-600">Institusi</th>
              <th class="px-3 py-2 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($certificates as $index => $certificate)
              <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                <td class="px-3 py-2 align-top text-gray-600">
                  {{ ($certificates->currentPage() - 1) * $certificates->perPage() + $index + 1 }}
                </td>
                <td class="px-3 py-2 align-top text-gray-900 font-medium">
                  {{ $certificate->name }}
                  @if ($certificate->date_of_birth)
                    <div class="text-[11px] text-gray-500">{{ $certificate->date_of_birth }}</div>
                  @endif
                </td>
                <td class="px-3 py-2 align-top text-gray-700">
                  {{ $certificate->certificate_title }}
                  @if ($certificate->category)
                    <div class="text-[11px] text-gray-500">Kategori: {{ $certificate->category }}</div>
                  @endif
                </td>
                <td class="px-3 py-2 align-top text-gray-700 font-mono text-[11px]">
                  {{ $certificate->certificate_number }}
                </td>
                <td class="px-3 py-2 align-top text-gray-800 font-mono text-[11px]">
                  {{ $certificate->verify_code }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700">
                  {{ $certificate->company_name }}
                </td>
                <td class="px-3 py-2 align-top text-right text-[11px] space-x-1">
                  @if (!empty($certificate->generated_pdf_path))
                    <a
                      href="{{ asset($certificate->generated_pdf_path) }}"
                      target="_blank"
                      class="inline-flex items-center px-2 py-1 rounded-md border border-green-300 text-green-700 hover:bg-green-50 mb-1"
                    >
                      Lihat PDF
                    </a>
                    <a
                      href="{{ route('admin.certificates.download', $certificate->id) }}"
                      class="inline-flex items-center px-2 py-1 rounded-md border border-blue-300 text-blue-700 hover:bg-blue-50 mb-1"
                    >
                      Download
                    </a>
                  @endif
                  <a href="{{ route('admin.certificates.edit', $certificate->id) }}" class="inline-flex items-center px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">Edit</a>
                  <form action="{{ route('admin.certificates.destroy', $certificate->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus sertifikat ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-red-300 text-red-600 hover:bg-red-50">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-3 py-6 text-center text-xs md:text-sm text-gray-500">
                  Belum ada data sertifikat.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 gap-2">
        <form method="POST" action="{{ route('admin.certificates.destroy-page') }}" onsubmit="return confirm('Hapus semua sertifikat di halaman ini?');">
          @csrf
          <input type="hidden" name="q" value="{{ $search ?? '' }}">
          <input type="hidden" name="page" value="{{ $certificates->currentPage() }}">
          <button
            type="submit"
            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-300 text-red-600 text-xs md:text-sm hover:bg-red-50"
          >
            Hapus semua di halaman ini
          </button>
        </form>
        <div>
          {{ $certificates->links() }}
        </div>
      </div>
    </div>

  </div>
  @endsection
