<template>
  <AdminLayout>
    <div class="max-w-4xl space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Pengaturan Sistem Platform
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Konfigurasi platform, QRIS statis pembayaran langganan, dan parameter sistem Qmis.
          </p>
        </div>
        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
          <span>Simpan Perubahan</span>
        </button>
      </div>

      <div v-if="loading" class="py-16 flex justify-center text-slate-500">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else class="space-y-6">

        <!-- Platform Static QRIS Configuration (CRITICAL SAAS BILLING) -->
        <div class="bg-slate-950 rounded-2xl border border-indigo-900/60 p-6 space-y-5 relative overflow-hidden shadow-lg shadow-indigo-950/20">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                  Pembayaran Paket Pelanggan
                </span>
                <h3 class="text-base font-bold text-white">QRIS Statis Platform (PT Kreatif Sky Abadi)</h3>
              </div>
              <p class="text-xs text-slate-400 mt-1">
                QRIS statis ini akan diubah otomatis oleh backend menjadi QRIS Dinamis ber-nominal tepat saat pelanggan memilih & membayar paket langganan.
              </p>
            </div>

            <!-- Action buttons in header -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <button
                type="button"
                @click="openQrFileInput"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-sm"
                title="Pilih gambar QRIS dari komputer"
              >
                <Upload class="w-3.5 h-3.5" />
                <span>Upload QRIS</span>
              </button>

              <button
                type="button"
                @click="showCameraScanner = !showCameraScanner"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors"
                title="Pindai QR menggunakan kamera"
              >
                <Camera class="w-3.5 h-3.5" />
                <span>{{ showCameraScanner ? 'Tutup Kamera' : 'Scan Kamera' }}</span>
              </button>

              <button
                type="button"
                @click="loadDefaultPlatformQris"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
                title="Gunakan default template PT Kreatif Sky Abadi"
              >
                <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
                <span>Muat Template</span>
              </button>
            </div>
          </div>

          <!-- Invisible file input for file uploads -->
          <input
            ref="qrFileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleFileSelected"
          />

          <!-- Expandable Camera Scanner Box -->
          <div v-if="showCameraScanner" class="p-4 bg-slate-900/90 rounded-xl border border-indigo-800/60 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Camera class="w-4 h-4 text-indigo-400" />
                <span class="text-xs font-bold text-white">Pemindai Kamera QRIS Statis</span>
              </div>
              <button
                type="button"
                @click="showCameraScanner = false"
                class="p-1 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
            <CameraScanner @scan="handleScannedQris" @scan-success="handleScannedQris" />
          </div>

          <!-- Drag & Drop Upload Zone -->
          <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            class="relative rounded-xl border-2 border-dashed transition-all"
            :class="[
              isDragging
                ? 'border-indigo-500 bg-indigo-950/40'
                : 'border-slate-800 hover:border-indigo-600/50 bg-slate-900/40'
            ]"
          >
            <!-- When image is uploaded and preview is active -->
            <div v-if="uploadedImagePreview" class="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="relative w-16 h-16 rounded-lg bg-black border border-slate-700 overflow-hidden shrink-0 flex items-center justify-center">
                  <img :src="uploadedImagePreview" alt="QRIS Preview" class="w-full h-full object-contain" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-white">{{ uploadedFileName || 'Gambar QRIS Terunggah' }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                      Terbaca & Valid
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Payload EMVCo string dan informasi merchant telah berhasil diekstrak otomatis.
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  @click="openQrFileInput"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors flex items-center gap-1.5"
                >
                  <Upload class="w-3.5 h-3.5" />
                  <span>Ganti Gambar</span>
                </button>
                <button
                  type="button"
                  @click="clearUploadedImage"
                  class="p-1.5 rounded-lg text-xs text-rose-400 hover:bg-rose-950/40 border border-slate-800 transition-colors"
                  title="Hapus gambar preview"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- When no image uploaded yet -->
            <div
              v-else
              @click="openQrFileInput"
              class="p-6 flex flex-col items-center justify-center text-center cursor-pointer group"
            >
              <div class="w-12 h-12 rounded-2xl bg-indigo-950/60 border border-indigo-800/40 text-indigo-400 flex items-center justify-center mb-2.5 group-hover:scale-110 group-hover:bg-indigo-900/60 transition-all">
                <QrCode class="w-6 h-6" />
              </div>
              <div class="text-xs font-semibold text-white group-hover:text-indigo-300 transition-colors">
                <span class="text-indigo-400 underline">Klik untuk Unggah Gambar QRIS</span> atau Tarik & Lepas (Drag & Drop) di sini
              </div>
              <p class="text-[11px] text-slate-500 mt-1 max-w-md">
                Mendukung JPG, PNG, WEBP. Anda juga bisa screenshot QRIS (Win+Shift+S) lalu tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]">Ctrl + V</kbd> (Paste) langsung di halaman ini.
              </p>
            </div>
          </div>

          <!-- Payload Input -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-300">
                Payload String QRIS Statis (EMVCo String)
              </label>
              <div class="flex items-center gap-3">
                <button
                  v-if="form['platform_qris_static']"
                  type="button"
                  @click="copyPayload"
                  class="text-[11px] text-indigo-400 hover:text-indigo-300 flex items-center gap-1 font-medium transition-colors"
                >
                  <Copy class="w-3 h-3" />
                  <span>Salin Payload</span>
                </button>
                <button
                  v-if="form['platform_qris_static']"
                  type="button"
                  @click="clearPayload"
                  class="text-[11px] text-rose-400 hover:text-rose-300 flex items-center gap-1 font-medium transition-colors"
                >
                  <Trash2 class="w-3 h-3" />
                  <span>Bersihkan</span>
                </button>
                <span class="text-[11px] text-slate-400 font-mono">Tag 01=11 (Static)</span>
              </div>
            </div>
            <textarea
              v-model="form['platform_qris_static']"
              rows="3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-white font-mono text-xs outline-none focus:border-indigo-500 transition-colors"
              placeholder="00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204581253033605802ID5920PT KREATIF SKY ABADI6007JAKARTA61051011062070703A016304B835"
              @input="onQrisInputChange"
            ></textarea>
          </div>

          <!-- Merchant Details on QRIS -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Merchant pada QRIS</label>
              <input
                v-model="form['platform_qris_merchant_name']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Kota Merchant</label>
              <input
                v-model="form['platform_qris_merchant_city']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Kode Pos Merchant</label>
              <input
                v-model="form['platform_qris_postal_code']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none focus:border-indigo-500"
              />
            </div>
          </div>

          <!-- Live QRIS Decoded Card -->
          <div v-if="previewResult" class="p-4 rounded-xl border border-slate-800 bg-slate-900/80 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-300">Hasil Pemindaian / Validasi QRIS Platform:</span>
              <span
                :class="[
                  'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                  previewResult.is_valid ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                ]"
              >
                {{ previewResult.is_valid ? 'VALID EMVCo & ASPI' : 'TIDAK VALID' }}
              </span>
            </div>

            <div v-if="previewResult.is_valid" class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
              <div>
                <span class="text-slate-500 block text-[10px]">Merchant Name (Tag 59)</span>
                <span class="font-bold text-white">{{ previewResult.merchant_name || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px]">Merchant City (Tag 60)</span>
                <span class="font-bold text-white">{{ previewResult.merchant_city || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px]">Metode</span>
                <span class="font-bold text-indigo-400 uppercase">{{ previewResult.method || 'Static' }} (01={{ previewResult.point_of_initiation }})</span>
              </div>
              <div>
                <span class="text-slate-500 block text-[10px]">CRC-16 CCITT (Tag 63)</span>
                <span class="font-mono font-bold text-emerald-400">{{ previewResult.crc }} (Valid)</span>
              </div>
            </div>

            <div v-if="previewResult.acquirers?.length" class="pt-2 border-t border-slate-800/80 flex flex-wrap gap-2 items-center text-[11px]">
              <span class="text-slate-500 text-[10px]">Acquirers Terdeteksi:</span>
              <span
                v-for="(acq, idx) in previewResult.acquirers"
                :key="idx"
                class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-[10px] font-mono"
              >
                Tag {{ acq.tag }}: {{ acq.acquirer_name }} ({{ acq.merchant_criteria || 'UMI' }})
              </span>
            </div>

            <div v-if="!previewResult.is_valid && previewResult.errors" class="text-xs text-rose-400 space-y-1">
              <div v-for="(err, idx) in previewResult.errors" :key="idx">&bull; {{ err }}</div>
            </div>
          </div>

          <!-- Automatic Mutation Webhook URL Info -->
          <div class="p-4 rounded-xl border border-indigo-900/60 bg-indigo-950/20 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-indigo-300">URL Webhook Notifikasi Mutasi Pembayaran Otomatis:</span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-400 font-mono">POST Callback</span>
            </div>
            <p class="text-[11px] text-slate-400">
              Gunakan URL ini di layanan mutasi otomatis Anda (seperti Cekmutasi.com, Moota.co, atau aplikasi Webhook Forwarder Notifikasi Android). Ketika pembeli scan QRIS dan uang masuk terdeteksi, sistem otomatis mencocokkan nominal dan melunasi faktur tanpa campur tangan manual.
            </p>
            <div class="flex items-center gap-2">
              <input
                type="text"
                readonly
                :value="mutationWebhookUrl"
                class="w-full px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-900 text-indigo-300 font-mono text-xs outline-none select-all"
              />
              <button
                type="button"
                @click="copyMutationUrl"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shrink-0 flex items-center gap-1"
              >
                <Copy class="w-3.5 h-3.5" />
                <span>Salin URL</span>
              </button>
            </div>
          </div>

          <!-- Google Apps Script Automation Section -->
          <div class="p-4 rounded-xl border border-indigo-900/60 bg-gradient-to-b from-indigo-950/30 to-slate-950 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-900/40 pb-3">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                  GAS
                </div>
                <div>
                  <h4 class="text-xs font-bold text-white flex items-center gap-2">
                    <span>Otomatisasi Google Apps Script (Gmail Notifier)</span>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                      GRATIS 24 JAM
                    </span>
                  </h4>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Membaca email bukti pembayaran masuk (ShopeePay, BCA, Mandiri, GoPay, dll) di cloud Google secara otomatis tanpa perlu HP menyala!
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <a
                  href="https://script.google.com/home/start"
                  target="_blank"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors flex items-center gap-1.5"
                >
                  <ExternalLink class="w-3.5 h-3.5" />
                  <span>Buka script.google.com</span>
                </a>
                <button
                  type="button"
                  @click="showGasCode = !showGasCode"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors flex items-center gap-1.5"
                >
                  <Code2 class="w-3.5 h-3.5" />
                  <span>{{ showGasCode ? 'Tutup Script' : 'Lihat & Salin Script' }}</span>
                </button>
              </div>
            </div>

            <!-- Expandable Script and Guide -->
            <div v-if="showGasCode" class="space-y-3 pt-1">
              <!-- Step-by-step instructions -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 text-[11px]">
                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                  <div class="font-bold text-indigo-400 mb-1">1. Buat Project Baru</div>
                  <p class="text-slate-400">Buka <a href="https://script.google.com/home/start" target="_blank" class="underline text-indigo-300">script.google.com</a>, klik <strong>+ New Project</strong>, hapus kode lama, lalu tempel kode di bawah ini.</p>
                </div>
                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                  <div class="font-bold text-indigo-400 mb-1">2. Pasang Trigger Waktu</div>
                  <p class="text-slate-400">Klik ikon jam/alarm (<strong>Triggers</strong>) di sebelah kiri &rarr; <strong>Add Trigger</strong> &rarr; pilih fungsi <code class="text-slate-200 font-mono">checkIncomingPayments</code>.</p>
                </div>
                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                  <div class="font-bold text-indigo-400 mb-1">3. Atur Setiap Menit</div>
                  <p class="text-slate-400">Pilih <em>Time-driven</em> &rarr; <em>Minutes timer</em> &rarr; <em>Every minute</em>. Klik <strong>Save</strong> dan beri izin akses akun Gmail Anda.</p>
                </div>
              </div>

              <!-- Code Box -->
              <div class="relative rounded-xl border border-slate-800 bg-slate-900/90 overflow-hidden">
                <div class="flex items-center justify-between px-3.5 py-2 bg-slate-950 border-b border-slate-800 text-[11px]">
                  <span class="font-mono text-slate-400">Code.gs &bull; Google Apps Script</span>
                  <button
                    type="button"
                    @click="copyGasCode"
                    class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs flex items-center gap-1.5 transition-colors shadow-sm"
                  >
                    <Check v-if="copiedGas" class="w-3.5 h-3.5 text-emerald-300" />
                    <Copy v-else class="w-3.5 h-3.5" />
                    <span>{{ copiedGas ? 'Tersalin!' : 'Salin Seluruh Script' }}</span>
                  </button>
                </div>
                <pre class="p-3.5 font-mono text-[11px] text-indigo-200 overflow-x-auto max-h-72 leading-relaxed select-all">{{ gasScriptContent }}</pre>
              </div>

              <!-- Testing Webhook Box -->
              <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                  <span class="text-xs font-bold text-white block">Uji Coba Kirim Webhook Mutasi</span>
                  <span class="text-[11px] text-slate-400">Simulasikan notifikasi dana masuk untuk memastikan server merespon dengan benar.</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-500 text-xs">Rp</span>
                    <input
                      v-model.number="testMutationAmount"
                      type="number"
                      placeholder="27750"
                      class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-700 bg-slate-950 text-white font-mono text-xs w-32 outline-none"
                    />
                  </div>
                  <button
                    type="button"
                    @click="sendTestMutation"
                    :disabled="testingMutation"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors flex items-center gap-1.5 disabled:opacity-50"
                  >
                    <Loader2 v-if="testingMutation" class="w-3.5 h-3.5 animate-spin" />
                    <span>{{ testingMutation ? 'Mengirim...' : 'Kirim Tes Webhook' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Aktif Toggle -->
          <div class="flex items-center justify-between pt-2">
            <div>
              <span class="text-xs font-semibold text-white">Aktifkan Pembayaran Langganan via QRIS Dinamis</span>
              <p class="text-[11px] text-slate-400">Jika aktif, invoice pelanggan akan otomatis menyediakan QRIS dinamis berbasis QRIS di atas.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="form['platform_qris_enabled']"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
          </div>
        </div>
        
        <!-- General System Card -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
          <h3 class="text-base font-bold text-white">Konfigurasi Umum & Identitas</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Platform</label>
              <input
                v-model="form['app_name']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Perusahaan Legal</label>
              <input
                v-model="form['company_name']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Email Layanan Support</label>
              <input
                v-model="form['company_email']"
                type="email"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Telepon Kantor</label>
              <input
                v-model="form['company_phone']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
          </div>
        </div>

        <!-- QRIS Engine Defaults -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
          <h3 class="text-base font-bold text-white">Default Parameter QRIS Dinamis Pelanggan</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Default Masa Berlaku QRIS (Menit)</label>
              <input
                v-model.number="form['qris_default_expiry_minutes']"
                type="number"
                min="1"
                max="1440"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Default Fee Mode</label>
              <select
                v-model="form['qris_default_fee_mode']"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              >
                <option value="charged_to_customer">Dibebankan ke Pembeli (Surcharge)</option>
                <option value="absorbed">Dipotong dari Merchant (Absorbed)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Maintenance Mode Toggle -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-white">Mode Pemeliharaan (Maintenance Mode)</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Jika diaktifkan, portal pengguna non-admin akan menampilkan halaman pemeliharaan sementara.
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="form['security_maintenance_mode']"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
          </label>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  Loader2,
  Upload,
  Camera,
  QrCode,
  Sparkles,
  Copy,
  Trash2,
  X,
  ExternalLink,
  Code2,
  Check,
} from 'lucide-vue-next';
import CameraScanner from '../../components/CameraScanner.vue';
import { decodeQrFromFile } from '../../utils/qrDecoder';

const toast = useToastStore();

const loading = ref(true);
const saving = ref(false);
const previewResult = ref<any>(null);

// QR Upload & Scanner State
const qrFileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const showCameraScanner = ref(false);
const uploadedImagePreview = ref<string | null>(null);
const uploadedFileName = ref<string | null>(null);

// Google Apps Script state
const showGasCode = ref(false);
const copiedGas = ref(false);
const testMutationAmount = ref(27750);
const testingMutation = ref(false);

const DEFAULT_PLATFORM_QRIS = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204581253033605802ID5920PT KREATIF SKY ABADI6007JAKARTA61051011062070703A016304B835';

const form = reactive<Record<string, any>>({
  'app_name': 'Qmis',
  'company_name': 'PT Kreatif Sky Abadi',
  'company_email': 'support@kreatifskyabadi.co.id',
  'company_phone': '+62 21 555 0199',
  'platform_qris_static': DEFAULT_PLATFORM_QRIS,
  'platform_qris_merchant_name': 'PT KREATIF SKY ABADI',
  'platform_qris_merchant_city': 'JAKARTA',
  'platform_qris_postal_code': '10110',
  'platform_qris_enabled': true,
  'qris_default_expiry_minutes': 15,
  'qris_default_fee_mode': 'charged_to_customer',
  'security_maintenance_mode': false,
});

let debounceTimer: any = null;

const validateQrisPreview = async (payload: string) => {
  if (!payload || payload.length < 20) {
    previewResult.value = null;
    return;
  }

  try {
    const res = await api.post('/admin/settings/qris-preview', { payload });
    previewResult.value = res.data.data;
    if (res.data.data.merchant_name) {
      form['platform_qris_merchant_name'] = res.data.data.merchant_name;
    }
    if (res.data.data.merchant_city) {
      form['platform_qris_merchant_city'] = res.data.data.merchant_city;
    }
    if (res.data.data.postal_code) {
      form['platform_qris_postal_code'] = res.data.data.postal_code;
    }
  } catch (err: any) {
    previewResult.value = {
      is_valid: false,
      errors: err.response?.data?.data?.errors || [err.response?.data?.message || 'Format QRIS tidak valid'],
    };
  }
};

const onQrisInputChange = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    validateQrisPreview(form['platform_qris_static']);
  }, 400);
};

const openQrFileInput = () => {
  qrFileInput.value?.click();
};

const handleFileSelected = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    processImageFile(file);
  }
  target.value = '';
};

const handleDrop = (e: DragEvent) => {
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) {
    processImageFile(file);
  }
};

const processImageFile = async (file: File) => {
  if (!file.type.startsWith('image/')) {
    toast.error('File Tidak Didukung', 'Silakan pilih file gambar (PNG, JPG, WEBP, atau SVG).');
    return;
  }

  // Create local preview
  const reader = new FileReader();
  reader.onload = () => {
    uploadedImagePreview.value = reader.result as string;
    uploadedFileName.value = file.name;
  };
  reader.readAsDataURL(file);

  try {
    const payload = await decodeQrFromFile(file);
    form['platform_qris_static'] = payload;
    await validateQrisPreview(payload);
    toast.success('QRIS Berhasil Dipindai!', 'Payload string dan identitas merchant berhasil diekstrak dari gambar.');
  } catch (err: any) {
    toast.error('Gagal Mendeteksi QRIS', err.message || 'Pastikan gambar QR terlihat jelas.');
  }
};

const handleScannedQris = async (payload: string) => {
  form['platform_qris_static'] = payload;
  showCameraScanner.value = false;
  await validateQrisPreview(payload);
  toast.success('QRIS Berhasil Dipindai!', 'Payload string dari kamera berhasil diterapkan.');
};

const handlePaste = (e: ClipboardEvent) => {
  const items = e.clipboardData?.items;
  if (!items) return;

  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile();
      if (file) {
        processImageFile(file);
        break;
      }
    }
  }
};

const clearUploadedImage = () => {
  uploadedImagePreview.value = null;
  uploadedFileName.value = null;
};

const copyPayload = () => {
  if (!form['platform_qris_static']) return;
  navigator.clipboard.writeText(form['platform_qris_static']);
  toast.success('Disalin', 'Payload string QRIS telah disalin ke clipboard.');
};

const clearPayload = () => {
  form['platform_qris_static'] = '';
  previewResult.value = null;
  clearUploadedImage();
  toast.info('Dibersihkan', 'Payload QRIS telah dikosongkan.');
};

const mutationWebhookUrl = computed(() => {
  return `${window.location.origin}/api/v1/billing/callbacks/mutation`;
});

const copyMutationUrl = () => {
  navigator.clipboard.writeText(mutationWebhookUrl.value);
  toast.success('Disalin', 'URL Webhook mutasi berhasil disalin ke clipboard.');
};

const gasScriptContent = computed(() => {
  const url = mutationWebhookUrl.value;
  return `/**
 * ============================================================================
 * QMIS PLATFORM - GMAIL PAYMENT MUTATION DETECTOR (GOOGLE APPS SCRIPT)
 * ============================================================================
 * Script ini berjalan otomatis di cloud Google (GRATIS 24 JAM NONSTOP)
 * untuk membaca email bukti uang masuk dari Bank / E-Wallet dan meneruskannya
 * ke sistem Qmis secara realtime.
 * 
 * Mendukung: ShopeePay/Shopee Partner, BCA, Mandiri Livin, GoBiz/GoPay, DANA, LinkAja.
 */

// 1. URL Webhook Qmis Platform Anda (Otomatis Sesuai Domain Ini)
const WEBHOOK_URL = "${url}";

// 2. Filter Pencarian Gmail (Email dari Bank/E-Wallet dalam 1 hari terakhir yang belum dibaca)
const GMAIL_SEARCH_QUERY = 'is:unread (from:shopee OR from:bca OR from:bankmandiri OR from:gopay OR from:dana OR from:linkaja OR subject:"QRIS" OR subject:"Transfer" OR subject:"Dana Masuk" OR subject:"Pembayaran Berhasil")';

function checkIncomingPayments() {
  Logger.log("Memulai pengecekan email transaksi masuk...");
  
  // Ambil thread email yang cocok dan belum dibaca
  const threads = GmailApp.search(GMAIL_SEARCH_QUERY, 0, 10);
  
  if (threads.length === 0) {
    Logger.log("Tidak ada email pembayaran baru.");
    return;
  }
  
  for (let i = 0; i < threads.length; i++) {
    const thread = threads[i];
    const messages = thread.getMessages();
    
    for (let j = 0; j < messages.length; j++) {
      const msg = messages[j];
      
      if (!msg.isUnread()) continue;
      
      const subject = msg.getSubject();
      const body = msg.getPlainBody();
      const sender = msg.getFrom();
      const date = msg.getDate();
      
      Logger.log("Memproses email dari: " + sender + " | Subjek: " + subject);
      
      // Ekstraksi nominal uang dari isi email
      const amount = extractAmountFromBody(body) || extractAmountFromBody(subject);
      
      if (amount && amount > 0) {
        Logger.log("Nominal terdeteksi: Rp " + amount);
        
        // Kirim notifikasi mutasi ke Webhook Qmis
        const success = sendWebhookToQmis({
          amount: amount,
          description: subject,
          sender: sender,
          date: date.toISOString(),
          source: "gmail_google_apps_script"
        });
        
        if (success) {
          // Tandai email sudah dibaca agar tidak diproses berulang
          msg.markRead();
          Logger.log("Email berhasil diproses dan ditandai lunas di Qmis.");
        }
      }
    }
  }
}

/**
 * Ekstraksi angka nominal rupiah dari teks email (misal: "Rp 27.750", "Rp. 27.750,00", "27,750")
 */
function extractAmountFromBody(text) {
  if (!text) return null;
  
  const regexList = [
    /(?:Rp\\.?|IDR)\\s*([0-9]{1,3}(?:\\.[0-9]{3})*(?:,[0-9]{2})?|[0-9]+)/i,
    /(?:sebesar|nominal|total|jumlah)\\s*(?:Rp\\.?|IDR)?\\s*([0-9]{1,3}(?:\\.[0-9]{3})*)/i,
    /([0-9]{1,3}(?:\\.[0-9]{3})+)/
  ];
  
  for (let r = 0; r < regexList.length; r++) {
    const match = text.match(regexList[r]);
    if (match && match[1]) {
      const cleanStr = match[1].replace(/\\./g, '').split(',')[0].trim();
      const num = parseInt(cleanStr, 10);
      if (!isNaN(num) && num > 0) {
        return num;
      }
    }
  }
  return null;
}

/**
 * Mengirim HTTP POST ke Webhook Qmis
 */
function sendWebhookToQmis(payload) {
  const options = {
    method: "post",
    contentType: "application/json",
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };
  
  try {
    const response = UrlFetchApp.fetch(WEBHOOK_URL, options);
    const code = response.getResponseCode();
    const content = response.getContentText();
    Logger.log("Response dari Qmis [" + code + "]: " + content);
    return code >= 200 && code < 300;
  } catch (err) {
    Logger.log("Gagal memanggil Webhook Qmis: " + err.toString());
    return false;
  }
}`;
});

const copyGasCode = async () => {
  await navigator.clipboard.writeText(gasScriptContent.value);
  copiedGas.value = true;
  toast.success('Script Disalin!', 'Kode Google Apps Script berhasil disalin ke clipboard.');
  setTimeout(() => {
    copiedGas.value = false;
  }, 2500);
};

const sendTestMutation = async () => {
  if (!testMutationAmount.value || testMutationAmount.value <= 0) {
    toast.error('Nominal Tidak Valid', 'Masukkan nominal angka yang valid.');
    return;
  }

  testingMutation.value = true;
  try {
    await api.post('/v1/billing/callbacks/mutation', {
      amount: testMutationAmount.value,
      description: 'Uji Coba Webhook Google Apps Script dari Pengaturan Admin',
      source: 'admin_test',
    });

    toast.success('Webhook Diterima!', `Server berhasil mencatat mutasi Rp ${testMutationAmount.value.toLocaleString('id-ID')} dan melunasi faktur terkait.`);
  } catch (err: any) {
    toast.error('Gagal Mengirim Webhook', err.response?.data?.message || 'Tidak ada faktur pending dengan nominal tersebut, atau server error.');
  } finally {
    testingMutation.value = false;
  }
};

const loadDefaultPlatformQris = () => {
  form['platform_qris_static'] = DEFAULT_PLATFORM_QRIS;
  form['platform_qris_merchant_name'] = 'PT KREATIF SKY ABADI';
  form['platform_qris_merchant_city'] = 'JAKARTA';
  form['platform_qris_postal_code'] = '10110';
  form['platform_qris_enabled'] = true;
  clearUploadedImage();
  validateQrisPreview(DEFAULT_PLATFORM_QRIS);
  toast.success('Template Dimuat', 'Template QRIS Statis PT Kreatif Sky Abadi berhasil dimuat.');
};

const fetchSettings = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/settings');
    const groups = res.data.data;
    for (const grp in groups) {
      for (const item of groups[grp]) {
        if (item.type === 'boolean') {
          form[item.key] = item.value === '1' || item.value === true || item.value === 'true';
        } else if (item.type === 'integer') {
          form[item.key] = parseInt(item.value, 10);
        } else {
          form[item.key] = item.value;
        }
      }
    }

    if (form['platform_qris_static']) {
      await validateQrisPreview(form['platform_qris_static']);
    }
  } catch (err) {
    console.error('Failed to load settings:', err);
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await api.post('/admin/settings/update', { settings: form });
    toast.success('Pengaturan Disimpan', 'Konfigurasi QRIS statis & parameter sistem platform berhasil diperbarui.');
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan saat menyimpan pengaturan.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
  window.addEventListener('paste', handlePaste);
});

onUnmounted(() => {
  window.removeEventListener('paste', handlePaste);
});
</script>

