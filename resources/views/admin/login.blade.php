<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Verifikasi Sertifikat</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-gradient-to-b from-red-500 via-red-500 to-red-400 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-xl border border-red-100 p-6 md:p-8">
        <div class="flex items-center justify-center gap-2 mb-6">
          <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
          <div>
            <p class="text-xs text-gray-500">Portal Verifikasi Sertifikat</p>
            <h1 class="text-lg md:text-xl font-extrabold text-gray-900">Login Admin</h1>
          </div>
        </div>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
          @csrf
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
              <input id="admin-password" type="password" name="password" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 pr-11 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
              <button id="admin-password-peek" type="button" aria-label="Tahan untuk lihat password" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-gray-500 hover:text-gray-700" tabindex="-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
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
              Masuk ke Dashboard
            </button>
          </div>
        </form>
      </div>
    </div>
    <script>
      (function () {
        const input = document.getElementById('admin-password');
        const btn = document.getElementById('admin-password-peek');
        if (!input || !btn) return;

        const show = () => { input.type = 'text'; };
        const hide = () => { input.type = 'password'; };

        if (window.PointerEvent) {
          btn.addEventListener('pointerdown', (e) => { e.preventDefault(); show(); });
          btn.addEventListener('pointerup', hide);
          btn.addEventListener('pointercancel', hide);
          btn.addEventListener('pointerleave', hide);
        } else {
          btn.addEventListener('mousedown', (e) => { e.preventDefault(); show(); });
          btn.addEventListener('mouseup', hide);
          btn.addEventListener('mouseleave', hide);
          btn.addEventListener('touchstart', (e) => { e.preventDefault(); show(); }, { passive: false });
          btn.addEventListener('touchend', hide);
          btn.addEventListener('touchcancel', hide);
        }

        window.addEventListener('blur', hide);
      })();
    </script>
  </body>
</html>
