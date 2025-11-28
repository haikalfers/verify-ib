<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sertifikat - PT Indo Bismar</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <style>
      @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
      }
      @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      .animate-fade-in-up { animation: fade-in-up 0.7s ease-out forwards; }
      .animate-fade-in { animation: fade-in 0.6s ease-out forwards; }
    </style>
  </head>
  <body class="min-h-screen bg-gradient-to-b from-red-50 to-white text-gray-900">
    <header class="fixed top-0 left-0 right-0 z-40 bg-gradient-to-r from-red-600 via-red-600 to-red-500 text-white shadow">
      <div class="max-w-6xl mx-auto flex items-center justify-between px-4 h-16">
        <div class="flex items-center gap-3">
          <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-10 h-10 object-contain">
          <span class="font-bold text-lg">Verifikasi Sertifikat</span>
        </div>
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
          <a href="#beranda" class="hover:text-red-100">Beranda</a>
          <a href="#tentang" class="hover:text-red-100">Tentang</a>
          <a href="#kontak" class="hover:text-red-100">Kontak</a>
        </nav>
      </div>
    </header>

    <main class="pt-16 md:pt-16" id="beranda">
      {{-- HERO SECTION --}}
      <section class="bg-gradient-to-r from-red-500 via-red-500 to-red-400">
        <div class="max-w-6xl mx-auto px-4 py-10 md:py-16 grid md:grid-cols-2 gap-10 items-center">
          <div class="text-white animate-fade-in-up">
            <p class="inline-flex items-center text-xs font-semibold tracking-wide px-4 py-1 rounded-full bg-red-100/90 text-red-700 mb-4">
              PORTAL VERIFIKASI SERTIFIKAT
            </p>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight mb-4">
              Solusi Digital untuk<br>
              <span class="block text-red-100">Dokumen Sertifikat<br>Anda</span>
            </h1>
            <p class="text-sm md:text-base text-red-50 leading-relaxed mb-6 max-w-xl">
              Platform digital yang memudahkan pengelolaan, verifikasi, dan pengunduhan Sertifikat.
              Membantu lembaga pendidikan dalam memastikan keabsahan dokumen akademik secara efisien.
            </p>
            <div class="flex flex-wrap items-center gap-4">
              <a href="{{ route('public.verify.form') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-red-700 hover:bg-red-800 text-sm md:text-base font-semibold shadow-lg transition">
                <span class="text-lg">🔍</span>
                <span>Verifikasi Sertifikat</span>
              </a>
            </div>
          </div>

          <div class="flex justify-center md:justify-end mt-8 md:mt-0 animate-fade-in" style="animation-delay: 150ms">
            <img src="{{ asset('images/students.png') }}" alt="Ilustrasi siswa" class="w-3/4 max-w-xs md:max-w-md md:w-full object-contain drop-shadow-2xl">
          </div>
        </div>
      </section>

      {{-- Fitur Utama Layanan --}}
      <section id="tentang" class="bg-slate-50 border-y border-red-100">
        <div class="max-w-6xl mx-auto px-4 py-12 md:py-16">
          <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-8">Fitur Utama Layanan</h2>
          <div class="grid gap-6 md:grid-cols-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-2 transition-transform duration-200 hover:-translate-y-1 hover:shadow-md">
              <div class="text-2xl">⚡</div>
              <h3 class="font-semibold text-gray-900">Verifikasi Otomatis</h3>
              <p class="text-sm text-gray-600">Sistem memeriksa keaslian sertifikat langsung dari database perusahaan. Proses berlangsung cepat dan akurat.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-2">
              <div class="text-2xl">🔒</div>
              <h3 class="font-semibold text-gray-900">Keamanan Data Sertifikat</h3>
              <p class="text-sm text-gray-600">Semua data tersimpan dengan enkripsi tingkat tinggi. Akses dan aktivitas tercatat untuk menjaga integritas informasi.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-2">
              <div class="text-2xl">🌐</div>
              <h3 class="font-semibold text-gray-900">Akses Platform 24 Jam</h3>
              <p class="text-sm text-gray-600">Kamu bisa memeriksa sertifikat kapan saja. Platform mendukung akses dari berbagai perangkat.</p>
            </div>
          </div>
        </div>
      </section>

      {{-- 3 STEPS SECTION --}}
      <section class="bg-white">
        <div class="max-w-6xl mx-auto px-4 py-12 md:py-16">
          <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-2">3 Langkah Mudah Memeriksa Sertifikat</h2>
          <p class="text-sm text-center text-gray-600 mb-10 max-w-2xl mx-auto">
            Ikuti prosedur sederhana berikut untuk memverifikasi keaslian Sertifikat Anda secara cepat dan akurat.
          </p>

          <div class="grid gap-10 md:grid-cols-2 items-center">
            <div class="flex justify-center">
              <img src="{{ asset('images/graduate.png') }}" alt="Ilustrasi verifikasi" class="max-w-sm w-full object-contain drop-shadow-xl">
            </div>
            <div class="space-y-6">
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold">1</div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Masukkan Informasi</h3>
                  <p class="text-sm text-gray-600">Isi kolom dengan Nama, Tanggal Lahir, dan Kode Verifikasi untuk mengakses Data Sertifikat.</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold">2</div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Proses Data</h3>
                  <p class="text-sm text-gray-600">Sistem akan memproses informasi Anda secara otomatis dengan cepat dan aman.</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold">3</div>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">Dapatkan Hasil</h3>
                  <p class="text-sm text-gray-600">Jika data valid, sistem akan menampilkan hasil pencarian Data Sertifikat Anda.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- FOOTER BARU --}}
      <footer class="footer" id="footer">
        <div class="container">
          <div class="footer-container">
            <div class="footer-column">
              <img src="{{ asset('images/logo.png') }}" alt="Indo Bismar Logo">
              <h4>IndoBismar <span>Group</span></h4>
              <p>
                Jl. Bendul Merisi Selatan XI No.59-61, Bendul Merisi, Kec.
                Wonocolo, Surabaya, Jawa Timur 60239.
              </p>
              <br />
            </div>
            <div class="footer-column">
              <h4>Useful Link</h4>
              <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#founder">Founder</a></li>
                <li><a href="#products">Product</a></li>
                <li><a href="#section-store">Store</a></li>
                <li><a href="#contact">Contact</a></li>
              </ul>
            </div>
            <div class="footer-column">
              <h4>Contact us</h4>
              <ul>
                <li>
                  <a href="https://www.instagram.com/indobismar.store?utm_source=ig_web_button_share_sheet&igsh=c3c1b2tzNXc3am4="
                    target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
                </li>
                <li>
                  <a href="https://www.tiktok.com/@indobismar.store?is_from_webapp=1&sender_device=pc"
                    target="_blank"><i class="fab fa-tiktok"></i> Tik-Tok</a>
                </li>
                <li>
                  <a href="https://www.youtube.com/@PTINDOBISMARGROUP" target="_blank"><i
                      class="fab fa-youtube"></i> YouTube</a>
                </li>
                <li>
                  <a href="https://www.linkedin.com/company/indobismar/?lipi=urn%3Ali%3Apage%3Ad_flagship3_search_srp_all%3BGO0CMm17TkqdFbnn2Ffbrg%3D%3D"
                    target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a>
                </li>
                <li>
                  <a href="https://wa.me/82335966079" target="_blank"><i class="fab fa-whatsapp"></i>
                    WhatsApp</a>
                </li>
              </ul>
            </div>
            <div class="footer-column">
              <h4>Location</h4>
              <div class="footer-column-map">
                <ul>
                  <li>
                    <iframe
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.3599341010695!2d112.74798849999999!3d-7.3134011999999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fb0f1fc7c151%3A0x879a96237dbe49f5!2sPT.%20Indo%20Bismar!5e0!3m2!1sid!2sid!4v1754551090913!5m2!1sid!2sid"
                      width="600" height="450" style="border: 0" allowfullscreen="" loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"></iframe>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="copyright">
            <p>&copy; 2025. All rights reserved by Indo Bismar Group</p>
          </div>
        </div>
      </footer>
    </main>
  </body>
</html>
