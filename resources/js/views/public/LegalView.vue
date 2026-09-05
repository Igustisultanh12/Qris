<template>
  <PublicLayout>
    <div class="py-16 bg-slate-50 dark:bg-slate-950 min-h-screen">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-12">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800/60 mb-4">
            Legal & Compliance
          </div>
          <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Ketentuan Layanan & Kebijakan Privasi
          </h1>
          <p class="mt-3 text-slate-600 dark:text-slate-400 text-sm sm:text-base">
            Standar Kepatuhan Operasional PT Kreatif Abadi & Regulasi QRIS Bank Indonesia (ASPI).
          </p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Terakhir diperbarui: 1 Januari 2026</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 mb-8">
          <button
            @click="activeTab = 'terms'"
            :class="[
              'pb-4 px-4 font-semibold text-sm transition-all border-b-2 -mb-[2px]',
              activeTab === 'terms'
                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
            ]"
          >
            Syarat & Ketentuan
          </button>
          <button
            @click="activeTab = 'privacy'"
            :class="[
              'pb-4 px-4 font-semibold text-sm transition-all border-b-2 -mb-[2px]',
              activeTab === 'privacy'
                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
            ]"
          >
            Kebijakan Privasi
          </button>
          <button
            @click="activeTab = 'qris'"
            :class="[
              'pb-4 px-4 font-semibold text-sm transition-all border-b-2 -mb-[2px]',
              activeTab === 'qris'
                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
            ]"
          >
            Kepatuhan ASPI & QRIS
          </button>
        </div>

        <!-- Content Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-10 shadow-sm prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed text-sm sm:text-base">
          
          <!-- Tab 1: Terms -->
          <div v-if="activeTab === 'terms'" class="space-y-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Ketentuan Umum</h2>
            <p>
              Selamat datang di PT Kreatif Abadi QRIS Platform ("Platform", "Kami"). Dengan mendaftar, mengakses, atau menggunakan layanan ini, Anda ("Pengguna", "Pelanggan", atau "Merchant") menyetujui untuk terikat dengan seluruh syarat dan ketentuan berikut ini.
            </p>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Hak Akses & Kepemilikan Akun</h2>
            <ul class="list-disc pl-5 space-y-2">
              <li>Pengguna wajib memberikan data legalitas usaha dan kontak yang valid saat pendaftaran.</li>
              <li>Satu akun pelanggan bertanggung jawab penuh atas seluruh sub-merchant dan transaksi yang diproses di bawah akun tersebut.</li>
              <li>Kunci API (API Key) dan rahasia (Secret) merupakan informasi rahasia. Kerugian akibat kebocoran kunci API oleh pengguna menjadi tanggung jawab pengguna sepenuhnya.</li>
            </ul>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Penggunaan Layanan Konversi QRIS</h2>
            <p>
              Platform PT Kreatif Abadi bertindak sebagai penyedia infrastruktur perangkat lunak konversi QRIS statis menjadi dinamis. Platform tidak memegang, menampung, atau mengendapkan dana transaksi pengguna (non-custodial). Seluruh dana dari pembayaran QRIS langsung masuk ke rekening bank/PJP acquirer merchant yang tercantum pada QRIS statis asli.
            </p>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">4. Biaya Layanan & Pembatalan</h2>
            <p>
              Layanan disediakan dengan model langganan bulanan atau tahunan (SaaS). Pembatalan langganan dapat dilakukan kapan saja dan akan berlaku pada akhir periode penagihan yang aktif.
            </p>
          </div>

          <!-- Tab 2: Privacy -->
          <div v-if="activeTab === 'privacy'" class="space-y-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Pengumpulan Informasi</h2>
            <p>
              Kami mengumpulkan informasi yang Anda berikan secara langsung saat membuat akun, mendaftarkan data merchant, atau menghasilkan payload transaksi:
            </p>
            <ul class="list-disc pl-5 space-y-2">
              <li>Informasi identitas: Nama, alamat email, nomor telepon, nama bisnis.</li>
              <li>Data Merchant QRIS: Nama merchant, kota, kode pos, kode QRIS statis, dan Merchant PAN/ID.</li>
              <li>Log Teknis: Alamat IP pemanggil API, User-Agent, riwayat request, dan catatan audit keamanan.</li>
            </ul>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Penggunaan Data</h2>
            <p>
              Data yang dikumpulkan digunakan semata-mata untuk:
            </p>
            <ul class="list-disc pl-5 space-y-2">
              <li>Memvalidasi dan mengonversi payload QRIS sesuai standar EMVCo / ASPI.</li>
              <li>Menyediakan layanan monitoring transaksi dan pengiriman webhook notifikasi.</li>
              <li>Menghitung kuota dan penagihan biaya langganan SaaS.</li>
              <li>Mencegah penipuan dan mematuhi kewajiban regulasi sistem pembayaran di Indonesia.</li>
            </ul>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Keamanan Data</h2>
            <p>
              Seluruh transmisi data dilindungi dengan enkripsi TLS 1.3. Kunci API disimpan dalam basis data menggunakan hashing SHA-256 yang aman. Kunci sesi dan 2FA TOTP dienkripsi menggunakan standar industri.
            </p>
          </div>

          <!-- Tab 3: ASPI / QRIS -->
          <div v-if="activeTab === 'qris'" class="space-y-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Standar EMVCo & ASPI QRIS</h2>
            <p>
              Sistem konversi PT Kreatif Abadi mematuhi spesifikasi standar <em>Quick Response Code Indonesian Standard (QRIS)</em> yang diterbitkan oleh Bank Indonesia dan Asosiasi Sistem Pembayaran Indonesia (ASPI), serta <em>EMVCo QR Code Specification for Payment Systems (Merchant-Presented Mode)</em>.
            </p>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Aturan Perubahan Tag EMVCo</h2>
            <ul class="list-disc pl-5 space-y-2">
              <li><strong>Tag 01 (Point of Initiation Method):</strong> Diubah dari nilai <code>11</code> (statis) menjadi <code>12</code> (dinamis dengan nominal tertentu).</li>
              <li><strong>Tag 54 (Transaction Amount):</strong> Dimasukkan sesuai nominal transaksi yang diminta tanpa desimal ganda yang tidak valid.</li>
              <li><strong>Tag 55, 56, 57 (Tip / Fee Convenience):</strong> Dikonfigurasi sesuai preferensi fixed fee atau percentage fee merchant.</li>
              <li><strong>Tag 63 (CRC16-CCITT):</strong> Dihitung ulang secara deterministik menggunakan polinomial <code>0x1021</code> dengan inisialisasi <code>0xFFFF</code>.</li>
            </ul>

            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Larangan Penyalahgunaan</h2>
            <p>
              Dilarang keras menggunakan platform ini untuk memalsukan identitas merchant (merchant spoofing), melakukan manipulasi NMID / Acquirer tanpa hak, atau melakukan transaksi pencucian uang dan perjudian online. Setiap pelanggaran akan dilaporkan kepada pihak berwenang dan akun akan segera dihentikan.
            </p>
          </div>

        </div>

        <div class="mt-8 text-center">
          <router-link to="/" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline">
            &larr; Kembali ke Beranda
          </router-link>
        </div>

      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import PublicLayout from '../../layouts/PublicLayout.vue';

const activeTab = ref<'terms' | 'privacy' | 'qris'>('terms');
</script>
