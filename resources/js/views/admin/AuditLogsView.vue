<template>
  <AdminLayout>
    <div class="space-y-6">
      
      <!-- Header -->
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
          Audit Trail & Security Logs
        </h1>
        <p class="text-slate-400 text-sm mt-1">
          Rekaman forensik seluruh tindakan administratif, login pengguna, perubahan kuota, dan mutasi data sistem.
        </p>
      </div>

      <!-- Filters -->
      <div class="bg-slate-950 rounded-2xl border border-slate-800 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
          <label class="block font-semibold text-slate-400 mb-1">Cari Aksi / Event</label>
          <input
            v-model="actionFilter"
            @input="fetchLogs(1)"
            type="text"
            placeholder="misal: user.login, customer.status_changed..."
            class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white outline-none"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-400 mb-1">Entitas Target</label>
          <select
            v-model="entityFilter"
            @change="fetchLogs(1)"
            class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white outline-none"
          >
            <option value="">Semua Entitas</option>
            <option value="User">User</option>
            <option value="Customer">Customer</option>
            <option value="Merchant">Merchant</option>
            <option value="Transaction">Transaction</option>
            <option value="ApiKey">ApiKey</option>
            <option value="SubscriptionPlan">SubscriptionPlan</option>
          </select>
        </div>
      </div>

      <!-- Audit Table -->
      <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden">
        <div v-if="loading" class="py-16 flex justify-center text-slate-500">
          <Loader2 class="w-8 h-8 animate-spin" />
        </div>

        <div v-else-if="logs.length === 0" class="py-16 text-center text-slate-500 text-xs">
          Tidak ada data log yang sesuai kriteria.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs font-mono">
            <thead class="text-slate-500 uppercase border-b border-slate-800 bg-slate-900/50">
              <tr>
                <th class="py-3 px-4">Waktu</th>
                <th class="py-3 px-4">Aksi</th>
                <th class="py-3 px-4">Entitas</th>
                <th class="py-3 px-4">Pengguna</th>
                <th class="py-3 px-4">Alamat IP</th>
                <th class="py-3 px-4">Perubahan Data</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900 text-slate-300">
              <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-900/60">
                <td class="py-3.5 px-4 text-slate-500">{{ formatDate(log.created_at) }}</td>
                <td class="py-3.5 px-4 font-bold text-white">{{ log.action }}</td>
                <td class="py-3.5 px-4 font-semibold text-indigo-400">{{ log.entity }}:#{{ log.entity_id }}</td>
                <td class="py-3.5 px-4 text-slate-400">{{ log.user?.email || 'System' }}</td>
                <td class="py-3.5 px-4 text-slate-500">{{ log.ip_address || '127.0.0.1' }}</td>
                <td class="py-3.5 px-4">
                  <div v-if="log.new_values" class="max-w-xs truncate text-slate-400 text-[10px]">
                    {{ JSON.stringify(log.new_values) }}
                  </div>
                  <span v-else class="text-slate-600">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="px-4 py-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
          <span>Halaman {{ meta.current_page }} dari {{ meta.last_page }}</span>
          <div class="flex gap-2">
            <button
              :disabled="meta.current_page <= 1"
              @click="fetchLogs(meta.current_page - 1)"
              class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded disabled:opacity-40"
            >
              Prev
            </button>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="fetchLogs(meta.current_page + 1)"
              class="px-3 py-1 bg-slate-800 hover:bg-slate-700 rounded disabled:opacity-40"
            >
              Next
            </button>
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
import { Loader2 } from 'lucide-vue-next';

const logs = ref<any[]>([]);
const meta = ref<any>(null);
const loading = ref(true);

const actionFilter = ref('');
const entityFilter = ref('');

const fetchLogs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/admin/audit-logs', {
      params: {
        page,
        action: actionFilter.value || undefined,
        entity: entityFilter.value || undefined,
      },
    });
    logs.value = res.data.data?.data || [];
    meta.value = res.data.data;
  } catch (err) {
    console.error('Failed to load audit logs:', err);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
};

onMounted(() => {
  fetchLogs(1);
});
</script>
