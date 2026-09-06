<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Pusat Bantuan & Tiket
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Hubungi tim teknis PT Kreatif Sky Abadi untuk kendala integrasi API, QRIS, atau penagihan.
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Buka Tiket Baru</span>
        </button>
      </div>

      <!-- Tickets List -->
      <div v-if="loading" class="py-16 flex justify-center text-slate-400">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else-if="tickets.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm">
        <LifeBuoy class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak Ada Tiket Dukungan</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
          Jika Anda memiliki pertanyaan seputar integrasi QRIS atau mengalami kendala, buat tiket baru untuk bantuan tim kami.
        </p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="t in tickets"
          :key="t.id"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <span :class="getStatusBadge(t.status)">{{ t.status.toUpperCase() }}</span>
              <span class="text-xs text-slate-400 font-mono">#{{ t.ticket_number || t.id }}</span>
              <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ t.category }}
              </span>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ t.subject }}</h3>
            <p class="text-xs text-slate-500 line-clamp-1">{{ t.message }}</p>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="openTicketDetail(t)"
              class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition-colors"
            >
              Lihat Percakapan
            </button>
          </div>
        </div>
      </div>

      <!-- Create Ticket Modal -->
      <Modal :is-open="showCreateModal" title="Buat Tiket Bantuan Baru" max-width="max-w-lg" @close="showCreateModal = false">
        <form @submit.prevent="createTicket" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Subjek Masalah</label>
            <input
              v-model="newTicket.subject"
              type="text"
              required
              placeholder="Kendala integrasi webhook / verifikasi QRIS"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
              <select
                v-model="newTicket.category"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              >
                <option value="technical">Integrasi Teknis & API</option>
                <option value="billing">Penagihan & Faktur</option>
                <option value="qris">Kendala Standar QRIS</option>
                <option value="general">Pertanyaan Umum</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Prioritas</label>
              <select
                v-model="newTicket.priority"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              >
                <option value="low">Rendah</option>
                <option value="medium">Sedang</option>
                <option value="high">Tinggi</option>
                <option value="urgent">Mendesak</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi Lengkap Kendala</label>
            <textarea
              v-model="newTicket.message"
              rows="4"
              required
              placeholder="Jelaskan detail kendala beserta ID transaksi atau payload yang digunakan..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            ></textarea>
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
              :disabled="submitting || !newTicket.subject"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
              <span>Kirim Tiket</span>
            </button>
          </div>
        </form>
      </Modal>

      <!-- Ticket Thread Modal -->
      <Modal :is-open="showDetailModal" title="Detail Percakapan Tiket" max-width="max-w-2xl" @close="showDetailModal = false">
        <div v-if="activeTicket" class="space-y-4">
          <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
            <div class="flex items-center justify-between mb-1">
              <span class="font-bold text-sm text-slate-900 dark:text-white">{{ activeTicket.subject }}</span>
              <span :class="getStatusBadge(activeTicket.status)">{{ activeTicket.status }}</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300">{{ activeTicket.message }}</p>
          </div>

          <!-- Messages Thread -->
          <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
            <div
              v-for="msg in (activeTicket.messages || [])"
              :key="msg.id"
              :class="[
                'p-3 rounded-xl text-xs space-y-1',
                (msg.is_admin_reply || msg.is_admin) ? 'bg-primary-50 dark:bg-primary-950/40 ml-6 border border-primary-100 dark:border-primary-800' : 'bg-slate-100 dark:bg-slate-800 mr-6'
              ]"
            >
              <div class="flex justify-between font-bold">
                <span>{{ (msg.is_admin_reply || msg.is_admin) ? 'Tim Support PT Kreatif Sky Abadi' : 'Anda' }}</span>
                <span class="text-[10px] text-slate-400">{{ formatDate(msg.created_at) }}</span>
              </div>
              <p class="text-slate-700 dark:text-slate-300">{{ msg.message }}</p>
            </div>
          </div>

          <!-- Reply Box -->
          <form @submit.prevent="sendReply" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <textarea
              v-model="replyText"
              rows="2"
              required
              placeholder="Tulis balasan Anda..."
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs outline-none"
            ></textarea>
            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="replying || !replyText.trim()"
                class="px-4 py-2 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 disabled:opacity-50 flex items-center gap-1.5"
              >
                <Loader2 v-if="replying" class="w-3.5 h-3.5 animate-spin" />
                <Send v-else class="w-3.5 h-3.5" />
                <span>Kirim Balasan</span>
              </button>
            </div>
          </form>
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
  LifeBuoy,
  Plus,
  Send,
  Loader2,
} from 'lucide-vue-next';

const toast = useToastStore();

const tickets = ref<any[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const submitting = ref(false);
const replying = ref(false);
const activeTicket = ref<any>(null);
const replyText = ref('');

const newTicket = reactive({
  subject: '',
  category: 'technical',
  priority: 'medium',
  message: '',
});

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await api.get('/tickets');
    const raw = res.data.data;
    tickets.value = Array.isArray(raw) ? raw : (raw?.data || []);
  } catch (err) {
    console.error('Failed to load tickets:', err);
  } finally {
    loading.value = false;
  }
};

const createTicket = async () => {
  submitting.value = true;
  try {
    await api.post('/tickets', newTicket);
    toast.success('Tiket Dibuat', 'Tim support kami akan segera meninjau tiket Anda.');
    showCreateModal.value = false;
    newTicket.subject = '';
    newTicket.message = '';
    fetchTickets();
  } catch (err: any) {
    toast.error('Gagal Membuat Tiket', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    submitting.value = false;
  }
};

const openTicketDetail = async (t: any) => {
  try {
    const res = await api.get(`/tickets/${t.id}`);
    activeTicket.value = res.data.data;
    showDetailModal.value = true;
  } catch (err: any) {
    toast.error('Gagal Memuat', err.response?.data?.message || 'Tiket tidak ditemukan.');
  }
};

const sendReply = async () => {
  if (!activeTicket.value || !replyText.value.trim()) return;
  replying.value = true;
  try {
    const res = await api.post(`/tickets/${activeTicket.value.id}/reply`, {
      message: replyText.value,
    });
    if (res.data.data) {
      if (!activeTicket.value.messages) {
        activeTicket.value.messages = [];
      }
      activeTicket.value.messages.push(res.data.data);
    }
    replyText.value = '';
    toast.success('Balasan Terkirim', 'Pesan balasan Anda telah diteruskan ke tim bantuan.');
  } catch (err: any) {
    toast.error('Gagal Membalas', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    replying.value = false;
  }
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'open':
      return 'px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
    case 'answered':
      return 'px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    case 'closed':
      return 'px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    default:
      return 'px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700';
  }
};

onMounted(() => {
  fetchTickets();
});
</script>
