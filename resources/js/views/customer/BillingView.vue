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

      <!-- Pay Invoice Modal (DYNAMIC QRIS FROM PLATFORM STATIC) -->
      <Modal :is-open="showPayModal" title="Pembayaran QRIS Dinamis Platform" max-width="max-w-lg" @close="closePayModal">
        <div v-if="selectedInvoice" class="space-y-4">
          <!-- Summary Header -->
          <div class="p-4 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
            <div>
              <span class="text-[11px] text-slate-500 block">Total Tagihan Faktur</span>
              <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                {{ formatRupiah(selectedInvoice.total) }}
              </span>
            </div>
            <div class="text-right">
              <span class="text-[11px] text-slate-500 block">Nomor Invoice</span>
              <span class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                {{ selectedInvoice.invoice_number }}
              </span>
            </div>
          </div>

          <!-- Loading QRIS state -->
          <div v-if="loadingQris" class="py-12 flex flex-col items-center justify-center gap-2 text-slate-400">
            <Loader2 class="w-8 h-8 animate-spin text-indigo-600" />
            <span class="text-xs">Mengonversi QRIS Statis Platform ke Dinamis...</span>
          </div>

          <!-- Payment Success Celebration State (Auto-detected or simulated) -->
          <div v-if="paymentDetected" class="py-10 text-center space-y-4 bg-emerald-950/20 rounded-2xl border border-emerald-500/30 p-6">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto animate-bounce">
              <CheckCircle class="w-10 h-10" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Pembayaran Berhasil Dikonfirmasi!</h3>
              <p class="text-xs text-slate-300 mt-1 max-w-sm mx-auto">
                Sistem telah mendeteksi dana masuk. Faktur telah lunas dan paket langganan Anda telah aktif seketika.
              </p>
            </div>
            <div class="text-[11px] text-emerald-400 font-medium">
              Menutup modal dan memperbarui status akun...
            </div>
          </div>

          <!-- QRIS Display Card -->
          <div v-else-if="qrisInfo" class="space-y-4">
            <!-- Realtime Auto-Detection Pulse Indicator -->
            <div class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
              </span>
              <span>Menunggu pembayaran... Sistem otomatis mendeteksi transaksi secara realtime.</span>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border-2 border-indigo-500/30 p-5 flex flex-col items-center text-center shadow-lg relative">
              <div class="w-full flex items-center justify-between pb-3 mb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded bg-rose-600 text-white font-black text-[10px] flex items-center justify-center">QR</div>
                  <span class="text-xs font-black tracking-wider text-slate-900 dark:text-white uppercase">QRIS DINAMIS</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400">
                  NOMINAL OTOMATIS
                </span>
              </div>

              <!-- Merchant info -->
              <div class="mb-3">
                <div class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-tight">
                  {{ qrisInfo.qris?.merchant_name || 'PT KREATIF SKY ABADI' }}
                </div>
                <div class="text-[11px] text-slate-500">
                  NMID: ID1020000000001 &bull; {{ qrisInfo.qris?.merchant_city || 'JAKARTA' }}
                </div>
              </div>

              <!-- QR Code visual SVG -->
              <div class="p-3 bg-white rounded-2xl border border-slate-200 shadow-inner max-w-[240px] w-full aspect-square flex items-center justify-center overflow-hidden">
                <div v-html="qrisInfo.qris?.qr_svg" class="w-full h-full flex items-center justify-center [&>svg]:w-full [&>svg]:h-full"></div>
              </div>

              <!-- QR Nominal highlight -->
              <div class="mt-3 text-center">
                <span class="text-[11px] text-slate-400 block">Nominal Tertera pada QR:</span>
                <span class="text-lg font-black text-slate-900 dark:text-white">
                  {{ formatRupiah(qrisInfo.qris?.amount || selectedInvoice.total) }}
                </span>
              </div>

              <div class="mt-2 text-[10px] text-slate-400 flex items-center gap-1">
                <span>Scan dengan GoPay, OVO, Dana, ShopeePay, BCA, Mandiri, BRI, BNI, LinkAja</span>
              </div>
            </div>

            <!-- String payload with copy button -->
            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-between gap-2 text-xs">
              <div class="truncate font-mono text-[11px] text-slate-600 dark:text-slate-300">
                {{ qrisInfo.qris?.payload }}
              </div>
              <button
                type="button"
                @click="copyPayload(qrisInfo.qris?.payload)"
                class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-200 font-semibold text-xs shrink-0 flex items-center gap-1 shadow-sm"
              >
                <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-600" />
                <Copy v-else class="w-3.5 h-3.5" />
                <span>{{ copied ? 'Tersalin' : 'Salin' }}</span>
              </button>
            </div>

            <!-- Developer / Testing Simulation Option -->
            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] text-slate-500">Mode Pengujian / Simulasi Tanpa Transfer:</span>
              </div>
              <button
                type="button"
                @click="simulatePayment"
                :disabled="paying"
                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
              >
                <Loader2 v-if="paying" class="w-4 h-4 animate-spin" />
                <CheckCircle v-else class="w-4 h-4 text-emerald-400" />
                <span>{{ paying ? 'Memverifikasi...' : 'Simulasikan Pembayaran Lunas (Dev Mode)' }}</span>
              </button>
              <p class="text-[10px] text-center text-slate-500 mt-1.5">
                Di lingkungan live, sistem otomatis mendeteksi mutasi uang masuk dari bank/e-wallet tanpa perlu klik tombol.
              </p>
            </div>
          </div>

          <div v-else class="py-8 text-center text-xs text-rose-500">
            Gagal memuat QRIS dinamis untuk invoice ini. Silakan coba kembali.
          </div>

          <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showPayModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl"
            >
              Tutup
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
import { ref, onMounted, onUnmounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import Modal from '../../components/Modal.vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import {
  Sparkles,
  Receipt,
  CheckCircle,
  Loader2,
  Copy,
  Check,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const toast = useToastStore();

const subscription = ref<any>(null);
const invoices = ref<any[]>([]);
const plans = ref<any[]>([]);
const loadingInvoices = ref(true);

const showPayModal = ref(false);
const showUpgradeModal = ref(false);
const selectedInvoice = ref<any>(null);
const qrisInfo = ref<any>(null);
const loadingQris = ref(false);
const paying = ref(false);
const copied = ref(false);
const paymentDetected = ref(false);
let pollTimer: any = null;

const startPolling = (invId: string | number) => {
  stopPolling();
  pollTimer = setInterval(async () => {
    if (!showPayModal.value) {
      stopPolling();
      return;
    }
    try {
      const res = await api.get(`/customer/billing/invoices/${invId}/qris`);
      if (res.data.data?.is_paid || res.data.data?.invoice?.status === 'paid') {
        stopPolling();
        paymentDetected.value = true;
        toast.success('Pembayaran Terdeteksi!', 'Faktur telah lunas dan paket langganan Anda telah aktif otomatis!');
        await authStore.fetchUser();
        await fetchBilling();
        setTimeout(() => {
          showPayModal.value = false;
          paymentDetected.value = false;
        }, 2500);
      }
    } catch {
      // ignore transient poll error
    }
  }, 3000);
};

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

const closePayModal = () => {
  stopPolling();
  showPayModal.value = false;
  paymentDetected.value = false;
};

const fetchBilling = async () => {
  loadingInvoices.value = true;
  try {
    const [subRes, invRes, plansRes] = await Promise.all([
      api.get('/customer/billing/current'),
      api.get('/customer/billing/invoices'),
      api.get('/plans'),
    ]);

    subscription.value = subRes.data.data?.subscription || subRes.data.data;
    const invRaw = invRes.data.data;
    invoices.value = Array.isArray(invRaw) ? invRaw : (invRaw?.data || []);
    const pRaw = plansRes.data.data;
    plans.value = Array.isArray(pRaw) ? pRaw : (pRaw?.data || []);
  } catch (err) {
    console.error('Failed to load billing:', err);
  } finally {
    loadingInvoices.value = false;
  }
};

const openPayModal = async (inv: any) => {
  selectedInvoice.value = inv;
  showPayModal.value = true;
  loadingQris.value = true;
  qrisInfo.value = null;
  paymentDetected.value = false;

  try {
    const invId = inv.uuid || inv.id;
    const res = await api.get(`/customer/billing/invoices/${invId}/qris`);
    qrisInfo.value = res.data.data;

    // Start background auto-detection polling
    startPolling(invId);
  } catch (err: any) {
    toast.error('Gagal Memuat QRIS', err.response?.data?.message || 'Terjadi kesalahan saat memuat QRIS dinamis.');
  } finally {
    loadingQris.value = false;
  }
};

const copyPayload = async (payload?: string) => {
  if (!payload) return;
  try {
    await navigator.clipboard.writeText(payload);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
    toast.success('Disalin', 'String payload QRIS berhasil disalin ke clipboard.');
  } catch {
    // fallback
  }
};

const simulatePayment = async () => {
  if (!selectedInvoice.value) return;
  paying.value = true;
  try {
    const invId = selectedInvoice.value.uuid || selectedInvoice.value.id;
    const res = await api.post(`/customer/billing/invoices/${invId}/simulate-paid`);
    stopPolling();
    paymentDetected.value = true;
    toast.success('Pembayaran Lunas!', res.data.message || 'Paket langganan Anda telah aktif!');
    
    // Refresh user state so auth store immediately reflects the active subscription
    await authStore.fetchUser();
    await fetchBilling();
    
    setTimeout(() => {
      showPayModal.value = false;
      paymentDetected.value = false;
    }, 2500);
  } catch (err: any) {
    toast.error('Gagal Memproses Pembayaran', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    paying.value = false;
  }
};

const selectPlanUpgrade = async (planId: number) => {
  try {
    const res = await api.post('/customer/billing/invoices/create', { plan_id: planId });
    toast.success('Faktur Dibuat', 'Faktur berhasil dibuat. Silakan selesaikan pembayaran QRIS.');
    showUpgradeModal.value = false;
    await fetchBilling();
    
    // Immediately open the dynamic QRIS modal for the newly generated invoice
    const createdInvoice = res.data.data;
    if (createdInvoice) {
      openPayModal(createdInvoice);
    }
  } catch (err: any) {
    toast.error('Gagal Membuat Faktur', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (val: string) => {
  if (!val) return '-';
  return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'paid':
      return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400';
    case 'pending':
      return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400';
    case 'overdue':
      return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-400';
    default:
      return 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400';
  }
};

onMounted(() => {
  fetchBilling();
});

onUnmounted(() => {
  stopPolling();
});
</script>
