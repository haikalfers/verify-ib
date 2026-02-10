@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-lg md:text-2xl font-bold text-gray-900">Trash Sertifikat</h1>
        <p class="text-xs md:text-sm text-gray-600">Lihat dan pulihkan sertifikat yang sudah dihapus (soft delete).</p>
      </div>
      <div class="flex flex-col md:flex-row gap-2 md:items-center">
        <form method="GET" action="{{ route('admin.certificates.trash') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <input
            type="text"
            name="q"
            value="{{ $search ?? '' }}"
            placeholder="Cari nama, judul, nomor, atau kode"
            class="w-full sm:w-64 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs md:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
          >
          <select
            name="category"
            class="w-full sm:w-48 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs md:text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
            onchange="this.form.submit()"
          >
            <option value="">Semua Kategori</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}" {{ (isset($category) && $category === $cat) ? 'selected' : '' }}>
                {{ $cat }}
              </option>
            @endforeach
          </select>
          <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-gray-100 text-xs font-medium text-gray-700 hover:bg-gray-200">Cari</button>
        </form>
      </div>
    </div>

    @if (session('status'))
      @php
        $statusMessage = session('status');
        $isPermanentDelete = str_contains($statusMessage, 'dihapus permanen');
      @endphp

      @if ($isPermanentDelete)
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
          {{ $statusMessage }}
        </div>
      @else
        <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs md:text-sm text-green-800">
          {{ $statusMessage }}
          <a href="{{ route('admin.certificates.index') }}" class="ml-2 underline text-green-900">Lihat di Kelola Sertifikat</a>
        </div>
      @endif
    @endif

    @if ($errors->has('general'))
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
        {{ $errors->first('general') }}
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <form method="POST" action="{{ route('admin.certificates.force-delete-bulk') }}" id="bulkDeleteForm">
        @csrf
        <div class="flex items-center justify-between px-3 pt-3 pb-2 border-b border-gray-100 text-xs md:text-sm">
          <div class="text-gray-700">Pilih beberapa sertifikat untuk dihapus permanen.</div>
          <button
            type="submit"
            id="bulkDeleteButton"
            class="inline-flex items-center px-3 py-1.5 rounded-md border border-red-300 text-red-600 bg-white hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed"
            onclick="return confirm('Hapus permanen semua sertifikat yang dipilih? Tindakan ini tidak dapat dibatalkan.');"
            disabled
          >
            Hapus Permanen Terpilih
          </button>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs md:text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 font-medium text-gray-600">
                <input type="checkbox" id="selectAllTrash" class="rounded border-gray-300 text-red-500 focus:ring-red-500">
              </th>
              <th class="px-3 py-2 font-medium text-gray-600">No</th>
              <th class="px-3 py-2 font-medium text-gray-600">Nama</th>
              <th class="px-3 py-2 font-medium text-gray-600">Judul Sertifikat</th>
              <th class="px-3 py-2 font-medium text-gray-600">Kategori</th>
              <th class="px-3 py-2 font-medium text-gray-600">Nomor Sertifikat</th>
              <th class="px-3 py-2 font-medium text-gray-600">Dihapus Pada</th>
              <th class="px-3 py-2 font-medium text-gray-600 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($certificates as $index => $certificate)
              <tr class="border-t border-gray-100 hover:bg-gray-50/80">
                <td class="px-3 py-2 align-top text-gray-600">
                  <input type="checkbox" name="ids[]" value="{{ $certificate->id }}" class="row-checkbox rounded border-gray-300 text-red-500 focus:ring-red-500">
                </td>
                <td class="px-3 py-2 align-top text-gray-600">
                  {{ ($certificates->currentPage() - 1) * $certificates->perPage() + $index + 1 }}
                </td>
                <td class="px-3 py-2 align-top text-gray-900 font-medium">
                  {{ $certificate->name }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700">
                  {{ $certificate->certificate_title }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700">
                  {{ $certificate->category }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700 font-mono text-[11px]">
                  {{ $certificate->certificate_number }}
                </td>
                <td class="px-3 py-2 align-top text-gray-700 text-[11px]">
                  {{ $certificate->deleted_at }}
                </td>
                <td class="px-3 py-2 align-top text-right text-[11px] space-x-1">
                  <form action="{{ route('admin.certificates.restore', $certificate->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Pulihkan sertifikat ini?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-green-300 text-green-700 hover:bg-green-50">Restore</button>
                  </form>
                  <form action="{{ route('admin.certificates.force-delete', $certificate->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus permanen sertifikat ini? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded-md border border-red-300 text-red-600 hover:bg-red-50">Hapus Permanen</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-3 py-6 text-center text-xs md:text-sm text-gray-500">
                  Trash kosong.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100 gap-2">
        <div></div>
        <div>
          @if ($certificates->lastPage() > 1)
            @php
              $current = $certificates->currentPage();
              $last = $certificates->lastPage();
              $start = max(1, $current - 1);
              $end = min($last, $current + 1);
            @endphp

            <nav class="inline-flex items-center gap-1 text-xs md:text-sm" aria-label="Pagination">
              @if ($certificates->onFirstPage())
                <span class="px-2 py-1 rounded border border-gray-200 text-gray-400 cursor-not-allowed">&lt;</span>
              @else
                <a href="{{ $certificates->previousPageUrl() }}" class="px-2 py-1 rounded border border-gray-200 text-gray-700 hover:bg-gray-50">&lt;</a>
              @endif

              <a href="{{ $certificates->url(1) }}" class="px-2 py-1 rounded border {{ $current === 1 ? 'bg-red-600 text-white border-red-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">1</a>

              @if ($start > 2)
                <span class="px-2 py-1 text-gray-400">...</span>
              @endif

              @for ($page = $start; $page <= $end; $page++)
                @if ($page !== 1 && $page !== $last)
                  <a
                    href="{{ $certificates->url($page) }}"
                    class="px-2 py-1 rounded border {{ $current === $page ? 'bg-red-600 text-white border-red-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}"
                  >
                    {{ $page }}
                  </a>
                @endif
              @endfor

              @if ($end < $last - 1)
                <span class="px-2 py-1 text-gray-400">...</span>
              @endif

              @if ($last > 1)
                <a href="{{ $certificates->url($last) }}" class="px-2 py-1 rounded border {{ $current === $last ? 'bg-red-600 text-white border-red-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">{{ $last }}</a>
              @endif

              @if ($certificates->hasMorePages())
                <a href="{{ $certificates->nextPageUrl() }}" class="px-2 py-1 rounded border border-gray-200 text-gray-700 hover:bg-gray-50">&gt;</a>
              @else
                <span class="px-2 py-1 rounded border border-gray-200 text-gray-400 cursor-not-allowed">&gt;</span>
              @endif
            </nav>
          @endif
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const form = document.getElementById('bulkDeleteForm');
      if (!form) return;

      const selectAll = document.getElementById('selectAllTrash');
      const bulkButton = document.getElementById('bulkDeleteButton');
      const checkboxes = form.querySelectorAll('.row-checkbox');

      function updateBulkButtonState() {
        if (!bulkButton) return;
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        bulkButton.disabled = !anyChecked;
      }

      if (selectAll) {
        selectAll.addEventListener('change', function () {
          const checked = this.checked;
          checkboxes.forEach(cb => { cb.checked = checked; });
          updateBulkButtonState();
        });
      }

      checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
          if (selectAll) {
            if (!this.checked) {
              selectAll.checked = false;
            } else {
              const allChecked = Array.from(checkboxes).every(x => x.checked);
              selectAll.checked = allChecked;
            }
          }
          updateBulkButtonState();
        });
      });

      updateBulkButtonState();
    })();
  </script>
@endsection
