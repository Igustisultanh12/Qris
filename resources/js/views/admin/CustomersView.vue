<template>
  <AdminLayout>
    <div class="space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Manajemen Pelanggan (Tenants)
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Daftar seluruh perusahaan/pelanggan SaaS, kontrol status akun, dan kuota sub-merchant.
          </p>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-slate-950 rounded-2xl border border-slate-800 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
          <label class="block font-semibold text-slate-400 mb-1">Cari Nama / Email / Bisnis</label>
          <input
            v-model="search"
            @input="fetchCustomers"
            type="text"
            placeholder="Cari..."
            class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white outline-none"
          />
        </div>
        <div>
          <label class="block font-semibold text-slate-400 mb-1">Status Akun</label>
          <select
            v-model="status"
            @change="fetchCustomers"
            class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white outline-none"
          >
            <option value="">Semua Status</option>
            <option value="active">Active (Aktif)</option>
            <option value="suspended">Suspended (Ditangguhkan)</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>

      <!-- Customers Table -->
      <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden">
        
        <div v-if="loading" class="py-16 flex justify-center text-slate-500">
          <Loader2 class="w-8 h-8 animate-spin" />
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="text-slate-500 uppercase border-b border-slate-800 bg-slate-900/50">
              <tr>
                <th class="py-3 px-4">Nama Pelanggan</th>
                <th class="py-3 px-4">Nama Bisnis</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Paket Langganan</th>
                <th class="py-3 px-4">Sub-Merchants</th>
                <th class="py-3 px-4">Transaksi</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900 text-slate-300">
              <tr v-for="c in customers" :key="c.id" class="hover:bg-slate-900/50">
                <td class="py-3.5 px-4 font-bold text-white">{{ c.name }}</td>
                <td class="py-3.5 px-4">{{ c.business_name }}</td>
                <td class="py-3.5 px-4 font-mono">{{ c.email }}</td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold">
                    {{ c.active_subscription?.plan?.name || 'Trial' }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-white">{{ c.merchants_count || 0 }}</span> / {{ c.max_merchants }}
                </td>
                <td class="py-3.5 px-4 font-bold text-white">{{ c.transactions_count || 0 }}</td>
                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full font-bold uppercase text-[10px]',
                      c.status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800'
                    ]"
                  >
                    {{ c.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right space-x-2">
                  <button
                    @click="openEditModal(c)"
                    class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white font-semibold transition-colors"
                  >
                    Ubah Kuota
                  </button>
                  <button
                    @click="toggleStatus(c)"
                    :class="[
                      'px-2.5 py-1 rounded font-semibold transition-colors',
                      c.status === 'active' ? 'bg-rose-950 hover:bg-rose-900 text-rose-300' : 'bg-emerald-950 hover:bg-emerald-900 text-emerald-300'
                    ]"
                  >
                    {{ c.status === 'active' ? 'Suspend' : 'Aktifkan' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

      <!-- Edit Customer Modal -->
      <Modal :is-open="showModal" title="Ubah Kuota & Paket Pelanggan" max-width="max-w-md" @close="showModal = false">
        <form @submit.prevent="saveCustomerSubscription" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Bisnis</label>
            <input :value="selectedCustomer?.business_name" disabled class="w-full px-3 py-2 bg-slate-800 text-slate-400 rounded-xl text-xs outline-none" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Maksimal Merchant</label>
            <input
              v-model.number="editForm.max_merchants"
              type="number"
              min="1"
              max="100"
              required
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Paket Subscription</label>
            <select
              v-model="editForm.plan_id"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm outline-none"
            >
              <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-semibold text-slate-500">Batal</button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold">Simpan</button>
          </div>
        </form>
      </Modal>

    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import Modal from '../../components/Modal.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import { Loader2 } from 'lucide-vue-next';

const toast = useToastStore();

const customers = ref<any[]>([]);
const plans = ref<any[]>([]);
const loading = ref(true);
const search = ref('');
const status = ref('');

const showModal = ref(false);
const selectedCustomer = ref<any>(null);

const editForm = reactive({
  max_merchants: 3,
  plan_id: '',
});

const fetchCustomers = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/customers', {
      params: { search: search.value || undefined, status: status.value || undefined },
    });
    customers.value = res.data.data?.data || res.data.data || [];
  } catch (err) {
    console.error('Failed to load customers:', err);
  } finally {
    loading.value = false;
  }
};

const openEditModal = (c: any) => {
  selectedCustomer.value = c;
  editForm.max_merchants = c.max_merchants;
  editForm.plan_id = c.active_subscription?.plan_id || plans.value[0]?.id;
  showModal.value = true;
};

const toggleStatus = async (c: any) => {
  const newSt = c.status === 'active' ? 'suspended' : 'active';
  if (!confirm(`Ubah status pelanggan ${c.name} menjadi ${newSt}?`)) return;
  try {
    await api.put(`/admin/customers/${c.id}/status`, { status: newSt });
    toast.success('Status Diperbarui', `Pelanggan sekarang berstatus ${newSt}`);
    fetchCustomers();
  } catch (err: any) {
    toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const saveCustomerSubscription = async () => {
  try {
    await api.put(`/admin/customers/${selectedCustomer.value.id}/subscription`, editForm);
    toast.success('Berhasil', 'Kuota dan paket pelanggan berhasil diperbarui.');
    showModal.value = false;
    fetchCustomers();
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

onMounted(async () => {
  fetchCustomers();
  try {
    const pRes = await api.get('/plans');
    plans.value = pRes.data.data;
  } catch {}
});
</script>
