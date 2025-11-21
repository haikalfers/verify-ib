@extends('admin.layout')

@section('content')
  <div class="space-y-6">
    {{-- Header laporan --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Laporan Sistem</h1>
        <p class="text-xs md:text-sm text-gray-600">Ringkasan statistik sertifikat dan penggunaan sistem.</p>
      </div>
      <div class="flex items-center gap-2">
        <a
          href="{{ route('admin.reports.export') }}"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs md:text-sm font-semibold shadow-sm"
        >
          <span>⬇️</span>
          <span>Export CSV</span>
        </a>
      </div>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid gap-4 md:gap-5 md:grid-cols-2 lg:grid-cols-3">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Total Sertifikat</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCertificates) }}</p>
        <p class="text-[11px] text-gray-500">Semua sertifikat yang tersimpan di sistem.</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Template Aktif</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($totalTemplates) }}</p>
        <p class="text-[11px] text-gray-500">Template yang dapat dipilih saat membuat sertifikat.</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col gap-1">
        <p class="text-xs font-medium text-gray-500">Perkiraan Rata-rata / Bulan</p>
        <p class="text-2xl font-bold text-gray-900">
          @php $avgPerMonth = $byMonth->avg('total') ?: 0; @endphp
          {{ number_format($avgPerMonth, 1) }}
        </p>
        <p class="text-[11px] text-gray-500">Rata-rata sertifikat terbit per bulan (12 bulan terakhir).</p>
      </div>
    </div>

    {{-- Grid dua kolom: per kategori & per bulan --}}
    <div class="grid gap-4 md:gap-6 lg:grid-cols-2">
      {{-- Sertifikat per kategori --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-3">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-sm md:text-base font-semibold text-gray-900">Distribusi Sertifikat per Kategori</h2>
        </div>
        <div class="space-y-2 text-xs md:text-sm">
          @forelse ($byCategory as $row)
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <p class="font-medium text-gray-800">{{ $row->category ?? 'Tanpa kategori' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-gray-700">{{ $row->total }}</span>
                <div class="h-1.5 w-20 md:w-28 rounded-full bg-gray-100 overflow-hidden">
                  @php
                    $percent = $totalCertificates ? min(100, round(($row->total / max(1, $totalCertificates)) * 100)) : 0;
                  @endphp
                  <div class="h-full bg-red-500" style="width: {{ $percent }}%"></div>
                </div>
              </div>
            </div>
          @empty
            <p class="text-[11px] text-gray-500">Belum ada data kategori.</p>
          @endforelse
        </div>
      </div>

      {{-- Sertifikat per bulan --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-3">
        <div class="flex items-center justify-between mb-1">
          <h2 class="text-sm md:text-base font-semibold text-gray-900">Sertifikat Terbit per Bulan</h2>
        </div>
        <div class="space-y-2 text-xs md:text-sm">
          @forelse ($byMonth as $row)
            @php
              [$year, $month] = explode('-', $row->ym);
              $label = \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->locale('id')->translatedFormat('M Y');
            @endphp
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <p class="font-medium text-gray-800">{{ $label }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-gray-700">{{ $row->total }}</span>
                <div class="h-1.5 w-20 md:w-28 rounded-full bg-gray-100 overflow-hidden">
                  @php
                    $maxMonth = $byMonth->max('total') ?: 1;
                    $percent = min(100, round(($row->total / $maxMonth) * 100));
                  @endphp
                  <div class="h-full bg-green-500" style="width: {{ $percent }}%"></div>
                </div>
              </div>
            </div>
          @empty
            <p class="text-[11px] text-gray-500">Belum ada data tanggal terbit.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection
