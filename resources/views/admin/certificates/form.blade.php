@extends('admin.layout')

@section('content')
  <div class="space-y-4 md:space-y-6 max-w-4xl">
    <div>
      <h1 class="text-lg md:text-2xl font-bold text-gray-900">
        {{ $mode === 'create' ? 'Tambah Sertifikat' : 'Edit Sertifikat' }}
      </h1>
      <p class="text-xs md:text-sm text-gray-600">Isi data sertifikat dengan lengkap dan sesuai.</p>
    </div>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.certificates.store') : route('admin.certificates.update', $certificate->id) }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 md:p-5 space-y-5">
      @csrf
      @if ($mode === 'edit')
        @method('PUT')
      @endif

      @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs md:text-sm text-red-800">
          <p class="font-semibold mb-1">Periksa kembali data yang diisi:</p>
          <ul class="list-disc pl-4 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Nama Perusahaan / Institusi *</label>
          <input type="text" name="company_name" value="{{ old('company_name', $certificate->company_name ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
          <p class="mt-1 text-[11px] text-gray-500">Digunakan untuk generate nomor sertifikat otomatis.</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Template Sertifikat *</label>
          @if ($mode === 'create')
            <select name="template_id" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
              <option value="">Pilih Template</option>
              @foreach (($templates ?? []) as $tpl)
                <option value="{{ $tpl->id }}" @selected(old('template_id') == $tpl->id)>{{ $tpl->name }}</option>
              @endforeach
            </select>
            <p class="mt-1 text-[11px] text-gray-500">Template aktif akan digunakan untuk auto-generate PDF sertifikat.</p>
          @else
            <input type="text" value="{{ $certificate->template_id ?? '-' }}" class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm" disabled>
          @endif
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap *</label>
          <input type="text" name="name" value="{{ old('name', $certificate->name ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tempat Lahir *</label>
          <input type="text" name="place_of_birth" value="{{ old('place_of_birth', $certificate->place_of_birth ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Lahir *</label>
          <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $certificate->date_of_birth ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tempat Terbit *</label>
          <input type="text" name="place_of_issue" value="{{ old('place_of_issue', $certificate->place_of_issue ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Terbit *</label>
          <input type="date" name="issued_date" value="{{ old('issued_date', $certificate->issued_date ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
          <p class="mt-1 text-[11px] text-gray-500">Digunakan untuk format nomor sertifikat (mm-yyyy).</p>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Kategori *</label>
          <select name="category" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
            <option value="">Pilih Kategori</option>
            @php $currentCategory = old('category', $certificate->category ?? ''); @endphp
            @foreach (($categories ?? []) as $cat)
              <option value="{{ $cat->name }}" @selected($currentCategory === $cat->name)>{{ $cat->name }}</option>
            @endforeach
          </select>
          <div class="mt-2 flex items-center gap-2">
            <input type="text" name="new_category" value="{{ old('new_category') }}" placeholder="Tambah kategori baru" class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <span class="text-[11px] text-gray-400">(opsional, disimpan manual nanti)</span>
          </div>
        </div>
      </div>

      <div id="internship-period-fields" class="grid md:grid-cols-2 gap-4 hidden">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai Magang *</label>
          <input type="date" name="internship_start_date" value="{{ old('internship_start_date') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai Magang *</label>
          <input type="date" name="internship_end_date" value="{{ old('internship_end_date') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
        </div>
      </div>

      <div id="competency-field-wrapper">
        <label class="block text-xs font-medium text-gray-700 mb-1">Bidang Kompetensi</label>
        <input type="text" name="competency_field" value="{{ old('competency_field', $certificate->competency_field ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Contoh: Web Development, Keamanan Jaringan">
      </div>

      <div id="certificate-title-wrapper">
        <label id="certificate-title-label" class="block text-xs font-medium text-gray-700 mb-1">Judul Sertifikat *</label>
        <input type="text" name="certificate_title" value="{{ old('certificate_title', $certificate->certificate_title ?? '') }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Masukkan judul sertifikat">
      </div>

      <div class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-3 text-[11px] text-blue-800 space-y-1">
        <p class="font-semibold">Informasi Penting</p>
        <ul class="list-disc list-inside space-y-0.5">
          <li>Nomor sertifikat dibuat otomatis dengan format: xxx/Perusahaan/mm-yyyy.</li>
          <li>Kode verifikasi 8 karakter dibuat otomatis dan unik.</li>
          <li>Field bertanda * wajib diisi agar proses generate sertifikat berhasil.</li>
          <li>Jika template dipilih, PDF akan di-generate dan disimpan otomatis.</li>
        </ul>
      </div>

      <div class="flex items-center justify-between pt-2 gap-2">
        <a href="{{ route('admin.certificates.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs md:text-sm text-gray-700 hover:bg-gray-100">← Kembali</a>
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs md:text-sm font-semibold shadow-sm">
          {{ $mode === 'create' ? 'Simpan Sertifikat' : 'Update Sertifikat' }}
        </button>
      </div>
    </form>
  </div>
  <script>
    (function () {
      const CATEGORY_MAGANG = 'Sertifikat PKL/Magang';
      const CATEGORY_UPSKILL = 'Sertifikat Upskilling Reskilling';

      const categorySelect = document.querySelector('select[name="category"]');
      const periodContainer = document.getElementById('internship-period-fields');
      const startInput = document.querySelector('input[name="internship_start_date"]');
      const endInput = document.querySelector('input[name="internship_end_date"]');
      const titleInput = document.querySelector('input[name="certificate_title"]');
      const titleLabel = document.getElementById('certificate-title-label');
      const competencyWrapper = document.getElementById('competency-field-wrapper');
      const titleWrapper = document.getElementById('certificate-title-wrapper');

      if (!categorySelect || !periodContainer || !startInput || !endInput || !titleInput || !titleLabel || !competencyWrapper || !titleWrapper) {
        return;
      }

      const bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ];

      function formatDateIndo(value) {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return '';
        const day = String(d.getDate()).padStart(2, '0');
        const month = bulanIndo[d.getMonth()] || '';
        const year = d.getFullYear();
        return month ? `${day} ${month} ${year}` : '';
      }

      function updateVisibility() {
        const isMagang = (categorySelect.value || '').trim() === CATEGORY_MAGANG;
        const isUpskill = (categorySelect.value || '').trim() === CATEGORY_UPSKILL;
        if (isMagang) {
          periodContainer.classList.remove('hidden');
          competencyWrapper.classList.remove('hidden');
          titleWrapper.classList.remove('hidden');
          titleLabel.textContent = 'Tanggal Periode *';
          if (!titleInput.placeholder) {
            titleInput.placeholder = 'Contoh: 01 Januari 2025 - 30 Juni 2025';
          }
        } else if (isUpskill) {
          // Untuk Upskilling Reskilling, bidang kompetensi & judul sertifikat tidak digunakan
          periodContainer.classList.add('hidden');
          competencyWrapper.classList.add('hidden');
          titleWrapper.classList.add('hidden');
          titleInput.value = '';
        } else {
          periodContainer.classList.add('hidden');
          competencyWrapper.classList.remove('hidden');
          titleWrapper.classList.remove('hidden');
          titleLabel.textContent = 'Judul Sertifikat *';
          if (titleInput.placeholder === 'Contoh: 01 Januari 2025 - 30 Juni 2025') {
            titleInput.placeholder = 'Masukkan judul sertifikat';
          }
        }
      }

      function updateTitleFromDates() {
        const isMagang = (categorySelect.value || '').trim() === CATEGORY_MAGANG;
        if (!isMagang) return;

        const start = formatDateIndo(startInput.value);
        const end = formatDateIndo(endInput.value);

        if (start && end) {
          titleInput.value = `${start} - ${end}`;
        }
      }

      categorySelect.addEventListener('change', function () {
        updateVisibility();
        updateTitleFromDates();
      });

      startInput.addEventListener('change', updateTitleFromDates);
      endInput.addEventListener('change', updateTitleFromDates);

      document.addEventListener('DOMContentLoaded', function () {
        updateVisibility();
        updateTitleFromDates();
      });

      updateVisibility();
      updateTitleFromDates();
    })();
  </script>
@endsection
