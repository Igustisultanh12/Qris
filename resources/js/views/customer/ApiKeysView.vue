<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            API Keys & Integrasi
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Kunci autentikasi untuk memanggil REST API v1 dari aplikasi kasir POS, backend, atau sistem mobile.
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Buat API Key Baru</span>
        </button>
      </div>

      <!-- Quick Docs Info Banner -->
      <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between gap-4 text-xs text-indigo-900 dark:text-indigo-300">
        <div class="flex items-center gap-3">
          <Key class="w-5 h-5 text-indigo-600 shrink-0" />
          <span>Sertakan header <code>X-API-Key</code> dan <code>X-API-Secret</code> di setiap pemanggilan endpoint <code>/api/v1/*</code>.</span>
        </div>
        <router-link to="/api-docs" class="font-semibold underline shrink-0 hover:text-indigo-950 dark:hover:text-indigo-100">
          Baca Dokumentasi API &rarr;
        </router-link>
      </div>

      <!-- Keys Table -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div v-if="loading" class="py-16 flex justify-center text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin" />
        </div>

        <div v-else-if="apiKeys.length === 0" class="py-16 text-center text-slate-500 dark:text-slate-400 text-sm">
          <Key class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
          Belum ada API Key aktif. Buat kunci pertama Anda untuk mulai mengintegrasikan sistem.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 font-medium">Nama Kunci</th>
                <th class="py-3.5 px-4 font-medium">Prefix Key</th>
                <th class="py-3.5 px-4 font-medium">Rate Limit</th>
                <th class="py-3.5 px-4 font-medium">Status</th>
                <th class="py-3.5 px-4 font-medium">Terakhir Digunakan</th>
                <th class="py-3.5 px-4 font-medium">Dibuat</th>
                <th class="py-3.5 px-4 font-medium text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="key in apiKeys" :key="key.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                  {{ key.name }}
                </td>
                <td class="py-3.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-400">
                  {{ key.key_prefix }}••••••••
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                  <span class="font-bold text-slate-900 dark:text-white">{{ key.rate_limit_rpm }}</span> rpm
                </td>
                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-xs font-semibold',
                      key.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400' : 'bg-rose-100 text-rose-700'
                    ]"
                  >
                    {{ key.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ key.last_used_at ? formatDate(key.last_used_at) : 'Belum pernah' }}
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ formatDate(key.created_at) }}
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="revokeKey(key.id)"
                    class="text-xs font-semibold text-rose-600 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                    title="Cabut Kunci"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

      <!-- Create API Key Modal -->
      <Modal :is-open="showCreateModal" title="Buat API Key Baru" max-width="max-w-lg" @close="showCreateModal = false">
        <form @submit.prevent="createApiKey" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Identifier</label>
            <input
              v-model="newKey.name"
              type="text"
              required
              placeholder="Backend POS Server / Mobile App"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Batas Request (Rate Limit)</label>
            <select
              v-model.number="newKey.rate_limit_rpm"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            >
              <option :value="60">60 Request / Menit (Standar)</option>
              <option :value="120">120 Request / Menit (Menengah)</option>
              <option :value="300">300 Request / Menit (Tinggi)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">IP Whitelist (Opsional)</label>
            <input
              v-model="newKey.ip_whitelist"
              type="text"
              placeholder="103.20.10.5, 103.20.10.6 (Kosongkan jika semua IP)"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            />
            <p class="text-[11px] text-slate-400 mt-1">Pisahkan dengan koma jika lebih dari satu alamat IP.</p>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting || !newKey.name"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
              <span>Buat Kunci</span>
            </button>
          </div>
        </form>
      </Modal>

      <!-- Reveal Plain Secret Modal (Shown Only Once) -->
      <Modal :is-open="showRevealModal" title="Simpan API Key & Secret Anda" max-width="max-w-xl" :show-close="false">
        <div class="space-y-5">
          <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-3">
            <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <strong>PERHATIAN PENTING:</strong> Kunci rahasia (Secret Key) hanya akan ditampilkan <strong>SATU KALI SAJA</strong>. Salin dan simpan di file <code>.env</code> server Anda dengan aman.
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">X-API-Key</label>
            <div class="flex items-center gap-2">
              <input
                :value="createdCredentials?.api_key"
                readonly
                class="w-full px-3.5 py-2 font-mono text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
              />
              <button
                @click="copyText(createdCredentials?.api_key, 'API Key disalin')"
                class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1 shrink-0"
              >
                <Copy class="w-3.5 h-3.5" />
                <span>Salin</span>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">X-API-Secret</label>
            <div class="flex items-center gap-2">
              <input
                :value="createdCredentials?.api_secret"
                readonly
                class="w-full px-3.5 py-2 font-mono text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
              />
              <button
                @click="copyText(createdCredentials?.api_secret, 'API Secret disalin')"
                class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1 shrink-0"
              >
                <Copy class="w-3.5 h-3.5" />
                <span>Salin</span>
              </button>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
            <button
              @click="closeRevealModal"
              class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-md"
            >
              Saya Sudah Menyimpan Kunci Ini
            </button>
          </div>
        </div>
      </Modal>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import Modal from '../../components/Modal.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  Key,
  Plus,
  Trash2,
  AlertTriangle,
  Copy,
  Loader2,
} from 'lucide-vue-next';

const toast = useToastStore();

const apiKeys = ref<any[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);
const showRevealModal = ref(false);
const submitting = ref(false);
const createdCredentials = ref<any>(null);

const newKey = reactive({
  name: '',
  rate_limit_rpm: 60,
  ip_whitelist: '',
});

const fetchKeys = async () => {
  loading.value = true;
  try {
    const res = await api.get('/customer/api-keys');
    apiKeys.value = res.data.data;
  } catch (err) {
    console.error('Failed to load API keys:', err);
  } finally {
    loading.value = false;
  }
};

const createApiKey = async () => {
  submitting.value = true;
  try {
    const ips = newKey.ip_whitelist
      ? newKey.ip_whitelist.split(',').map((s) => s.trim()).filter(Boolean)
      : null;

    const res = await api.post('/customer/api-keys', {
      name: newKey.name,
      rate_limit_rpm: newKey.rate_limit_rpm,
      ip_whitelist: ips,
    });

    createdCredentials.value = res.data.data;
    showCreateModal.value = false;
    showRevealModal.value = true;
    newKey.name = '';
    newKey.ip_whitelist = '';
  } catch (err: any) {
    toast.error('Gagal Membuat Key', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    submitting.value = false;
  }
};

const closeRevealModal = () => {
  showRevealModal.value = false;
  createdCredentials.value = null;
  fetchKeys();
};

const revokeKey = async (id: number) => {
  if (!confirm('Cabut dan nonaktifkan API key ini sekarang? Pemanggilan API dengan kunci ini akan ditolak.')) return;
  try {
    await api.delete(`/customer/api-keys/${id}`);
    toast.success('Kunci Dicabut', 'API Key berhasil dinonaktifkan.');
    fetchKeys();
  } catch (err: any) {
    toast.error('Gagal Mencabut Kunci', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const copyText = async (text: string, msg: string) => {
  if (!text) return;
  await navigator.clipboard.writeText(text);
  toast.info('Tersalin!', msg);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
};

onMounted(() => {
  fetchKeys();
});
</script>
