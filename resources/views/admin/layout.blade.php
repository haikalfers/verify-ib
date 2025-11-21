<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Verifikasi Sertifikat</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="min-h-screen flex">
      {{-- Sidebar kiri --}}
      <aside class="hidden md:flex md:flex-col w-64 bg-white border-r border-gray-200">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-200 bg-red-600 text-white">
          <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain">
          <div class="leading-tight">
            <p class="text-xs text-red-100">Portal Verifikasi Sertifikat</p>
            <p class="font-semibold text-sm">Admin Panel</p>
          </div>
        </div>

        <nav class="space-y-1">
          <a href="{{ route('admin.dashboard') }}"
             class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-red-50 text-red-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-500' }}">
            <span class="text-lg">🏠</span>
            <span>Dashboard</span>
          </a>
          <a href="{{ route('admin.certificates.index') }}"
             class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.certificates.*') ? 'bg-red-50 text-red-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-500' }}">
            <span class="text-lg">📜</span>
            <span>Kelola Sertifikat</span>
          </a>
          <a href="{{ route('admin.templates.index') }}"
             class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.templates.*') ? 'bg-red-50 text-red-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-500' }}">
            <span class="text-lg">📄</span>
            <span>Template Sertifikat</span>
          </a>
          <a href="{{ route('admin.reports.index') }}"
             class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.reports.*') ? 'bg-red-50 text-red-600 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-500' }}">
            <span class="text-lg">📊</span>
            <span>Laporan</span>
          </a>
        </nav>

        <div class="px-4 pb-4">
          <div class="bg-gray-50 rounded-xl border border-gray-200 px-3 py-3 flex items-center gap-3 text-xs">
            <div class="h-9 w-9 rounded-full bg-gray-300 flex items-center justify-center text-xs font-semibold">N</div>
            <div class="flex-1">
              <p class="font-medium text-gray-800 truncate">admin@example.com</p>
              <p class="text-[11px] text-gray-500">Administrator</p>
            </div>
          </div>
        </div>
      </aside>

      {{-- Area konten kanan --}}
      <div class="flex-1 flex flex-col min-w-0">
        {{-- Top bar --}}
        <header class="h-14 md:h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-6">
          <div>
            <p class="text-sm font-semibold text-gray-900">Dashboard</p>
          </div>
          <div class="flex items-center gap-4 text-xs md:text-sm">
            <a href="{{ route('public.landing') }}" class="text-gray-600 hover:text-gray-900">Lihat Situs</a>
            <form method="POST" action="{{ route('admin.logout') }}">
              @csrf
              <button type="submit" class="text-red-600 hover:text-red-700 font-semibold">Logout</button>
            </form>
          </div>
        </header>

        {{-- Konten utama --}}
        <main class="flex-1 px-4 md:px-6 py-4 md:py-6 overflow-x-hidden overflow-y-auto">
          @yield('content')
        </main>
      </div>
    </div>
  </body>
</html>
