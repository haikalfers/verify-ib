@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">Template Sertifikat</h1>
        <p class="text-xs md:text-sm text-gray-600">Kelola template sertifikat yang digunakan untuk generate PDF.</p>
      </div>
      <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">
        + Tambah Template
      </a>
    </div>

    @if (session('status'))
      <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs md:text-sm text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
        {{ $errors->first() }}
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs md:text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 font-medium text-gray-600">Nama</th>
              <th class="px-3 py-2 font-medium text-gray-600">Preview</th>
              <th class="px-3 py-2 font-medium text-gray-600">Kategori</th>
              <th class="px-3 py-2 font-medium text-gray-600">Tipe File</th>
              <th class="px-3 py-2 font-medium text-gray-600">Status</th>
              <th class="px-3 py-2 font-medium text-gray-600">Dibuat</th>
              <th class="px-3 py-2 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($templates as $template)
              <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                <td class="px-3 py-2 align-top text-gray-900 font-medium">
                  {{ $template->name }}
                  @if ($template->description)
                    <div class="text-[11px] text-gray-500">{{ $template->description }}</div>
                  @endif
                </td>
                <td class="px-3 py-2 align-top">
                  @if ($template->file_type === 'image' && !empty($template->file_path))
                    <button
                      type="button"
                      class="group"
                      data-template-preview="{{ asset(ltrim($template->file_path, '/')) }}"
                    >
                      <div class="w-20 h-12 rounded-md overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center group-hover:ring-2 group-hover:ring-red-400 group-hover:ring-offset-1 transition">
                        <img
                          src="{{ asset(ltrim($template->file_path, '/')) }}"
                          alt="Preview {{ $template->name }}"
                          class="max-w-full max-h-full object-contain pointer-events-none"
                        >
                      </div>
                    </button>
                  @else
                    <span class="text-[11px] text-gray-400">-</span>
                  @endif
                </td>
                <td class="px-3 py-2 align-top text-gray-700">
                  {{ $template->category ?? '-' }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700 text-[11px] uppercase">
                  {{ $template->file_type ?? '-' }}
                </td>
                <td class="px-3 py-2 align-top">
                  <form action="{{ route('admin.templates.toggle', $template->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-medium {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                      {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                    </button>
                  </form>
                </td>
                <td class="px-3 py-2 align-top text-gray-600 text-[11px]">
                  {{ optional($template->created_at)->format('d M Y') ?? '-' }}
                </td>
                <td class="px-3 py-2 align-top text-right text-[11px] space-x-1">
                  <a href="{{ route('admin.templates.edit', $template->id) }}" class="inline-flex items-center px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">Edit</a>
                  <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus template ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-red-300 text-red-600 hover:bg-red-50">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-3 py-6 text-center text-xs md:text-sm text-gray-500">
                  Belum ada template sertifikat.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Modal preview gambar template --}}
  <div
    id="template-preview-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm"
  >
    <div class="relative max-w-4xl max-h-[90vh] mx-4 bg-white rounded-xl shadow-2xl overflow-hidden">
      <button
        type="button"
        id="template-preview-close"
        class="absolute top-2 right-2 inline-flex items-center justify-center w-8 h-8 rounded-full bg-black/60 text-white text-sm hover:bg-black/80"
      >
        ✕
      </button>
      <div class="bg-gray-900 flex items-center justify-center p-3">
        <img
          id="template-preview-image"
          src=""
          alt="Preview Template"
          class="max-w-full max-h-[80vh] object-contain bg-gray-900"
        >
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modal = document.getElementById('template-preview-modal');
      const modalImg = document.getElementById('template-preview-image');
      const btnClose = document.getElementById('template-preview-close');

      if (!modal || !modalImg || !btnClose) return;

      document.querySelectorAll('[data-template-preview]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const src = this.getAttribute('data-template-preview');
          if (!src) return;
          modalImg.src = src;
          modal.classList.remove('hidden');
          modal.classList.add('flex');
        });
      });

      function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalImg.src = '';
      }

      btnClose.addEventListener('click', closeModal);

      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          closeModal();
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
          closeModal();
        }
      });
    });
  </script>
@endsection
