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
            Konfigurasi global platform QRIS, batas kedaluwarsa bawaan, dan informasi operasional.
          </p>
        </div>
        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-md disabled:opacity-50 flex items-center gap-2"
        >
          <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
          <span>Simpan Perubahan</span>
        </button>
      </div>

      <div v-if="loading" class="py-16 flex justify-center text-slate-500">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else class="space-y-6">
        
        <!-- General System Card -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
          <h3 class="text-base font-bold text-white">Konfigurasi Umum & Identitas</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Platform</label>
              <input
                v-model="form['app.name']"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Email Layanan Support</label>
              <input
                v-model="form['app.support_email']"
                type="email"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
          </div>
        </div>

        <!-- QRIS Engine Defaults -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
          <h3 class="text-base font-bold text-white">Default Parameter QRIS Dinamis</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Default Masa Berlaku QRIS (Menit)</label>
              <input
                v-model.number="form['qris.default_expiry_minutes']"
                type="number"
                min="1"
                max="1440"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Default Fee Mode</label>
              <select
                v-model="form['qris.default_fee_mode']"
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
              v-model="form['app.maintenance_mode']"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
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

const form = reactive<Record<string, any>>({
  'app.name': 'PT Kreatif Abadi QRIS Platform',
  'app.support_email': 'support@kreatifabadi.co.id',
  'qris.default_expiry_minutes': 15,
  'qris.default_fee_mode': 'charged_to_customer',
  'app.maintenance_mode': false,
});

const fetchSettings = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/settings');
    const groups = res.data.data;
    for (const grp in groups) {
      for (const item of groups[grp]) {
        form[item.key] = item.value;
      }
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
    toast.success('Pengaturan Disimpan', 'Konfigurasi sistem berhasil diperbarui.');
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>
