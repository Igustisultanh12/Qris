<template>
  <AdminLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Laporan Keuangan & Analitik
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Rekapitulasi volume transaksi QRIS, pendapatan langganan SaaS, dan export CSV.
          </p>
        </div>
        <button
          @click="downloadCsv"
          :disabled="downloading"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-medium text-sm transition-all disabled:opacity-50"
        >
          <Download class="w-4 h-4" />
          <span>{{ downloading ? 'Mengekspor...' : 'Export Laporan CSV' }}</span>
        </button>
      </div>

      <!-- Financial Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Gross Transaction Volume</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ formatRupiah(overview?.total_transaction_volume || 0) }}
          </div>
          <div class="text-xs text-slate-500 mt-1">Nilai nominal QRIS diproses</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Transaksi Selesai</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ (overview?.paid_transactions_count || 0).toLocaleString('id-ID') }}
          </div>
          <div class="text-xs text-emerald-400 mt-1">Status: Terbayar (PAID)</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Convenience Fee</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ formatRupiah(overview?.total_fees_collected || 0) }}
          </div>
          <div class="text-xs text-slate-500 mt-1">Akumulasi tip / fee</div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pendapatan SaaS</span>
          <div class="text-2xl font-black text-emerald-400 mt-2">
            {{ formatRupiah(overview?.subscription_revenue || 0) }}
          </div>
          <div class="text-xs text-slate-500 mt-1">Faktur langganan berbayar</div>
        </div>
      </div>

      <!-- Rankings Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Top Customers -->
        <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
          <h3 class="text-base font-bold text-white">Top 10 Pelanggan Berdasarkan Volume</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="text-slate-500 uppercase border-b border-slate-800">
                <tr>
                  <th class="py-2.5">Peringkat</th>
                  <th class="py-2.5">Nama Bisnis</th>
                  <th class="py-2.5 text-right">Volume Transaksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-900 text-slate-300">
                <tr v-for="(c, idx) in customerRankings" :key="c.id" class="hover:bg-slate-900/50">
                  <td class="py-2.5 font-bold text-slate-500">#{{ idx + 1 }}</td>
                  <td class="py-2.5 font-bold text-white">{{ c.business_name || c.name }}</td>
                  <td class="py-2.5 text-right font-mono font-semibold text-emerald-400">{{ formatRupiah(c.total_volume || 0) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top Merchants -->
        <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
          <h3 class="text-base font-bold text-white">Top 10 Sub-Merchant Berdasarkan Transaksi</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="text-slate-500 uppercase border-b border-slate-800">
                <tr>
                  <th class="py-2.5">Peringkat</th>
                  <th class="py-2.5">Nama Merchant</th>
                  <th class="py-2.5 text-right">Total Transaksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-900 text-slate-300">
                <tr v-for="(m, idx) in merchantRankings" :key="m.id" class="hover:bg-slate-900/50">
                  <td class="py-2.5 font-bold text-slate-500">#{{ idx + 1 }}</td>
                  <td class="py-2.5 font-bold text-white">{{ m.name }}</td>
                  <td class="py-2.5 text-right font-bold text-white">{{ m.total_transactions || 0 }} tx</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import api from '../../api/client';
import { Download } from 'lucide-vue-next';

const overview = ref<any>(null);
const customerRankings = ref<any[]>([]);
const merchantRankings = ref<any[]>([]);
const downloading = ref(false);

const fetchData = async () => {
  try {
    const [ovRes, rkRes] = await Promise.all([
      api.get('/admin/financial/overview'),
      api.get('/admin/financial/rankings'),
    ]);
    overview.value = ovRes.data.data;
    customerRankings.value = rkRes.data.data?.customers || [];
    merchantRankings.value = rkRes.data.data?.merchants || [];
  } catch (err) {
    console.error('Failed to load financial reports:', err);
  }
};

const downloadCsv = async () => {
  downloading.value = true;
  try {
    const res = await api.get('/admin/financial/export-csv', {
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `kreatif_qris_report_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    console.error('Download CSV failed:', err);
  } finally {
    downloading.value = false;
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

onMounted(() => {
  fetchData();
});
</script>
