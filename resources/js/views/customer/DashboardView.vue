<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Welcome Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Dashboard Merchant
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Pantau aktivitas konversi QRIS dinamis dan volume transaksi secara real-time.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <router-link
            to="/customer/generator"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
          >
            <QrCode class="w-4 h-4" />
            <span>Generate QRIS</span>
          </router-link>
        </div>
      </div>

      <!-- Subscription Banner if Trial or Grace Period -->
      <div
        v-if="dashboardData?.subscription?.is_grace_period"
        class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between gap-4 text-sm text-amber-800 dark:text-amber-300"
      >
        <div class="flex items-center gap-3">
          <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0" />
          <span>Masa langganan Anda dalam masa tenggang (Grace Period). Harap lakukan pembayaran sebelum akun dibatasi.</span>
        </div>
        <router-link to="/customer/billing" class="font-semibold underline shrink-0 hover:text-amber-950 dark:hover:text-amber-100">
          Bayar Sekarang &rarr;
        </router-link>
      </div>

      <!-- Metric Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Volume -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Volume QRIS</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <Banknote class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ formatRupiah(dashboardData?.stats?.total_volume || 0) }}
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Akumulasi transaksi terbayar
          </p>
        </div>

        <!-- Transactions Count -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Transaksi</span>
            <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 flex items-center justify-center">
              <ArrowLeftRight class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ (dashboardData?.stats?.total_transactions || 0).toLocaleString('id-ID') }}
          </div>
          <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ dashboardData?.stats?.paid_transactions_count || 0 }} Terbayar</span>
            <span>&bull;</span>
            <span>{{ dashboardData?.stats?.generated_qr_count || 0 }} Aktif</span>
          </div>
        </div>

        <!-- Merchants Quota -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Merchant Terdaftar</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <Store class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ dashboardData?.stats?.total_merchants || 0 }} / {{ dashboardData?.stats?.max_merchants || 0 }}
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Kuota merchant aktif Anda
          </p>
        </div>

        <!-- API Calls -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Panggilan API</span>
            <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 flex items-center justify-center">
              <Code class="w-5 h-5" />
            </div>
          </div>
          <div class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ (dashboardData?.stats?.api_calls_count || 0).toLocaleString('id-ID') }}
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Total request REST API v1
          </p>
        </div>

      </div>

      <!-- Main Section: Recent Transactions & Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Recent Transactions Table -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Transaksi Terbaru</h2>
            <router-link to="/customer/transactions" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline">
              Lihat Semua &rarr;
            </router-link>
          </div>

          <div v-if="loading" class="py-12 flex justify-center text-slate-400">
            <Loader2 class="w-8 h-8 animate-spin" />
          </div>

          <div v-else-if="!dashboardData?.recent_transactions?.length" class="py-12 text-center text-slate-500 dark:text-slate-400 text-sm">
            <QrCode class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            Belum ada transaksi yang dibuat. Mulai dengan membuat QRIS dinamis pertama Anda!
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800">
                <tr>
                  <th class="pb-3 font-medium">Referensi / ID</th>
                  <th class="pb-3 font-medium">Merchant</th>
                  <th class="pb-3 font-medium">Nominal</th>
                  <th class="pb-3 font-medium">Status</th>
                  <th class="pb-3 font-medium">Waktu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-for="tx in dashboardData.recent_transactions" :key="tx.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                  <td class="py-3.5 font-mono text-xs font-semibold text-slate-900 dark:text-white">
                    {{ tx.reference }}
                  </td>
                  <td class="py-3.5 text-slate-700 dark:text-slate-300">
                    {{ tx.merchant?.name || 'Default Merchant' }}
                  </td>
                  <td class="py-3.5 font-semibold text-slate-900 dark:text-white">
                    {{ formatRupiah(tx.amount) }}
                  </td>
                  <td class="py-3.5">
                    <span :class="getStatusBadge(tx.status)">
                      {{ tx.status.toUpperCase() }}
                    </span>
                  </td>
                  <td class="py-3.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ formatDate(tx.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right: Subscription & Integration Status -->
        <div class="space-y-6">
          
          <!-- Plan Summary Card -->
          <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 text-white shadow-lg shadow-primary-600/20">
            <div class="flex items-center justify-between mb-4">
              <span class="text-xs uppercase tracking-wider font-semibold text-primary-200">Paket Aktif</span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-white/20 text-white">
                {{ dashboardData?.subscription?.status || 'TRIAL' }}
              </span>
            </div>
            <h3 class="text-2xl font-black tracking-tight">
              {{ dashboardData?.subscription?.plan_name || 'Standard Tier' }}
            </h3>
            <p class="text-primary-100 text-xs mt-1">
              Berlaku hingga: {{ dashboardData?.subscription?.ends_at || '14 Hari Kedepan' }}
            </p>

            <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-between">
              <router-link
                to="/customer/billing"
                class="px-3.5 py-2 rounded-xl bg-white text-primary-700 font-semibold text-xs hover:bg-primary-50 transition-colors shadow-sm"
              >
                Kelola Langganan
              </router-link>
              <router-link to="/pricing" class="text-xs text-primary-200 hover:text-white underline">
                Lihat Semua Paket
              </router-link>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Akses Cepat</h3>
            
            <router-link
              to="/customer/api-keys"
              class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 border border-slate-100 dark:border-slate-800 transition-colors group"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 flex items-center justify-center">
                  <Key class="w-4 h-4" />
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 transition-colors">API Keys</div>
                  <div class="text-[11px] text-slate-500">Integrasikan ke POS / App</div>
                </div>
              </div>
              <ChevronRight class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
            </router-link>

            <router-link
              to="/customer/webhooks"
              class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 border border-slate-100 dark:border-slate-800 transition-colors group"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center">
                  <BellRing class="w-4 h-4" />
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 transition-colors">Webhooks</div>
                  <div class="text-[11px] text-slate-500">Notifikasi status real-time</div>
                </div>
              </div>
              <ChevronRight class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
            </router-link>

            <router-link
              to="/api-docs"
              class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 border border-slate-100 dark:border-slate-800 transition-colors group"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 flex items-center justify-center">
                  <FileText class="w-4 h-4" />
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-900 dark:text-white group-hover:text-primary-600 transition-colors">Dokumentasi API</div>
                  <div class="text-[11px] text-slate-500">Panduan cURL, Node, PHP</div>
                </div>
              </div>
              <ChevronRight class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
            </router-link>
          </div>

        </div>

      </div>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import api from '../../api/client';
import {
  Banknote,
  ArrowLeftRight,
  Store,
  Code,
  QrCode,
  AlertTriangle,
  Loader2,
  Key,
  BellRing,
  FileText,
  ChevronRight,
} from 'lucide-vue-next';

const loading = ref(true);
const dashboardData = ref<any>(null);

const fetchDashboard = async () => {
  loading.value = true;
  try {
    const res = await api.get('/customer/dashboard');
    dashboardData.value = res.data.data;
  } catch (err) {
    console.error('Failed to load dashboard:', err);
  } finally {
    loading.value = false;
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
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
  fetchDashboard();
});
</script>
