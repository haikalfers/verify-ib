@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6 max-w-3xl">
    <div>
      <h1 class="text-lg md:text-2xl font-bold text-gray-900">Import Sertifikat via CSV</h1>
      <p class="text-xs md:text-sm text-gray-600">Upload file CSV untuk menambahkan dan generate sertifikat secara massal.</p>
    </div>

    @if ($errors->any())
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
        <p class="font-semibold mb-1">Terjadi kesalahan:</p>
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('import_result'))
      @php $r = session('import_result'); @endphp
      <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs md:text-sm text-green-800 space-y-1">
        <p class="font-semibold">Hasil Import</p>
        <p>Total baris diproses: <span class="font-mono">{{ $r['total'] ?? 0 }}</span></p>
        <p>Berhasil: <span class="font-mono">{{ $r['success'] ?? 0 }}</span></p>
        <p>Gagal: <span class="font-mono">{{ $r['failed'] ?? 0 }}</span></p>
        @if (!empty($r['errors']))
          <details class="mt-1">
            <summary class="cursor-pointer text-xs underline">Lihat detail error</summary>
            <ul class="mt-1 list-disc pl-4 space-y-0.5 text-xs text-red-700">
              @foreach ($r['errors'] as $msg)
                <li>{{ $msg }}</li>
              @endforeach
            </ul>
          </details>
        @endif
      </div>
    @endif

    <form method="POST" action="{{ route('admin.certificates.import.process') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-4">
      @csrf

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">File CSV *</label>
        <input type="file" name="file" accept=".csv,text/csv" class="w-full text-xs md:text-sm">
        <p class="mt-1 text-[11px] text-gray-500">Format file: CSV dengan header berikut (urutan bebas, nama harus sama):</p>
        <pre class="mt-1 text-[11px] bg-gray-50 border border-gray-200 rounded px-2 py-1 overflow-x-auto">
company_name;template_name;name;place_of_birth;date_of_birth;category;competency_field;place_of_issue;issued_date;certificate_title;internship_start_date;internship_end_date
        </pre>
        <p class="mt-1 text-[11px] text-gray-500">Tanggal gunakan format <code>YYYY-MM-DD</code>. Kolom <code>competency_field</code>, <code>internship_start_date</code>, dan <code>internship_end_date</code> opsional.</p>
        <p class="mt-1 text-[11px] text-gray-500">Kolom <code>template_name</code> akan dicocokkan dengan nama template aktif di sistem.</p>
      </div>

      <div class="flex items-center justify-between pt-2 gap-2">
        <a href="{{ route('admin.certificates.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs md:text-sm text-gray-700 hover:bg-gray-100">← Kembali</a>
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">Proses Import</button>
      </div>
    </form>
  </div>
@endsection
