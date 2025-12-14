## Portal Verifikasi Sertifikat – Laravel

Aplikasi full **Laravel** untuk **Portal Verifikasi Sertifikat Indo Bismar** (backend + tampilan publik & admin). Seluruh tampilan menggunakan Blade + Tailwind. Proyek ini menyediakan:

- Halaman publik landing & form verifikasi sertifikat.
- Panel admin untuk mengelola sertifikat, template sertifikat, dan laporan.
- Fitur generate PDF sertifikat berdasarkan template (FPDF/FPDI).
- Sistem **Trash** untuk sertifikat yang dihapus, dengan auto purge setelah 30 hari.

---

## Teknologi Utama

- PHP ^8.2
- Laravel ^12
- MySQL / MariaDB (database default: `sertifikat_db` di `.env`)
- Tailwind CSS (via CDN) untuk tampilan Blade admin & publik
- FPDF + FPDI untuk generate PDF sertifikat

---

## Persiapan & Instalasi

1. **Clone repository** (di luar scope README ini).
2. Masuk ke folder backend:

   ```bash
   cd laravelback
   ```

3. **Install dependency PHP**:

   ```bash
   composer install
   ```

4. **Buat file `.env`** (jika belum ada):

   ```bash
   cp .env.example .env
   ```

5. **Atur konfigurasi database** di `.env` (contoh lokal bawaan):

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sertifikat_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Generate key aplikasi**:

   ```bash
   php artisan key:generate
   ```

7. **Jalankan migrasi (dan seeder jika ada)**:

   ```bash
   php artisan migrate
   ```

---

## Menjalankan Aplikasi Secara Lokal

Di folder `laravelback`:

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://127.0.0.1:8000` (atau base URL sesuai `APP_URL`).

### Route Utama (Publik)

- `GET /` – Landing page publik.
- `GET /verifikasi` – Form verifikasi sertifikat publik.
- `POST /verifikasi` – Proses verifikasi sertifikat.
- `GET /sertifikat/{id}/download` – Download PDF sertifikat publik (jika file tersedia).

### Panel Admin

- `GET /admin` – Redirect otomatis ke `/admin/login` atau `/admin/dashboard` jika sudah login.
- `GET /admin/login` – Form login admin (session berbasis middleware kustom `AdminWebMiddleware`).
- `GET /admin/dashboard` – Dashboard admin (ringkasan data & aksi cepat).
- `GET /admin/certificates` – Kelola sertifikat (listing, filter, pagination, download ZIP per halaman, bulk delete).
- `GET /admin/certificates/create` – Form input sertifikat baru.
- `GET /admin/certificates/import` – Form impor CSV sertifikat.
- `GET /admin/certificates/trash` – Halaman Trash sertifikat (restore / hapus permanen).
- `GET /admin/templates` – Kelola template sertifikat (CRUD, toggle aktif, preview gambar/PDF sebagai background sertifikat).
- `GET /admin/reports` – Laporan sistem (statistik, distribusi, export CSV).

---

## Fitur Utama Backend

### 1. Verifikasi Publik

- Form verifikasi berdasarkan **nama**, **tanggal lahir**, dan **kode verifikasi**.
- Menampilkan status *Sertifikat Valid* / *tidak ditemukan*.
- Jika tersedia `generated_pdf_path` atau `pdf_url`, pengguna dapat:
  - **Lihat PDF** di tab baru.
  - **Download PDF** melalui route publik.

### 2. Manajemen Sertifikat (Admin)

- Input data sertifikat lengkap (nama, TTL, judul, kategori, perusahaan, tanggal terbit, dsb.).
- Nomor sertifikat dan kode verifikasi **dibuat otomatis** berdasarkan perusahaan & tanggal terbit.
- Pilih template aktif untuk **auto-generate PDF** sertifikat.
- Di tabel admin:
  - Tombol **Lihat PDF** dan **Download**.
  - Edit & hapus sertifikat.

### 3. Template Sertifikat (Admin)

- Upload file template (PDF / gambar **PNG/JPG/JPEG**).
- Simpan kategori, deskripsi, status aktif.
- Simpan koordinat teks dalam bentuk **JSON** untuk memposisikan field (nama, judul, tanggal, dsb.) di PDF.
- Di daftar template:
  - Kolom **Preview** menampilkan thumbnail gambar.
  - Klik thumbnail membuka **modal preview** ukuran besar.
  - Tombol Edit / Hapus / Toggle aktif.

### 4. Laporan (Admin)

- Ringkasan:
  - Total sertifikat.
  - Total template aktif.
  - Rata-rata sertifikat per bulan (12 bulan terakhir).
- Distribusi:
  - Sertifikat per kategori.
  - Sertifikat terbit per bulan.
- Export:
  - Tombol **Export CSV** menghasilkan file `laporan-sertifikat-YYYY-MM-DD.csv`.
  - CSV menggunakan delimiter `;` dan format tanggal `dd-mm-YYYY` agar rapi di Excel.

---

## Struktur Direktori Penting

- `app/Http/Controllers/`
  - `PublicVerificationController.php` – Halaman publik & verifikasi.
  - `AdminWebAuthController.php` – Login/logout admin.
  - `AdminDashboardController.php` – Dashboard admin.
  - `AdminCertificateController.php` – CRUD sertifikat (UI admin).
  - `AdminTemplateController.php` – CRUD template (UI admin).
  - `AdminReportController.php` – Laporan & export CSV.
  - `CertificateController.php` – API sertifikat (generate nomor, PDF, download).
- `resources/views/public/` – Blade untuk landing & verifikasi publik.
- `resources/views/admin/` – Blade untuk layout & halaman admin.
- `public/uploads/templates/` – Lokasi file template yang diupload.

---

## Catatan Deploy

- Pastikan permission folder:
  - `storage/`
  - `bootstrap/cache/`
  - `public/uploads/templates/`

- Pada shared hosting (mis. cPanel/Hostinger), arahkan **document root** ke folder `public/` untuk keamanan.

---

## Lisensi

Proyek ini berbasis kerangka kerja Laravel yang dirilis di bawah lisensi [MIT](https://opensource.org/licenses/MIT).
