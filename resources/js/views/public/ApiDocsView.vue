<template>
  <PublicLayout>
    <div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Top Header Banner -->
      <div class="mb-10 pb-8 border-b border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mb-3 border border-indigo-200 dark:border-indigo-800/60">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>REST API v1.0 & Webhook Gateway</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
              Dokumentasi Integrasi API Qmis
            </h1>
            <p class="mt-2 text-sm sm:text-base text-slate-600 dark:text-slate-400 max-w-3xl leading-relaxed">
              Panduan integrasi teknis konversi QRIS statis ke dinamis, penarikan status transaksi real-time, serta sistem webhook otomatis saat pembayaran berhasil diselesaikan pelanggan.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <a
              href="/docs/openapi.json"
              target="_blank"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 transition-all shadow-sm"
            >
              <ExternalLink class="w-4 h-4 text-indigo-500" />
              <span>OpenAPI 3.0 Spec</span>
            </a>
            <router-link
              to="/register"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white transition-all shadow-sm shadow-indigo-600/20"
            >
              <Key class="w-4 h-4" />
              <span>Dapatkan Kunci API</span>
            </router-link>
          </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-4 text-xs font-mono text-slate-500">
          <span class="flex items-center gap-1.5">
            <span class="text-slate-400">Base URL:</span>
            <code class="bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md text-indigo-600 dark:text-indigo-400 font-bold">https://api.kreatifskyabadi.co.id/api/v1</code>
          </span>
          <span>•</span>
          <span class="flex items-center gap-1.5">
            <span class="text-slate-400">Format:</span>
            <code class="text-slate-700 dark:text-slate-300">JSON (UTF-8)</code>
          </span>
          <span>•</span>
          <span class="flex items-center gap-1.5">
            <span class="text-slate-400">Auth Header:</span>
            <code class="text-slate-700 dark:text-slate-300">Authorization: Bearer &lt;API_KEY&gt;</code>
          </span>
        </div>
      </div>

      <!-- Main Two-Column Navigation & Content Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Sticky Sidebar Menu -->
        <div class="lg:col-span-3 sticky top-24 space-y-2 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 py-1">Navigasi Dokumentasi</p>
          
          <button
            v-for="item in navSections"
            :key="item.id"
            @click="activeSection = item.id"
            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-left"
            :class="activeSection === item.id 
              ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60' 
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <div class="flex items-center gap-2.5 truncate">
              <component :is="item.icon" class="w-4 h-4 shrink-0" />
              <span class="truncate">{{ item.title }}</span>
            </div>
            <span v-if="item.badge" class="px-1.5 py-0.5 text-[9px] font-mono rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 uppercase shrink-0">
              {{ item.badge }}
            </span>
          </button>
        </div>

        <!-- Right Content Panels -->
        <div class="lg:col-span-9 space-y-12">

          <!-- ========================================================= -->
          <!-- 1. WEBHOOKS & REAL-TIME NOTIFICATION GUIDE (HIGHLIGHTED) -->
          <!-- ========================================================= -->
          <section v-if="activeSection === 'webhooks'" class="space-y-8 animate-fadeIn">
            <div class="p-6 sm:p-8 bg-gradient-to-br from-indigo-900/40 via-slate-900 to-slate-950 rounded-3xl border border-indigo-500/30 text-white shadow-xl">
              <div class="flex items-center gap-2 text-indigo-400 text-xs font-mono font-bold uppercase tracking-wider mb-2">
                <Webhook class="w-4 h-4" />
                <span>Webhooks & Notifikasi Otomatis</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Alur Pembayaran Otomatis via Webhook
              </h2>
              <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-3xl">
                Bagaimana sistem kasir, website, atau aplikasi Anda mengetahui bahwa pelanggan telah sukses membayar QRIS? Sistem Qmis mengirimkan notifikasi <strong>HTTP POST (Webhook)</strong> secara seketika (*real-time*) langsung ke URL server Anda ketika dana terkonfirmasi.
              </p>
            </div>

            <!-- Flow Diagram Illustration -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Zap class="w-5 h-5 text-amber-500" />
                <span>Diagram Alur Pembayaran QRIS Dinamis</span>
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs mx-auto mb-2">1</div>
                  <h4 class="font-bold text-xs text-slate-900 dark:text-white">Pelanggan Scan QR</h4>
                  <p class="text-[11px] text-slate-500 mt-1">Pelanggan scan QRIS dinamis via BCA, Mandiri, GoPay, OVO, DANA, dsb.</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs mx-auto mb-2">2</div>
                  <h4 class="font-bold text-xs text-slate-900 dark:text-white">Pembayaran Sukses</h4>
                  <p class="text-[11px] text-slate-500 mt-1">Saldo nasabah terpotong dan masuk ke rekening PJP acquirer merchant.</p>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800">
                  <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs mx-auto mb-2">3</div>
                  <h4 class="font-bold text-xs text-indigo-700 dark:text-indigo-300">Qmis Kirim Webhook</h4>
                  <p class="text-[11px] text-slate-500 mt-1">Server Qmis menembak HTTP POST ber-tanda tangan HMAC-SHA256 ke URL Anda.</p>
                </div>

                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800">
                  <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs mx-auto mb-2">4</div>
                  <h4 class="font-bold text-xs text-emerald-700 dark:text-emerald-300">Sistem Berubah Bayar</h4>
                  <p class="text-[11px] text-slate-500 mt-1">Backend Anda memvalidasi tanda tangan, mengubah status order jadi <strong>PAID</strong>, & cetak struk.</p>
                </div>
              </div>
            </div>

            <!-- Webhook Headers & Security -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <ShieldCheck class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                <span>Header Keamanan Webhook</span>
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Setiap request webhook yang dikirim oleh Qmis menyertakan header digital signature untuk memastikan request benar-benar berasal dari sistem resmi Qmis dan bukan dari pihak luar:
              </p>

              <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                  <thead class="bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                      <th class="py-2.5 px-4 font-mono">Header</th>
                      <th class="py-2.5 px-4">Deskripsi</th>
                      <th class="py-2.5 px-4">Contoh Nilai</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-[11px]">
                    <tr>
                      <td class="py-2.5 px-4 font-bold text-indigo-600">X-Qmis-Signature</td>
                      <td class="py-2.5 px-4 font-sans text-slate-600 dark:text-slate-400">Tanda tangan HMAC-SHA256 dari raw payload JSON menggunakan Webhook Secret Anda.</td>
                      <td class="py-2.5 px-4 text-slate-500 truncate max-w-xs">e3b0c44298fc1c149afbf4c8996...</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 px-4 font-bold text-indigo-600">X-Signature-SHA256</td>
                      <td class="py-2.5 px-4 font-sans text-slate-600 dark:text-slate-400">Alias standar untuk kompatibilitas gateway pembayaran internasional.</td>
                      <td class="py-2.5 px-4 text-slate-500 truncate max-w-xs">e3b0c44298fc1c149afbf4c8996...</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 px-4 font-bold text-indigo-600">X-Qmis-Event</td>
                      <td class="py-2.5 px-4 font-sans text-slate-600 dark:text-slate-400">Jenis event kejadian (misal: <code>transaction.paid</code>, <code>transaction.expired</code>).</td>
                      <td class="py-2.5 px-4 text-emerald-600 font-bold">transaction.paid</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 px-4 font-bold text-indigo-600">X-Qmis-Delivery</td>
                      <td class="py-2.5 px-4 font-sans text-slate-600 dark:text-slate-400">UUID unik pengiriman webhook untuk kebutuhan deduplikasi / idempotency di server Anda.</td>
                      <td class="py-2.5 px-4 text-slate-500">9c1b3e94-1a2c-4f8e...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Webhook Payload Format Sample -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <Code class="w-5 h-5 text-indigo-500" />
                  <span>Struktur JSON Payload (Event: transaction.paid)</span>
                </h3>
                <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-full font-bold">
                  Content-Type: application/json
                </span>
              </div>

              <pre class="p-5 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>{
  "event": "transaction.paid",
  "timestamp": "2026-09-05T10:45:30+07:00",
  "data": {
    "transaction_id": "TX-20260905-ABCD",
    "uuid": "7a9b1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
    "reference": "INV-ORDER-202609-001",
    "amount": 50000,
    "fee": 1500,
    "total": 51500,
    "status": "paid",
    "paid_at": "2026-09-05T10:45:28+07:00"
  }
}</code></pre>
            </div>

            <!-- Signature Verification Code Example Tabs -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  Contoh Kode Verifikasi Signature di Server Anda
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                  Pilih bahasa pemrograman backend Anda untuk melihat cara memverifikasi tanda tangan dan menangani event <code>transaction.paid</code>:
                </p>
              </div>

              <div class="bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between px-4 py-3 bg-slate-900/80 border-b border-slate-800">
                  <div class="flex gap-1.5">
                    <button
                      v-for="l in webhookLangs"
                      :key="l.id"
                      @click="activeWebhookLang = l.id"
                      class="px-3 py-1 rounded-lg text-xs font-mono font-semibold transition-colors"
                      :class="activeWebhookLang === l.id ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                    >
                      {{ l.name }}
                    </button>
                  </div>

                  <button
                    @click="copyCode(webhookSnippets[activeWebhookLang])"
                    class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5"
                  >
                    <Copy class="w-3.5 h-3.5" />
                    <span>Salin Kode</span>
                  </button>
                </div>

                <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed"><code>{{ webhookSnippets[activeWebhookLang] }}</code></pre>
              </div>

              <!-- Important Best Practices Note -->
              <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-200 space-y-1.5">
                <p class="font-bold flex items-center gap-1.5">
                  <AlertTriangle class="w-4 h-4 text-amber-600" />
                  <span>Aturan Wajib Saat Menerima Webhook:</span>
                </p>
                <ul class="list-disc pl-5 space-y-1 text-slate-600 dark:text-slate-300">
                  <li><strong>Return 200 OK Cepat:</strong> Selalu kirimkan respons HTTP <code>200 OK</code> dengan segera (&lt; 3 detik). Jika pemrosesan order Anda berat, masukkan ke antrean latar belakang (*background job*).</li>
                  <li><strong>Idempotency:</strong> Periksa apakah nomor <code>reference</code> atau <code>transaction_id</code> sudah pernah ditandai lunas sebelumnya agar tidak terjadi pengiriman barang ganda.</li>
                  <li><strong>Gunakan Raw Body:</strong> Saat menghitung HMAC-SHA256, gunakan string *raw body* asli yang diterima, jangan re-encode dari array objek JSON yang telah di-parse.</li>
                </ul>
              </div>
            </div>
          </section>

          <!-- ========================================================= -->
          <!-- 2. SIMULATE PAYMENT SANDBOX ENDPOINT -->
          <!-- ========================================================= -->
          <section v-if="activeSection === 'simulate'" class="space-y-8 animate-fadeIn">
            <div class="p-6 sm:p-8 bg-slate-900 rounded-3xl border border-slate-800 text-white shadow-xl">
              <div class="flex items-center gap-2 text-emerald-400 text-xs font-mono font-bold uppercase tracking-wider mb-2">
                <Play class="w-4 h-4" />
                <span>Testing Sandbox & Simulasi</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Simulasi Pembayaran (Mark as Paid)
              </h2>
              <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-3xl">
                Gunakan endpoint ini di lingkungan *development* / sandbox untuk menguji alur webhook dan otomatisasi sistem Anda tanpa harus mentransfer uang nyata. Memanggil endpoint ini akan langsung mengubah status transaksi menjadi <code>paid</code> dan memicu webhook <code>transaction.paid</code>.
              </p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">POST</span>
                  <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/qris/{id}/simulate-paid</code>
                </div>
                <p class="text-xs text-slate-500">
                  Parameter <code>{id}</code> dapat berupa <code>transaction_number</code> (misal: <code>TX-20260905-XXXX</code>), <code>uuid</code>, atau <code>reference</code> order Anda.
                </p>
              </div>

              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Contoh Pemanggilan cURL</h4>
                <pre class="p-4 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>curl -X POST https://api.kreatifskyabadi.co.id/api/v1/qris/TX-20260905-ABCD/simulate-paid \
  -H "Authorization: Bearer ka_live_YOUR_API_KEY" \
  -H "Accept: application/json"</code></pre>
              </div>

              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Contoh Respons Sukses (200 OK)</h4>
                <pre class="p-4 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>{
  "success": true,
  "message": "Payment successfully simulated. Transaction status changed to PAID and webhook event transaction.paid has been dispatched.",
  "data": {
    "transaction_id": "TX-20260905-ABCD",
    "reference": "INV-ORDER-001",
    "amount": 50000,
    "fee_amount": 0,
    "total_amount": 50000,
    "status": "paid",
    "paid_at": "2026-09-05T10:48:12+07:00"
  }
}</code></pre>
              </div>
            </div>
          </section>

          <!-- ========================================================= -->
          <!-- 3. GENERATE DYNAMIC QRIS ENDPOINT -->
          <!-- ========================================================= -->
          <section v-if="activeSection === 'create-qris'" class="space-y-8 animate-fadeIn">
            <div class="p-6 sm:p-8 bg-slate-900 rounded-3xl border border-slate-800 text-white shadow-xl">
              <div class="flex items-center gap-2 text-indigo-400 text-xs font-mono font-bold uppercase tracking-wider mb-2">
                <QrCode class="w-4 h-4" />
                <span>Core Engine</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Membuat QRIS Dinamis (Generate Dynamic QR)
              </h2>
              <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-3xl">
                Mengonversi QRIS statis toko menjadi QRIS dinamis baru dengan nominal rupiah otomatis dan opsi biaya layanan (*convenience fee*).
              </p>
            </div>

            <!-- Endpoint Specification Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
              <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                <div>
                  <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">POST</span>
                    <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/qris/dynamic</code>
                  </div>
                  <p class="text-xs text-slate-500">
                    Menghasilkan payload EMVCo dinamis (Tag 01 = 12), nominal di Tag 54, kalkulasi CRC16 baru, serta SVG / PNG QR code siap tampil.
                  </p>
                </div>

                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Required Headers</h4>
                  <div class="bg-slate-50 dark:bg-slate-950 rounded-xl p-3 text-xs font-mono space-y-1.5 border border-slate-100 dark:border-slate-800">
                    <p><span class="text-indigo-500 font-bold">Authorization</span>: Bearer &lt;YOUR_API_KEY&gt;</p>
                    <p><span class="text-indigo-500 font-bold">Content-Type</span>: application/json</p>
                    <p><span class="text-indigo-500 font-bold">Idempotency-Key</span>: &lt;UNIQUE_REQUEST_ID&gt; <span class="text-slate-400">(Opsional)</span></p>
                  </div>
                </div>

                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Body Parameters (JSON)</h4>
                  <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">merchant_id</span>
                        <span class="text-rose-500 font-bold ml-1">*</span>
                        <p class="text-slate-400 text-[11px]">Kode unik merchant (misal: MC-QMIS-001)</p>
                      </div>
                      <span class="font-mono text-slate-400">string</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">amount</span>
                        <span class="text-rose-500 font-bold ml-1">*</span>
                        <p class="text-slate-400 text-[11px]">Nominal rupiah transaksi (min. 1000)</p>
                      </div>
                      <span class="font-mono text-slate-400">integer</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">reference</span>
                        <span class="text-rose-500 font-bold ml-1">*</span>
                        <p class="text-slate-400 text-[11px]">ID unik invoice / tagihan dari toko Anda</p>
                      </div>
                      <span class="font-mono text-slate-400">string</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">fee_type</span>
                        <p class="text-slate-400 text-[11px]">fixed, percentage, atau none</p>
                      </div>
                      <span class="font-mono text-slate-400">string</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">fee_value</span>
                        <p class="text-slate-400 text-[11px]">Besaran biaya nominal atau persentase</p>
                      </div>
                      <span class="font-mono text-slate-400">numeric</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                      <div>
                        <span class="font-mono font-bold text-indigo-600">expiry_minutes</span>
                        <p class="text-slate-400 text-[11px]">Masa kadaluarsa QR dalam menit (default: 15)</p>
                      </div>
                      <span class="font-mono text-slate-400">integer</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Code Snippet Box -->
              <div class="bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between px-4 py-3 bg-slate-900/80 border-b border-slate-800">
                  <div class="flex gap-1.5">
                    <button
                      v-for="tab in dynamicTabs"
                      :key="tab.id"
                      @click="activeDynamicTab = tab.id"
                      class="px-3 py-1 rounded-lg text-xs font-mono font-semibold transition-colors"
                      :class="activeDynamicTab === tab.id ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                    >
                      {{ tab.name }}
                    </button>
                  </div>

                  <button
                    @click="copyCode(dynamicSnippets[activeDynamicTab])"
                    class="text-xs text-slate-400 hover:text-white flex items-center gap-1"
                  >
                    <Copy class="w-3.5 h-3.5" />
                    <span>Salin</span>
                  </button>
                </div>

                <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed"><code>{{ dynamicSnippets[activeDynamicTab] }}</code></pre>
              </div>
            </div>
          </section>

          <!-- ========================================================= -->
          <!-- 4. CHECK STATUS & CANCEL TRANSACTION -->
          <!-- ========================================================= -->
          <section v-if="activeSection === 'check-status'" class="space-y-8 animate-fadeIn">
            <div class="p-6 sm:p-8 bg-slate-900 rounded-3xl border border-slate-800 text-white shadow-xl">
              <div class="flex items-center gap-2 text-indigo-400 text-xs font-mono font-bold uppercase tracking-wider mb-2">
                <RefreshCw class="w-4 h-4" />
                <span>Polling & Status Tracking</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Cek Status Transaksi & Pembatalan QRIS
              </h2>
              <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-3xl">
                Selain menerima Webhook, Anda dapat melakukan polling periodik atau mengecek status terkini transaksi pembayaran kapan saja.
              </p>
            </div>

            <!-- Get Single Transaction Detail -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">GET</span>
                <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/transactions/{id}</code>
              </div>
              <p class="text-xs text-slate-500">
                Mengambil status transaksi berdasarkan <code>transaction_number</code> atau <code>uuid</code>.
              </p>

              <pre class="p-4 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>curl -X GET https://api.kreatifskyabadi.co.id/api/v1/transactions/TX-20260905-ABCD \
  -H "Authorization: Bearer ka_live_YOUR_API_KEY"</code></pre>
            </div>

            <!-- Cancel Transaction -->
            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">POST</span>
                <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/qris/{id}/cancel</code>
              </div>
              <p class="text-xs text-slate-500">
                Membatalkan QRIS yang belum kadaluarsa sehingga tidak dapat diproses lagi oleh nasabah.
              </p>

              <pre class="p-4 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>curl -X POST https://api.kreatifskyabadi.co.id/api/v1/qris/TX-20260905-ABCD/cancel \
  -H "Authorization: Bearer ka_live_YOUR_API_KEY"</code></pre>
            </div>
          </section>

          <!-- ========================================================= -->
          <!-- 5. VALIDATE & PARSE QRIS STATIS -->
          <!-- ========================================================= -->
          <section v-if="activeSection === 'validate-qris'" class="space-y-8 animate-fadeIn">
            <div class="p-6 sm:p-8 bg-slate-900 rounded-3xl border border-slate-800 text-white shadow-xl">
              <div class="flex items-center gap-2 text-indigo-400 text-xs font-mono font-bold uppercase tracking-wider mb-2">
                <ShieldCheck class="w-4 h-4" />
                <span>Validasi EMVCo TLV</span>
              </div>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Validasi & Dekonstruksi QRIS Statis
              </h2>
              <p class="mt-2 text-sm text-slate-300 leading-relaxed max-w-3xl">
                Memeriksa apakah string payload QRIS statis dari bank atau e-wallet mematuhi standar nasional Bank Indonesia dan mengekstrak informasi toko (Nama, Kota, MCC, PAN).
              </p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">POST</span>
                <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/qris/validate</code>
              </div>

              <pre class="p-4 bg-slate-950 rounded-xl text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed border border-slate-800"><code>curl -X POST https://api.kreatifskyabadi.co.id/api/v1/qris/validate \
  -H "Authorization: Bearer ka_live_YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "qris": "00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5923KREATIF SKY ABADI STORE6013JAKARTA PUSAT61051011062070703A0163046155"
  }'</code></pre>
            </div>
          </section>

        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import PublicLayout from '../../layouts/PublicLayout.vue';
import { useToastStore } from '../../stores/toast';
import {
  Webhook,
  Play,
  QrCode,
  RefreshCw,
  ShieldCheck,
  Zap,
  Code,
  Copy,
  ExternalLink,
  Key,
  AlertTriangle,
} from 'lucide-vue-next';

const toast = useToastStore();
const activeSection = ref('webhooks');
const activeWebhookLang = ref('php');
const activeDynamicTab = ref('curl');

const navSections = [
  { id: 'webhooks', title: 'Webhook & Notifikasi Bayar', icon: Webhook, badge: 'Wajib' },
  { id: 'simulate', title: 'Simulasi Pembayaran (Sandbox)', icon: Play, badge: 'Testing' },
  { id: 'create-qris', title: 'Buat QRIS Dinamis', icon: QrCode, badge: 'Core' },
  { id: 'check-status', title: 'Cek Status & Batalkan QR', icon: RefreshCw },
  { id: 'validate-qris', title: 'Validasi QRIS Statis', icon: ShieldCheck },
];

const webhookLangs = [
  { id: 'php', name: 'PHP (Native / Laravel)' },
  { id: 'express', name: 'Node.js (Express)' },
  { id: 'python', name: 'Python (Flask / FastAPI)' },
];

const dynamicTabs = [
  { id: 'curl', name: 'cURL' },
  { id: 'php', name: 'PHP' },
  { id: 'js', name: 'Node.js' },
  { id: 'python', name: 'Python' },
];

const dynamicSnippets: Record<string, string> = {
  curl: `curl -X POST https://api.kreatifskyabadi.co.id/api/v1/qris/dynamic \\
  -H "Authorization: Bearer ka_live_YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: ORD-9988-ABC" \\
  -d '{
    "merchant_id": "MC-QMIS-001",
    "amount": 50000,
    "reference": "INV-202609-001",
    "fee_type": "fixed",
    "fee_value": 1000,
    "fee_mode": "charged_to_customer",
    "expiry_minutes": 15
  }'`,

  php: `<?php
$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.kreatifskyabadi.co.id/api/v1/qris/dynamic",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode([
    "merchant_id" => "MC-QMIS-001",
    "amount" => 50000,
    "reference" => "INV-202609-001",
    "fee_type" => "fixed",
    "fee_value" => 1000
  ]),
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer ka_live_YOUR_API_KEY",
    "Content-Type: application/json",
    "Idempotency-Key: " . uniqid("ord_")
  ],
]);

$response = curl_exec($curl);
$data = json_decode($response, true);
echo "QRIS Dynamic: " . $data['data']['qris_dynamic'];`,

  js: `const axios = require('axios');

async function createDynamicQris() {
  const response = await axios.post(
    'https://api.kreatifskyabadi.co.id/api/v1/qris/dynamic',
    {
      merchant_id: 'MC-QMIS-001',
      amount: 50000,
      reference: 'INV-202609-001',
      fee_type: 'fixed',
      fee_value: 1000
    },
    {
      headers: {
        'Authorization': 'Bearer ka_live_YOUR_API_KEY',
        'Idempotency-Key': 'ORD-' + Date.now()
      }
    }
  );

  console.log('Dynamic QR String:', response.data.data.qris_dynamic);
  console.log('SVG QR:', response.data.data.qr_svg);
}

createDynamicQris();`,

  python: `import requests

url = "https://api.kreatifskyabadi.co.id/api/v1/qris/dynamic"

headers = {
    "Authorization": "Bearer ka_live_YOUR_API_KEY",
    "Content-Type": "application/json",
    "Idempotency-Key": "ORD-12345"
}

payload = {
    "merchant_id": "MC-QMIS-001",
    "amount": 50000,
    "reference": "INV-202609-001",
    "fee_type": "fixed",
    "fee_value": 1000
}

response = requests.post(url, json=payload, headers=headers)
print(response.json())`,
};

const webhookSnippets: Record<string, string> = {
  php: `<?php
// webhook.php - Endpoint penerima webhook di server Anda

$webhookSecret = 'whsec_RAHASIA_WEBHOOK_DARI_DASHBOARD';

// 1. Ambil raw payload dan header signature
$rawPayload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_QMIS_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE_SHA256'] ?? '';

// 2. Hitung ulang signature HMAC-SHA256
$calculatedSignature = hash_hmac('sha256', $rawPayload, $webhookSecret);

// 3. Verifikasi keabsahan signature (Timing-Attack Safe)
if (!hash_equals($calculatedSignature, $signatureHeader)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// 4. Parse payload JSON
$eventData = json_decode($rawPayload, true);
$event = $eventData['event'] ?? '';
$data = $eventData['data'] ?? [];

// 5. Tangani event pembayaran sukses
if ($event === 'transaction.paid') {
    $orderRef = $data['reference'];        // ID Order Anda
    $amountPaid = $data['total'];          // Total nominal yang dibayar
    $transactionId = $data['transaction_id']; // ID Transaksi Qmis
    $paidAt = $data['paid_at'];

    // TODO: Update status order di database Anda menjadi 'PAID'
    // Contoh: DB::table('orders')->where('order_id', $orderRef)->update(['status' => 'PAID']);
    // Kirim notifikasi WhatsApp ke pembeli / cetak struk kasir
}

// 6. WAJIB return respons 200 OK
http_response_code(200);
echo json_encode(['status' => 'success']);`,

  express: `// webhook.js - Node.js Express server
const express = require('express');
const crypto = require('crypto');

const app = express();
const WEBHOOK_SECRET = 'whsec_RAHASIA_WEBHOOK_DARI_DASHBOARD';

// PENTING: Gunakan express.raw atau express.json dengan opsi verify untuk mendapatkan raw body
app.post('/webhook/qmis', express.raw({ type: 'application/json' }), (req, res) => {
  const rawBody = req.body.toString('utf8');
  const signature = req.headers['x-qmis-signature'] || req.headers['x-signature-sha256'];

  // Hitung HMAC-SHA256
  const expectedSignature = crypto
    .createHmac('sha256', WEBHOOK_SECRET)
    .update(rawBody)
    .digest('hex');

  // Bandingkan secara aman
  if (signature !== expectedSignature) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  const payload = JSON.parse(rawBody);

  // Tangani event transaksi berhasil dibayar
  if (payload.event === 'transaction.paid') {
    const { reference, total, transaction_id } = payload.data;
    console.log(\`[QRIS LUNAS] Order \${reference} sejumlah Rp\${total} telah dibayar!\`);

    // TODO: Update status invoice Anda ke database menjadi 'PAID'
  }

  // Balas dengan status 200 OK
  res.status(200).json({ received: true });
});

app.listen(3000, () => console.log('Webhook receiver running on port 3000'));`,

  python: `# webhook.py - Python FastAPI / Flask webhook receiver
import hmac
import hashlib
from fastapi import FastAPI, Request, HTTPException, status

app = FastAPI()
WEBHOOK_SECRET = "whsec_RAHASIA_WEBHOOK_DARI_DASHBOARD"

@app.post("/webhook/qmis")
async def receive_webhook(request: Request):
    raw_body = await request.body()
    signature_header = request.headers.get("x-qmis-signature") or request.headers.get("x-signature-sha256")

    if not signature_header:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Missing signature")

    # Hitung HMAC-SHA256
    computed_sig = hmac.new(
        WEBHOOK_SECRET.encode("utf-8"),
        raw_body,
        hashlib.sha256
    ).hexdigest()

    if not hmac.compare_digest(computed_sig, signature_header):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid signature")

    payload = await request.json()

    if payload.get("event") == "transaction.paid":
        data = payload.get("data", {})
        reference = data.get("reference")
        total = data.get("total")
        print(f"[PAID] Order {reference} senilai Rp {total} telah berhasil dibayar!")

        # TODO: Update database status order -> PAID

    return {"status": "success"}`,
};

const copyCode = (code: string) => {
  navigator.clipboard.writeText(code);
  toast.success('Disalin!', 'Kode telah disalin ke clipboard Anda.');
};
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
