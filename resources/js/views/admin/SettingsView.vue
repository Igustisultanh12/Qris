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

            <button
              type="button"
              @click="loadDefaultPlatformQris"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors shrink-0"
            >
              Muat Template PT Kreatif Sky Abadi
            </button>
          </div>

          <!-- Payload Input -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-300">
                Payload String QRIS Statis (EMVCo String)
              </label>
              <span class="text-[11px] text-slate-400 font-mono">Tag 01=11 (Static)</span>
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
import { ref, reactive, onMounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import { Loader2 } from 'lucide-vue-next';

const toast = useToastStore();

const loading = ref(true);
const saving = ref(false);
const previewResult = ref<any>(null);

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
    if (res.data.data.merchant_name && !form['platform_qris_merchant_name']) {
      form['platform_qris_merchant_name'] = res.data.data.merchant_name;
    }
    if (res.data.data.merchant_city && !form['platform_qris_merchant_city']) {
      form['platform_qris_merchant_city'] = res.data.data.merchant_city;
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

const loadDefaultPlatformQris = () => {
  form['platform_qris_static'] = DEFAULT_PLATFORM_QRIS;
  form['platform_qris_merchant_name'] = 'PT KREATIF SKY ABADI';
  form['platform_qris_merchant_city'] = 'JAKARTA';
  form['platform_qris_postal_code'] = '10110';
  form['platform_qris_enabled'] = true;
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
});
</script>

