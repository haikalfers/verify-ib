<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email Admin - Verifikasi Sertifikat</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-gradient-to-b from-red-500 via-red-500 to-red-400 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-xl border border-red-100 p-6 md:p-8">
        <div class="flex items-center justify-center gap-2 mb-6">
          <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
          <div>
            <p class="text-xs text-gray-500">Portal Verifikasi Sertifikat</p>
            <h1 class="text-lg md:text-xl font-extrabold text-gray-900">Verifikasi Email Admin</h1>
          </div>
        </div>

        @if (session('status'))
          <div class="mb-4 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
            {{ session('status') }}
          </div>
        @endif

        <form method="POST" action="{{ route('admin.verify.submit') }}" class="space-y-4">
          @csrf
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Kode Verifikasi</label>
            <input type="text" name="code" value="{{ old('code') }}" maxlength="6" class="uppercase tracking-widest w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
            <p class="mt-1 text-[11px] text-gray-500">Masukkan kode verifikasi 6 karakter yang dikirim ke email admin.</p>
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

          <div class="pt-2 flex items-center justify-between gap-3">
            <a href="{{ route('admin.login') }}" class="text-[11px] text-gray-500 hover:text-gray-700">Kembali ke login</a>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold shadow-sm transition">
              Verifikasi dan Masuk
            </button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
