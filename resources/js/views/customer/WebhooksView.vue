<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Webhook Real-Time
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Terima HTTP POST callback instan ketika QRIS dinamis berhasil dibayar atau kadaluarsa.
          </p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Webhook URL</span>
        </button>
      </div>

      <!-- HMAC Signature Info -->
      <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 space-y-1">
        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 text-emerald-600" />
          <span>Verifikasi Tanda Tangan HMAC-SHA256</span>
        </div>
        <p>Setiap payload webhook dikirim dengan header <code>X-Signature-SHA256: hash_hmac('sha256', $body, $secret)</code> dan <code>X-Event-Name</code>.</p>
      </div>

      <!-- Webhook Endpoints List -->
      <div v-if="loading" class="py-16 flex justify-center text-slate-400">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else-if="webhooks.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm">
        <BellRing class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Belum Ada Webhook Dikonfigurasi</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
          Daftarkan URL server backend Anda untuk menerima notifikasi otomatis begitu transaksi QRIS selesai dibayar.
        </p>
        <button
          @click="openAddModal"
          class="mt-5 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-md"
        >
          <Plus class="w-4 h-4" />
          <span>Konfigurasi Webhook Pertama</span>
        </button>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="wh in webhooks"
          :key="wh.id"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6"
        >
          <div class="space-y-2 max-w-xl">
            <div class="flex items-center gap-3">
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase',
                  wh.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-slate-100 text-slate-600'
                ]"
              >
                {{ wh.is_active ? 'Aktif' : 'Non-aktif' }}
              </span>
              <span class="text-xs text-slate-400 font-mono">ID: #{{ wh.id }}</span>
            </div>

            <div class="font-mono text-sm font-bold text-slate-900 dark:text-white break-all">
              {{ wh.url }}
            </div>

            <!-- Event tags -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <span
                v-for="ev in (wh.events || [])"
                :key="ev"
                class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px] font-mono text-slate-600 dark:text-slate-300"
              >
                {{ ev }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3 shrink-0">
            <button
              @click="testWebhook(wh.id)"
              :disabled="testingId === wh.id"
              class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition-colors flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="testingId === wh.id" class="w-3.5 h-3.5 animate-spin" />
              <Send v-else class="w-3.5 h-3.5" />
              <span>Kirim Test Ping</span>
            </button>

            <button
              @click="deleteWebhook(wh.id)"
              class="p-2 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
              title="Hapus Webhook"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Add Webhook Modal -->
      <Modal :is-open="showAddModal" title="Konfigurasi Webhook Baru" max-width="max-w-lg" @close="showAddModal = false">
        <form @submit.prevent="createWebhook" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Target URL Endpoint (HTTPS)</label>
            <input
              v-model="newWebhook.url"
              type="url"
              required
              placeholder="https://api.domainanda.com/webhook/qris"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none font-mono"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Secret Token (HMAC Signing Key)</label>
            <input
              v-model="newWebhook.secret"
              type="text"
              required
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none font-mono"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-2">Events yang Di-subscribe</label>
            <div class="space-y-2">
              <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                <input type="checkbox" value="transaction.paid" v-model="newWebhook.events" class="rounded text-primary-600" />
                <span class="font-mono">transaction.paid</span> (Ketika pembayaran berhasil dikonfirmasi)
              </label>
              <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                <input type="checkbox" value="transaction.created" v-model="newWebhook.events" class="rounded text-primary-600" />
                <span class="font-mono">transaction.created</span> (Ketika QRIS dinamis berhasil dibuat)
              </label>
              <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                <input type="checkbox" value="transaction.expired" v-model="newWebhook.events" class="rounded text-primary-600" />
                <span class="font-mono">transaction.expired</span> (Ketika QRIS kadaluarsa tanpa dibayar)
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showAddModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting || !newWebhook.url || newWebhook.events.length === 0"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
              <span>Simpan Webhook</span>
            </button>
          </div>
        </form>
      </Modal>

      <!-- Test Ping Result Modal -->
      <Modal :is-open="showPingResult" title="Hasil Pengujian Webhook" max-width="max-w-xl" @close="showPingResult = false">
        <div v-if="pingResult" class="space-y-4 text-xs">
          <div
            :class="[
              'p-4 rounded-xl border flex items-center justify-between',
              pingResult.success ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 text-rose-800 dark:text-rose-300'
            ]"
          >
            <div class="font-bold text-sm">
              Status HTTP: {{ pingResult.status_code || 0 }}
            </div>
            <div>Latency: {{ pingResult.latency_ms || 0 }} ms</div>
          </div>

          <div>
            <label class="block font-semibold text-slate-500 mb-1">Payload Sample yang Dikirim</label>
            <pre class="p-3 bg-slate-900 text-slate-100 rounded-xl font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(pingResult.payload, null, 2) }}</pre>
          </div>

          <div>
            <label class="block font-semibold text-slate-500 mb-1">Response Body Server Anda</label>
            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all">
              {{ pingResult.response_body || '(Kosong)' }}
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              @click="showPingResult = false"
              class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-xl font-semibold"
            >
              Tutup
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
  BellRing,
  Plus,
  ShieldCheck,
  Send,
  Trash2,
  Loader2,
} from 'lucide-vue-next';

const toast = useToastStore();

const webhooks = ref<any[]>([]);
const loading = ref(true);
const showAddModal = ref(false);
const submitting = ref(false);
const testingId = ref<number | null>(null);

const showPingResult = ref(false);
const pingResult = ref<any>(null);

const newWebhook = reactive({
  url: '',
  secret: '',
  events: ['transaction.paid', 'transaction.created'],
});

const fetchWebhooks = async () => {
  loading.value = true;
  try {
    const res = await api.get('/customer/webhooks');
    const raw = res.data.data;
    webhooks.value = Array.isArray(raw) ? raw : (raw?.data || []);
  } catch (err) {
    console.error('Failed to load webhooks:', err);
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  newWebhook.url = '';
  newWebhook.secret = 'whsec_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
  newWebhook.events = ['transaction.paid', 'transaction.created'];
  showAddModal.value = true;
};

const createWebhook = async () => {
  submitting.value = true;
  try {
    await api.post('/customer/webhooks', newWebhook);
    toast.success('Webhook Ditambahkan', 'Webhook baru berhasil dikonfigurasi.');
    showAddModal.value = false;
    fetchWebhooks();
  } catch (err: any) {
    toast.error('Gagal Menyimpan Webhook', err.response?.data?.message || 'Periksa format URL.');
  } finally {
    submitting.value = false;
  }
};

const testWebhook = async (id: number) => {
  testingId.value = id;
  try {
    const res = await api.post(`/customer/webhooks/${id}/test`);
    pingResult.value = res.data.data;
    showPingResult.value = true;
    toast.success('Test Ping Terkirim', `Server merespon dengan status ${res.data.data?.status_code || 200}`);
  } catch (err: any) {
    toast.error('Test Ping Gagal', err.response?.data?.message || 'Server tujuan tidak dapat dijangkau.');
  } finally {
    testingId.value = null;
  }
};

const deleteWebhook = async (id: number) => {
  if (!confirm('Hapus konfigurasi webhook ini?')) return;
  try {
    await api.delete(`/customer/webhooks/${id}`);
    toast.success('Webhook Dihapus', 'Konfigurasi webhook telah dihapus.');
    fetchWebhooks();
  } catch (err: any) {
    toast.error('Gagal Menghapus', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

onMounted(() => {
  fetchWebhooks();
});
</script>
