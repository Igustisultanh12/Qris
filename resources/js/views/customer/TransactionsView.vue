<template>
  <DashboardLayout>
    <div class="space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Riwayat Transaksi
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Daftar seluruh payload QRIS dinamis yang pernah dibuat dan status pembayarannya.
          </p>
        </div>
        <router-link
          to="/customer/generator"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Buat QRIS Baru</span>
        </router-link>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Cari Referensi / UUID</label>
          <div class="relative">
            <Search class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              v-model="filters.search"
              @input="debounceFetch"
              type="text"
              placeholder="INV-..."
              class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs outline-none"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Status Pembayaran</label>
          <select
            v-model="filters.status"
            @change="fetchTransactions(1)"
            class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs outline-none"
          >
            <option value="">Semua Status</option>
            <option value="generated">GENERATED (Menunggu Bayar)</option>
            <option value="paid">PAID (Terbayar)</option>
            <option value="expired">EXPIRED (Kadaluarsa)</option>
            <option value="cancelled">CANCELLED (Dibatalkan)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Dari Tanggal</label>
          <input
            v-model="filters.from_date"
            @change="fetchTransactions(1)"
            type="date"
            class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Sampai Tanggal</label>
          <input
            v-model="filters.to_date"
            @change="fetchTransactions(1)"
            type="date"
            class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs outline-none"
          />
        </div>
      </div>

      <!-- Table Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <div v-if="loading" class="py-16 flex justify-center text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin" />
        </div>

        <div v-else-if="transactions.length === 0" class="py-16 text-center text-slate-500 dark:text-slate-400 text-sm">
          <FileQuestion class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
          Tidak ada transaksi yang cocok dengan kriteria filter.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 font-medium">Nomor Referensi</th>
                <th class="py-3.5 px-4 font-medium">Merchant</th>
                <th class="py-3.5 px-4 font-medium">Nominal Pokok</th>
                <th class="py-3.5 px-4 font-medium">Biaya (Fee)</th>
                <th class="py-3.5 px-4 font-medium">Total Akhir</th>
                <th class="py-3.5 px-4 font-medium">Status</th>
                <th class="py-3.5 px-4 font-medium">Kadaluarsa</th>
                <th class="py-3.5 px-4 font-medium text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="tx in transactions"
                :key="tx.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 dark:text-white">
                  {{ tx.reference }}
                </td>
                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                  {{ tx.merchant?.name || 'Default Merchant' }}
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                  {{ formatRupiah(tx.amount) }}
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ tx.fee_amount > 0 ? formatRupiah(tx.fee_amount) : '-' }}
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                  {{ formatRupiah(tx.total_amount) }}
                </td>
                <td class="py-3.5 px-4">
                  <span :class="getStatusBadge(tx.status)">
                    {{ tx.status.toUpperCase() }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ formatDate(tx.expires_at) }}
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="viewTransactionDetails(tx.id)"
                    class="px-3 py-1 rounded-lg text-xs font-semibold bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-950/60 dark:text-primary-400 transition-colors"
                  >
                    Detail QR
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="meta && meta.last_page > 1" class="px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
          <span>Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ meta.total }} transaksi</span>
          <div class="flex gap-2">
            <button
              :disabled="meta.current_page <= 1"
              @click="fetchTransactions(meta.current_page - 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 disabled:opacity-40"
            >
              Sebelumnya
            </button>
            <span class="px-2 py-1 font-bold">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="fetchTransactions(meta.current_page + 1)"
              class="px-3 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 disabled:opacity-40"
            >
              Berikutnya
            </button>
          </div>
        </div>

      </div>

      <!-- Transaction Detail Modal -->
      <Modal :is-open="showDetailModal" title="Detail Transaksi QRIS" max-width="max-w-2xl" @close="showDetailModal = false">
        <div v-if="selectedTx" class="space-y-6">
          
          <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
            <div v-if="selectedTx.qr_svg" v-html="selectedTx.qr_svg" class="w-40 h-40 bg-white p-2 rounded-xl border border-slate-200 shrink-0"></div>
            
            <div class="space-y-2 text-sm w-full">
              <div class="flex justify-between">
                <span class="text-slate-500">Status</span>
                <span :class="getStatusBadge(selectedTx.status)">{{ selectedTx.status.toUpperCase() }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Merchant</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ selectedTx.merchant?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Nominal Pokok</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ formatRupiah(selectedTx.amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Fee</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ formatRupiah(selectedTx.fee_amount) }}</span>
              </div>
              <div class="flex justify-between font-bold text-base pt-1 border-t border-slate-200 dark:border-slate-800">
                <span class="text-slate-900 dark:text-white">Total Bayar</span>
                <span class="text-primary-600">{{ formatRupiah(selectedTx.total_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Raw String & Action -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Payload QRIS Dinamis</label>
            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all select-all">
              {{ selectedTx.qris_dynamic }}
            </div>
          </div>

          <div class="flex justify-between items-center pt-2">
            <button
              v-if="selectedTx.status === 'generated'"
              @click="cancelTx(selectedTx.id)"
              class="px-4 py-2 text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 rounded-xl hover:bg-rose-100"
            >
              Batalkan Transaksi
            </button>
            <div v-else></div>

            <button
              @click="showDetailModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl"
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
  Plus,
  Search,
  FileQuestion,
  Loader2,
} from 'lucide-vue-next';

const toast = useToastStore();

const transactions = ref<any[]>([]);
const meta = ref<any>(null);
const loading = ref(true);
const showDetailModal = ref(false);
const selectedTx = ref<any>(null);

const filters = reactive({
  search: '',
  status: '',
  from_date: '',
  to_date: '',
});

let debounceTimer: any = null;
const debounceFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchTransactions(1);
  }, 400);
};

const fetchTransactions = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
      search: filters.search || undefined,
      status: filters.status || undefined,
      from_date: filters.from_date || undefined,
      to_date: filters.to_date || undefined,
    };
    const res = await api.get('/customer/transactions', { params });
    transactions.value = res.data.data;
    meta.value = res.data.meta;
  } catch (err) {
    console.error('Failed to load transactions:', err);
  } finally {
    loading.value = false;
  }
};

const viewTransactionDetails = async (id: number) => {
  try {
    const res = await api.get(`/customer/transactions/${id}`);
    selectedTx.value = res.data.data;
    showDetailModal.value = true;
  } catch (err: any) {
    toast.error('Gagal Memuat Detail', err.response?.data?.message || 'Transaksi tidak ditemukan.');
  }
};

const cancelTx = async (id: number) => {
  if (!confirm('Batalkan transaksi ini? QRIS tidak akan dapat dibayar lagi.')) return;
  try {
    await api.post(`/customer/transactions/${id}/cancel`);
    toast.success('Transaksi Dibatalkan', 'Status transaksi berhasil diubah menjadi cancelled.');
    showDetailModal.value = false;
    fetchTransactions(meta.value?.current_page || 1);
  } catch (err: any) {
    toast.error('Gagal Membatalkan', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'paid':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400';
    case 'generated':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-400';
    case 'expired':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400';
    case 'cancelled':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400';
    default:
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600';
  }
};

onMounted(() => {
  fetchTransactions(1);
});
</script>
