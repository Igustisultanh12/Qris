<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Langganan & Tagihan
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Kelola paket SaaS Qmis (PT Kreatif Sky Abadi) Anda, riwayat faktur, dan metode pembayaran.
          </p>
        </div>
        <button
          @click="showUpgradeModal = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Sparkles class="w-4 h-4" />
          <span>Upgrade / Ganti Paket</span>
        </button>
      </div>

      <!-- Current Subscription Card -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Paket Aktif Saat Ini</span>
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase',
                  subscription?.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 text-amber-800'
                ]"
              >
                {{ subscription?.status || 'TRIAL' }}
              </span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
              {{ subscription?.plan?.name || 'Paket Basic (Trial)' }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg">
              {{ subscription?.plan?.description || 'Akses konversi QRIS statis ke dinamis hingga 3 merchant.' }}
            </p>
          </div>

          <div class="md:text-right border-t md:border-t-0 pt-4 md:pt-0 border-slate-100 dark:border-slate-800">
            <div class="text-xs text-slate-400">Biaya Langganan</div>
            <div class="text-2xl font-black text-primary-600 dark:text-primary-400">
              {{ formatRupiah(subscription?.price || 0) }} <span class="text-xs font-normal text-slate-400">/ bulan</span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
              Jatuh Tempo: <strong>{{ subscription?.ends_at ? formatDate(subscription.ends_at) : '14 Hari Kedepan' }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoices Section -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Riwayat Faktur (Invoices)</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar tagihan langganan SaaS dan status pembayarannya.</p>
        </div>

        <div v-if="loadingInvoices" class="py-16 flex justify-center text-slate-400">
          <Loader2 class="w-8 h-8 animate-spin" />
        </div>

        <div v-else-if="invoices.length === 0" class="py-16 text-center text-slate-500 dark:text-slate-400 text-sm">
          <Receipt class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
          Belum ada tagihan faktur untuk akun Anda.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 font-medium">Nomor Faktur</th>
                <th class="py-3.5 px-4 font-medium">Deskripsi</th>
                <th class="py-3.5 px-4 font-medium">Subtotal</th>
                <th class="py-3.5 px-4 font-medium">PPN 11%</th>
                <th class="py-3.5 px-4 font-medium">Total Tagihan</th>
                <th class="py-3.5 px-4 font-medium">Status</th>
                <th class="py-3.5 px-4 font-medium text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 dark:text-white">
                  {{ inv.invoice_number }}
                </td>
                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                  Langganan {{ inv.subscription?.plan?.name || 'SaaS Plan' }}
                </td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                  {{ formatRupiah(inv.subtotal) }}
                </td>
                <td class="py-3.5 px-4 text-xs text-slate-500">
                  {{ formatRupiah(inv.tax) }}
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                  {{ formatRupiah(inv.total) }}
                </td>
                <td class="py-3.5 px-4">
                  <span :class="getStatusBadge(inv.status)">
                    {{ inv.status.toUpperCase() }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    v-if="inv.status === 'pending' || inv.status === 'overdue'"
                    @click="openPayModal(inv)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 shadow-sm transition-all"
                  >
                    Bayar Sekarang
                  </button>
                  <span v-else class="text-xs text-emerald-600 font-semibold flex items-center justify-end gap-1">
                    <CheckCircle class="w-3.5 h-3.5" /> Lunas
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pay Invoice Modal -->
      <Modal :is-open="showPayModal" title="Bayar Tagihan Faktur" max-width="max-w-lg" @close="showPayModal = false">
        <div v-if="selectedInvoice" class="space-y-5">
          <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Nomor Invoice</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ selectedInvoice.invoice_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Total Pembayaran</span>
              <span class="font-bold text-base text-primary-600 dark:text-primary-400">{{ formatRupiah(selectedInvoice.total) }}</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Pilih Saluran Pembayaran</label>
            <div class="space-y-2">
              <label
                v-for="gw in gateways"
                :key="gw.id"
                :class="[
                  'p-3.5 rounded-xl border-2 cursor-pointer flex items-center justify-between transition-all',
                  paymentGateway === gw.id ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20' : 'border-slate-200 dark:border-slate-700'
                ]"
              >
                <div class="flex items-center gap-3">
                  <input type="radio" :value="gw.id" v-model="paymentGateway" class="text-primary-600" />
                  <div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white">{{ gw.name }}</div>
                    <div class="text-[11px] text-slate-500">{{ gw.desc }}</div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showPayModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl"
            >
              Batal
            </button>
            <button
              type="button"
              @click="processPayment"
              :disabled="paying"
              class="px-5 py-2.5 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 shadow-md disabled:opacity-50"
            >
              <Loader2 v-if="paying" class="w-3.5 h-3.5 animate-spin" />
              <span>Konfirmasi Pembayaran</span>
            </button>
          </div>
        </div>
      </Modal>

      <!-- Upgrade Plan Modal -->
      <Modal :is-open="showUpgradeModal" title="Pilih Paket Langganan" max-width="max-w-3xl" @close="showUpgradeModal = false">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="p-5 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 transition-all flex flex-col justify-between"
          >
            <div>
              <div class="text-base font-bold text-slate-900 dark:text-white">{{ plan.name }}</div>
              <div class="text-xl font-black text-primary-600 mt-2">{{ formatRupiah(plan.price) }} <span class="text-xs font-normal text-slate-500">/bln</span></div>
              <p class="text-xs text-slate-500 mt-2">{{ plan.description }}</p>
              <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                <div>&bull; Max {{ plan.max_merchants }} Merchant</div>
                <div>&bull; {{ plan.rate_limit_rpm }} req/min API Rate Limit</div>
                <div>&bull; Dukungan Webhook & POS</div>
              </div>
            </div>

            <button
              @click="selectPlanUpgrade(plan.id)"
              class="mt-5 w-full py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold transition-colors"
            >
              Pilih Paket Ini
            </button>
          </div>
        </div>
      </Modal>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import Modal from '../../components/Modal.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  Sparkles,
  Receipt,
  CheckCircle,
  Loader2,
} from 'lucide-vue-next';

const toast = useToastStore();

const subscription = ref<any>(null);
const invoices = ref<any[]>([]);
const plans = ref<any[]>([]);
const loadingInvoices = ref(true);

const showPayModal = ref(false);
const showUpgradeModal = ref(false);
const selectedInvoice = ref<any>(null);
const paymentGateway = ref('manual');
const paying = ref(false);

const gateways = [
  { id: 'manual', name: 'Transfer Bank Manual (BCA / Mandiri / BRI)', desc: 'Verifikasi instan via konfirmasi transfer' },
  { id: 'midtrans', name: 'Midtrans Payment Gateway', desc: 'QRIS, GoPay, ShopeePay, Virtual Account' },
  { id: 'xendit', name: 'Xendit Gateway', desc: 'Virtual Account & E-Wallet' },
  { id: 'tripay', name: 'Tripay Gateway', desc: 'Alfamart / Indomaret & VA' },
];

const fetchBilling = async () => {
  loadingInvoices.value = true;
  try {
    const [subRes, invRes, plansRes] = await Promise.all([
      api.get('/customer/billing/current'),
      api.get('/customer/billing/invoices'),
      api.get('/plans'),
    ]);

    subscription.value = subRes.data.data;
    invoices.value = invRes.data.data;
    plans.value = plansRes.data.data;
  } catch (err) {
    console.error('Failed to load billing:', err);
  } finally {
    loadingInvoices.value = false;
  }
};

const openPayModal = (inv: any) => {
  selectedInvoice.value = inv;
  showPayModal.value = true;
};

const processPayment = async () => {
  if (!selectedInvoice.value) return;
  paying.value = true;
  try {
    const res = await api.post(`/customer/billing/invoices/${selectedInvoice.value.id}/pay`, {
      gateway: paymentGateway.value,
    });
    toast.success('Pembayaran Diproses', res.data.message || 'Invoice berhasil dibayar.');
    showPayModal.value = false;
    fetchBilling();
  } catch (err: any) {
    toast.error('Gagal Memproses Pembayaran', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    paying.value = false;
  }
};

const selectPlanUpgrade = async (planId: number) => {
  try {
    await api.post('/customer/billing/invoices/create', { plan_id: planId });
    toast.success('Faktur Dibuat', 'Faktur untuk upgrade paket berhasil dibuat. Silakan lakukan pembayaran.');
    showUpgradeModal.value = false;
    fetchBilling();
  } catch (err: any) {
    toast.error('Gagal Upgrade', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'medium' });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'paid':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400';
    case 'pending':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
    case 'overdue':
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400';
    default:
      return 'px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600';
  }
};

onMounted(() => {
  fetchBilling();
});
</script>
