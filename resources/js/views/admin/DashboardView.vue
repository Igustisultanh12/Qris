<template>
  <AdminLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Platform Monitoring & Overview
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Statistik agregat seluruh tenant, transaksi QRIS, kesehatan sistem, dan log keamanan.
          </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-emerald-400 font-semibold">Engine QRIS & PJP Acquirer Online</span>
        </div>
      </div>

      <!-- KPI Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Volume QRIS</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ formatRupiah(stats?.transaction_volume || 0) }}
          </div>
          <div class="text-xs text-slate-500 mt-1">
            {{ stats?.paid_transactions || 0 }} transaksi berhasil
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pelanggan (Tenants)</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ stats?.total_customers || 0 }}
          </div>
          <div class="text-xs text-emerald-400 mt-1">
            {{ stats?.active_customers || 0 }} akun aktif
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sub-Merchant Terdaftar</span>
          <div class="text-2xl font-black text-white mt-2">
            {{ stats?.total_merchants || 0 }}
          </div>
          <div class="text-xs text-slate-500 mt-1">
            {{ stats?.active_merchants || 0 }} merchant aktif
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-sm">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pendapatan SaaS (MRR)</span>
          <div class="text-2xl font-black text-emerald-400 mt-2">
            {{ formatRupiah(stats?.subscription_revenue || 0) }}
          </div>
          <div class="text-xs text-slate-500 mt-1">
            Faktur lunas
          </div>
        </div>

      </div>

      <!-- Health Status Bar & API Traffic -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <div class="text-xs font-semibold text-slate-400 uppercase">Kesehatan API & Server</div>
          <div class="flex items-center gap-3 mt-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-950/60 text-emerald-400 flex items-center justify-center font-bold">
              OK
            </div>
            <div>
              <div class="text-sm font-bold text-white">REST API v1 Operasional</div>
              <div class="text-xs text-slate-400">{{ stats?.total_api_calls || 0 }} Total Panggilan</div>
            </div>
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <div class="text-xs font-semibold text-slate-400 uppercase">Tingkat Kesalahan API</div>
          <div class="flex items-center gap-3 mt-3">
            <div class="w-10 h-10 rounded-xl bg-blue-950/60 text-blue-400 flex items-center justify-center font-bold">
              {{ stats?.api_errors_count || 0 }}
            </div>
            <div>
              <div class="text-sm font-bold text-white">4xx / 5xx Status Code</div>
              <div class="text-xs text-slate-400">{{ stats?.webhook_errors_count || 0 }} Gagal Webhook</div>
            </div>
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800">
          <div class="text-xs font-semibold text-slate-400 uppercase">Faktur Tertunggak</div>
          <div class="flex items-center gap-3 mt-3">
            <div class="w-10 h-10 rounded-xl bg-amber-950/60 text-amber-400 flex items-center justify-center font-bold">
              {{ stats?.outstanding_invoices_count || 0 }}
            </div>
            <div>
              <div class="text-sm font-bold text-white">{{ formatRupiah(stats?.outstanding_invoices_amount || 0) }}</div>
              <div class="text-xs text-slate-400">Menunggu Pembayaran</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Audit Trail Logs -->
      <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-white">Log Aktivitas Keamanan Terbaru (Audit Trail)</h3>
          <router-link to="/admin/audit-logs" class="text-xs text-red-400 hover:underline">
            Buka Semua Log &rarr;
          </router-link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-mono">
            <thead class="text-slate-500 uppercase border-b border-slate-800">
              <tr>
                <th class="py-2.5 px-3">Waktu</th>
                <th class="py-2.5 px-3">Aksi</th>
                <th class="py-2.5 px-3">Entitas</th>
                <th class="py-2.5 px-3">Pengguna</th>
                <th class="py-2.5 px-3">Alamat IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900 text-slate-300">
              <tr v-for="log in recentAudits" :key="log.id" class="hover:bg-slate-900/60">
                <td class="py-2.5 px-3 text-slate-500">{{ formatDate(log.created_at) }}</td>
                <td class="py-2.5 px-3 font-semibold text-white">{{ log.action }}</td>
                <td class="py-2.5 px-3">{{ log.entity }}:#{{ log.entity_id }}</td>
                <td class="py-2.5 px-3 text-slate-400">{{ log.user?.email || 'System' }}</td>
                <td class="py-2.5 px-3">{{ log.ip_address || '127.0.0.1' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import api from '../../api/client';

const stats = ref<any>(null);
const recentAudits = ref<any[]>([]);

const fetchDashboard = async () => {
  try {
    const res = await api.get('/admin/dashboard');
    stats.value = res.data.data?.stats;
    recentAudits.value = res.data.data?.recent_audits || [];
  } catch (err) {
    console.error('Admin dashboard error:', err);
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

onMounted(() => {
  fetchDashboard();
});
</script>
