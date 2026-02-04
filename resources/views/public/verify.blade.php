<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Verifikasi Sertifikat - PT Indo Bismar</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-gradient-to-b from-red-500 via-red-500 to-red-400 text-gray-900">
    <header class="fixed top-0 left-0 right-0 z-40 bg-gradient-to-r from-red-600 via-red-600 to-red-500 text-white shadow">
      <div class="max-w-6xl mx-auto flex items-center justify-between px-4 h-16">
        <div class="flex items-center gap-3">
          <img src="/images/logo.png" alt="Logo" class="w-10 h-10 object-contain">
          <span class="font-bold text-lg">Verifikasi Sertifikat</span>
        </div>
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
          <a href="{{ route('public.landing') }}#beranda" class="hover:text-red-100">Beranda</a>
          <a href="{{ route('public.landing') }}#tentang" class="hover:text-red-100">Tentang</a>
          <a href="{{ route('public.landing') }}#kontak" class="hover:text-red-100">Kontak</a>
        </nav>
      </div>
    </header>

    <main class="pt-20 md:pt-24 pb-12 flex items-center justify-center min-h-[calc(100vh-4rem)]">
      <section class="w-full max-w-xl px-4">
        <div class="bg-white rounded-3xl shadow-xl border border-red-100 p-6 md:p-8">
          <div class="flex items-center justify-center gap-2 mb-6">
            <span class="text-lg">🔍</span>
            <h1 class="text-lg md:text-xl font-extrabold text-gray-900">Verifikasi Sertifikat</h1>
          </div>

          <form method="POST" action="{{ route('public.verify') }}" class="space-y-4">
            @csrf
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Kode Verifikasi</label>
              <input
                type="text"
                name="verify_code"
                value="{{ old('verify_code', $input['verify_code'] ?? '') }}"
                placeholder="Masukkan Kode Verifikasi"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                required
              >
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nama</label>
              <input
                type="text"
                name="name"
                value="{{ old('name', $input['name'] ?? '') }}"
                placeholder="Masukkan nama lengkap tanpa gelar"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                required
              >
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Lahir</label>
              <input
                type="date"
                name="date_of_birth"
                value="{{ old('date_of_birth', $input['date_of_birth'] ?? '') }}"
                placeholder="dd/mm/yyyy"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                required
              >
            </div>

            @if ($errors->any())
              <div class="text-xs text-red-600">
                <ul class="list-disc pl-4 space-y-0.5">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="pt-2">
              <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold shadow-sm transition">
                Verifikasi Sekarang
              </button>
            </div>
          </form>

          @if ($result === 'valid')
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-3 py-3 text-xs md:text-sm text-green-800">
              <p class="font-semibold mb-1">Sertifikat Valid</p>
              <p class="mb-1">Nama: <span class="font-medium">{{ $certificate->name }}</span></p>
              <p class="mb-1">Sekolah/Institusi: <span class="font-medium">{{ $certificate->company_name }}</span></p>
              @if ($certificate->issued_date)
                <p class="mb-1">Tanggal Terbit: <span class="font-medium">{{ \Illuminate\Support\Carbon::parse($certificate->issued_date)->format('d-m-Y') }}</span></p>
              @endif
              <p class="mb-2">Kode Verifikasi: <span class="font-mono">{{ $certificate->verify_code }}</span></p>

              <div class="mt-2 flex flex-wrap gap-2">
                @if (!empty($certificate->generated_pdf_path))
                  <a
                    href="{{ asset($certificate->generated_pdf_path) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow text-xs md:text-sm transition-colors"
                  >
                    Lihat PDF
                  </a>
                  <a
                    href="{{ route('public.certificate.download', $certificate->id) }}"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow text-xs md:text-sm transition-colors"
                  >
                    Download PDF
                  </a>
                @elseif (!empty($certificate->pdf_url))
                  <a
                    href="{{ $certificate->pdf_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow text-xs md:text-sm transition-colors"
                  >
                    Lihat Sertifikat
                  </a>
                @endif
              </div>
            </div>
          @elseif ($result === 'invalid')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-xs md:text-sm text-red-800">
              <p class="font-semibold mb-1">Sertifikat tidak ditemukan</p>
              <p>Pastikan data yang Anda masukkan sudah benar.</p>
            </div>
          @endif
        </div>

        <div class="mt-4 flex justify-center">
          <a href="{{ route('public.landing') }}" class="inline-flex items-center text-xs md:text-sm text-red-50 hover:text-white">
            ← Kembali ke Beranda
          </a>
        </div>
      </section>
    </main>
  </body>
</html>
