<template>
  <AdminLayout>
    <div class="space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Paket & Tarif SaaS
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Konfigurasi tier langganan, batasan sub-merchant, dan limit laju pemanggilan API (RPM).
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-colors"
        >
          + Tambah Paket Baru
        </button>
      </div>

      <!-- Plans Cards Grid -->
      <div v-if="loading" class="py-16 flex justify-center text-slate-500">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="p in plans"
          :key="p.id"
          class="bg-slate-950 rounded-2xl border border-slate-800 p-6 flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs uppercase tracking-wider font-bold text-slate-400">{{ p.slug }}</span>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                  p.is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400'
                ]"
              >
                {{ p.is_active ? 'Aktif' : 'Draft' }}
              </span>
            </div>

            <h3 class="text-xl font-bold text-white">{{ p.name }}</h3>
            <div class="text-2xl font-black text-red-400 mt-2">
              {{ formatRupiah(p.price) }} <span class="text-xs font-normal text-slate-400">/ bulan</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">{{ p.description }}</p>

            <div class="mt-4 pt-4 border-t border-slate-900 space-y-2 text-xs text-slate-300">
              <div class="flex justify-between">
                <span class="text-slate-500">Maks. Merchant</span>
                <span class="font-bold text-white">{{ p.max_merchants }} merchant</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">API Rate Limit</span>
                <span class="font-bold text-white">{{ p.rate_limit_rpm }} req/min</span>
              </div>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-900 flex justify-end gap-2">
            <button
              @click="openEditModal(p)"
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-semibold"
            >
              Edit
            </button>
            <button
              @click="deletePlan(p.id)"
              class="px-3 py-1.5 bg-rose-950/60 hover:bg-rose-900 text-rose-300 rounded-lg text-xs font-semibold"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>

      <!-- Plan Form Modal -->
      <Modal :is-open="showModal" :title="isEditing ? 'Edit Paket' : 'Buat Paket Baru'" max-width="max-w-lg" @close="showModal = false">
        <form @submit.prevent="savePlan" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Paket</label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Slug URL</label>
              <input v-model="form.slug" type="text" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Harga (IDR)</label>
              <input v-model.number="form.price" type="number" min="0" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Sub-Merchant</label>
              <input v-model.number="form.max_merchants" type="number" min="1" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">API Rate Limit (RPM)</label>
            <input v-model.number="form.rate_limit_rpm" type="number" min="10" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Singkat</label>
            <textarea v-model="form.description" rows="2" class="w-full px-3 py-2 border rounded-xl text-sm outline-none dark:bg-slate-800 dark:border-slate-700"></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-semibold text-slate-500">Batal</button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold">Simpan Paket</button>
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

const plans = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = reactive({
  name: '',
  slug: '',
  price: 0,
  currency: 'IDR',
  billing_interval: 'monthly',
  max_merchants: 3,
  rate_limit_rpm: 60,
  description: '',
  is_active: true,
});

const fetchPlans = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/plans');
    plans.value = res.data.data;
  } catch (err) {
    console.error('Failed to load plans:', err);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.name = '';
  form.slug = '';
  form.price = 99000;
  form.max_merchants = 5;
  form.rate_limit_rpm = 60;
  form.description = '';
  showModal.value = true;
};

const openEditModal = (p: any) => {
  isEditing.value = true;
  editingId.value = p.id;
  form.name = p.name;
  form.slug = p.slug;
  form.price = p.price;
  form.max_merchants = p.max_merchants;
  form.rate_limit_rpm = p.rate_limit_rpm;
  form.description = p.description;
  showModal.value = true;
};

const savePlan = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await api.put(`/admin/plans/${editingId.value}`, form);
      toast.success('Paket Diperbarui', 'Data paket berhasil disimpan.');
    } else {
      await api.post('/admin/plans', form);
      toast.success('Paket Dibuat', 'Paket baru berhasil ditambahkan.');
    }
    showModal.value = false;
    fetchPlans();
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

const deletePlan = async (id: number) => {
  if (!confirm('Hapus paket ini?')) return;
  try {
    await api.delete(`/admin/plans/${id}`);
    toast.success('Paket Dihapus', 'Paket telah dinonaktifkan.');
    fetchPlans();
  } catch (err: any) {
    toast.error('Gagal Menghapus', err.response?.data?.message || 'Paket ini sedang digunakan oleh pelanggan.');
  }
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

onMounted(() => {
  fetchPlans();
});
</script>
