<template>
  <PublicLayout>
    <!-- Hero Section -->
    <section class="relative pt-20 pb-28 overflow-hidden">
      <!-- Gradient glows -->
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/15 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 mb-6">
          <span class="w-2 h-2 rounded-full bg-indigo-600 animate-ping"></span>
          <span>Teknologi Pemrosesan QRIS Terdepan</span>
        </div>

        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.15]">
          Kelola QRIS Anda Lebih Mudah dengan <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-500 bg-clip-text text-transparent">Kreatif QRIS</span>
        </h1>

        <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto leading-relaxed">
          Platform API dan manajemen QRIS untuk kebutuhan bisnis, aplikasi, POS, dan merchant. Ubah QRIS static menjadi dynamic instan dengan nominal transaksi dan biaya layanan yang fleksibel.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
          <router-link
            to="/register"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all"
          >
            <span>Mulai Sekarang</span>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </router-link>

          <router-link
            to="/api-docs"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl border border-slate-300 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-900 text-slate-800 dark:text-slate-200 font-bold text-base transition-all"
          >
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            <span>Lihat Dokumentasi API</span>
          </router-link>
        </div>

        <!-- Sample Dynamic QR Demo Preview Box -->
        <div class="mt-16 max-w-2xl mx-auto rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 text-left">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-rose-500"></div>
              <div class="w-3 h-3 rounded-full bg-amber-500"></div>
              <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
              <span class="text-xs font-mono text-slate-400 ml-2">POST /api/v1/qris/dynamic</span>
            </div>
            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-0.5 rounded-full">201 Created</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6 items-center">
            <div class="font-mono text-xs text-slate-600 dark:text-slate-400 space-y-1 bg-slate-50 dark:bg-slate-950 p-4 rounded-xl">
              <p><span class="text-indigo-500">"merchant_id"</span>: "MC-KREATIF-001",</p>
              <p><span class="text-indigo-500">"amount"</span>: 50000,</p>
              <p><span class="text-indigo-500">"fee"</span>: 1000,</p>
              <p><span class="text-indigo-500">"total"</span>: 51000,</p>
              <p><span class="text-indigo-500">"reference"</span>: "ORDER-12345",</p>
              <p><span class="text-indigo-500">"status"</span>: <span class="text-emerald-500">"generated"</span></p>
            </div>

            <div class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-100 dark:border-slate-800">
              <div class="w-32 h-32 bg-white p-2 rounded-lg shadow-sm flex items-center justify-center">
                <!-- QR illustration -->
                <svg class="w-full h-full text-slate-900" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm10 0h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm4 0h2v2h-2v-2zm2-2h2v2h-2v-2zm0 4h2v2h-2v-2z" />
                </svg>
              </div>
              <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 mt-2">Rp 51.000</span>
              <span class="text-[10px] text-slate-400">Total Pembayaran</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white dark:bg-slate-900/50 border-y border-slate-200/80 dark:border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Fitur Lengkap</h2>
          <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">Semua yang Anda butuhkan untuk automasi QRIS</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="p-8 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Konversi Static ke Dynamic</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
              Algoritma EMVCo TLV parser & CRC16-CCITT akurat menghasilkan payload dinamis dengan nominal pasti tanpa human error.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-6">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Sistem Biaya Layanan (Fee)</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
              Dukungan fee nominal tetap (fixed) atau persentase. Pilihan fee ditanggung merchant atau dibebankan ke pelanggan.
            </p>
          </div>

          <div class="p-8 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center mb-6">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">High-Performance REST API</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
              Dilengkapi autentikasi API Key, Idempotency-Key untuk proteksi duplikasi, rate limiting dinamis, dan webhook realtime.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Cara Kerja</h2>
        <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2 mb-16">3 Langkah Mudah Integrasi QRIS</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
          <div class="relative p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-4xl font-black text-indigo-600/20 mb-4 block">01</span>
            <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Daftar & Masukkan QRIS</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Buat akun bisnis Anda, masukkan string QRIS static atau unggah foto QR code merchant yang sudah Anda miliki.
            </p>
          </div>

          <div class="relative p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-4xl font-black text-indigo-600/20 mb-4 block">02</span>
            <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Generate Dynamic QR</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Gunakan dashboard web atau panggil REST API dengan parameter nominal transaksi dan nomor referensi pesanan.
            </p>
          </div>

          <div class="relative p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-4xl font-black text-indigo-600/20 mb-4 block">03</span>
            <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Pelanggan Bayar Instan</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Pelanggan scan kode QR di aplikasi m-banking atau e-wallet apa pun tanpa perlu mengetik nominal secara manual.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Box -->
    <section class="py-16">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gradient-to-r from-indigo-900 via-indigo-800 to-violet-900 p-8 sm:p-12 text-center text-white relative overflow-hidden shadow-2xl">
          <h2 class="text-2xl sm:text-4xl font-extrabold mb-4">Siap Mengoptimalkan Pembayaran Bisnis Anda?</h2>
          <p class="text-indigo-200 max-w-xl mx-auto text-sm sm:text-base mb-8">
            Daftar sekarang dan nikmati trial 14 hari gratis tanpa kartu kredit. Akses penuh API dan fitur dashboard.
          </p>
          <router-link
            to="/register"
            class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-white text-indigo-950 font-bold hover:bg-indigo-50 transition-all shadow-lg"
          >
            <span>Daftar Akun Bisnis</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </router-link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '../../layouts/PublicLayout.vue';
</script>
