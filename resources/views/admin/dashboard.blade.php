@extends('admin.layout')

@section('content')
  <div class="space-y-6">
    {{-- Header selamat datang --}}
    <div class="space-y-1">
      <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Selamat Datang di Dashboard Admin</h1>
      <p class="text-xs md:text-sm text-gray-600">Kelola sistem verifikasi sertifikat dengan mudah dan efisien.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Total Sertifikat</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCertificates) }}</p>
        <p class="text-[11px] text-green-600">+12% dari bulan lalu</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Total Template</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($totalTemplates) }}</p>
        <p class="text-[11px] text-green-600">+5% dari bulan lalu</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Verifikasi Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900">41</p>
        <p class="text-[11px] text-green-600">+8% dari bulan lalu</p>
      </div>
    </div>

    {{-- Baris kedua: Aksi Cepat & Aktivitas Terbaru --}}
    <div class="grid gap-4 md:gap-6 lg:grid-cols-2">
      {{-- Aksi Cepat --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-3">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-sm md:text-base font-semibold text-gray-900">Aksi Cepat</h2>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <a href="{{ route('admin.certificates.create') }}" class="flex flex-col items-start gap-1 rounded-xl bg-red-500 hover:bg-red-600 text-white px-4 py-3 text-left text-sm shadow-sm">
            <span class="font-semibold flex items-center gap-2"><span class="text-lg">＋</span>Tambah Sertifikat</span>
            <span class="text-[11px] opacity-90">Tambahkan sertifikat baru ke sistem</span>
          </a>
          <a href="{{ route('admin.certificates.index') }}" class="flex flex-col items-start gap-1 rounded-xl bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 text-left text-sm shadow-sm">
            <span class="font-semibold flex items-center gap-2"><span class="text-lg">📜</span>Kelola Sertifikat</span>
            <span class="text-[11px] opacity-90">Lihat dan kelola semua sertifikat</span>
          </a>
          <a href="{{ route('admin.reports.index') }}" class="flex flex-col items-start gap-1 rounded-xl bg-green-500 hover:bg-green-600 text-white px-4 py-3 text-left text-sm shadow-sm">
            <span class="font-semibold flex items-center gap-2"><span class="text-lg">📊</span>Lihat Laporan</span>
            <span class="text-[11px] opacity-90">Analisis dan statistik sistem</span>
          </a>
          <a href="{{ route('admin.templates.index') }}" class="flex flex-col items-start gap-1 rounded-xl bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 text-left text-sm shadow-sm">
            <span class="font-semibold flex items-center gap-2"><span class="text-lg">📄</span>Management Template</span>
            <span class="text-[11px] opacity-90">Kelola template sertifikat</span>
          </a>
        </div>
      </div>

      {{-- Aktivitas Terbaru --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-3">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-sm md:text-base font-semibold text-gray-900">Aktivitas Terbaru</h2>
        </div>
        <ul class="space-y-2 text-xs md:text-sm">
          @forelse ($latestCertificates as $item)
            <li class="flex flex-col">
              <span class="text-red-600 font-medium">• Sertifikat "{{ $item->certificate_title ?? $item->name ?? 'Tanpa judul' }}" ditambahkan</span>
              <span class="text-[11px] text-gray-500">
                Admin
                @if (!empty($item->created_at))
                  • {{ \Illuminate\Support\Carbon::parse($item->created_at)->format('d M Y \p\u\k\u\l H:i') }}
                @endif
              </span>
            </li>
          @empty
            <li class="text-[11px] md:text-xs text-gray-500">
              Belum ada aktivitas terbaru.
            </li>
          @endforelse
        </ul>
        <a href="{{ route('admin.certificates.index') }}" class="mt-1 inline-block text-[11px] md:text-xs font-semibold text-red-600 hover:text-red-700">Lihat semua sertifikat →</a>
      </div>
    </div>
  </div>
@endsection
