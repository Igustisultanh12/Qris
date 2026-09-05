<template>
  <DashboardLayout>
    <div class="space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Manajemen Merchant
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Daftarkan QRIS statis dari bank/acquirer Anda untuk dijadikan sumber konversi dinamis.
          </p>
        </div>
        <button
          @click="openAddModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium text-sm shadow-lg shadow-primary-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Merchant</span>
        </button>
      </div>

      <!-- Merchants List -->
      <div v-if="loading" class="py-16 flex justify-center text-slate-400">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else-if="merchants.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm">
        <Store class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Belum Ada Merchant Terdaftar</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
          Daftarkan QRIS statis Anda (BCA, Mandiri, BRI, GoPay, OVO, ShopeePay, Dana, dll) untuk mulai menerima pembayaran dinamis.
        </p>
        <button
          @click="openAddModal"
          class="mt-5 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-md transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Daftar Merchant Pertama</span>
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="m in merchants"
          :key="m.id"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between"
        >
          <div>
            <div class="flex items-start justify-between gap-2 mb-3">
              <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold">
                <Store class="w-5 h-5" />
              </div>
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider',
                  m.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 text-slate-600'
                ]"
              >
                {{ m.status }}
              </span>
            </div>

            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ m.name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ m.store_name || m.city || 'Indonesia' }}</p>

            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-400">Kode Merchant</span>
                <span class="font-mono text-slate-700 dark:text-slate-300">{{ m.merchant_code }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">NMID</span>
                <span class="font-mono text-slate-700 dark:text-slate-300">{{ m.primary_qris?.nmid || '-' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Kota / Kode Pos</span>
                <span class="text-slate-700 dark:text-slate-300">{{ m.city || '-' }} / {{ m.postal_code || '-' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Total Transaksi</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ m.transactions_count || 0 }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <router-link
              :to="{ path: '/customer/generator', query: { merchant_id: m.id } }"
              class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
              <QrCode class="w-3.5 h-3.5" />
              <span>Generate Dynamic</span>
            </router-link>

            <button
              @click="deleteMerchant(m.id)"
              class="text-xs text-rose-600 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
              title="Hapus Merchant"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Add Merchant Modal -->
      <Modal :is-open="showAddModal" title="Tambah Merchant & QRIS Statis" max-width="max-w-2xl" @close="showAddModal = false">
        <form @submit.prevent="submitMerchant" class="space-y-5">
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Merchant / Bisnis</label>
              <input
                v-model="newMerchant.name"
                type="text"
                required
                placeholder="Kopi Kenangan Senopati"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Gerai / Toko (Opsional)</label>
              <input
                v-model="newMerchant.store_name"
                type="text"
                placeholder="Cabang Jakarta Selatan"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota Merchant (Tag 60)</label>
              <input
                v-model="newMerchant.city"
                type="text"
                placeholder="JAKARTA SELATAN"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Pos (Tag 61)</label>
              <input
                v-model="newMerchant.postal_code"
                type="text"
                placeholder="12190"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
          </div>

          <!-- QRIS Static Input & Scanner -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-900 dark:text-white">
                String QRIS Statis (EMVCo Tag 01=11)
              </label>
              <button
                type="button"
                @click="showScanner = !showScanner"
                class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
              >
                <Camera class="w-3.5 h-3.5" />
                <span>{{ showScanner ? 'Tutup Scanner' : 'Pindai Kamera / Upload Gambar' }}</span>
              </button>
            </div>

            <!-- Embedded Scanner Component -->
            <div v-if="showScanner" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
              <CameraScanner @scan-success="handleScanSuccess" />
            </div>

            <textarea
              v-model="newMerchant.qris_static"
              rows="3"
              required
              @input="validateStaticInput"
              placeholder="00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5923KREATIF SKY ABADI STORE6013JAKARTA PUSAT61051011062070703A0163046155"
              class="w-full px-3.5 py-2 font-mono text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white outline-none"
            ></textarea>

            <!-- Live Validation Indicator -->
            <div v-if="validationResult" class="flex items-center gap-2 text-xs font-semibold" :class="validationResult.valid ? 'text-emerald-600' : 'text-rose-600'">
              <CheckCircle v-if="validationResult.valid" class="w-4 h-4" />
              <AlertCircle v-else class="w-4 h-4" />
              <span>
                {{ validationResult.valid ? `QRIS Statis Valid: ${validationResult.data?.merchant_name || 'Terverifikasi'}` : validationResult.message }}
              </span>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showAddModal = false"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting || !newMerchant.qris_static"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="submitting" class="w-3.5 h-3.5 animate-spin" />
              <span>Simpan Merchant</span>
            </button>
          </div>

        </form>
      </Modal>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import Modal from '../../components/Modal.vue';
import CameraScanner from '../../components/CameraScanner.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  Store,
  Plus,
  QrCode,
  Trash2,
  Camera,
  Loader2,
  CheckCircle,
  AlertCircle,
} from 'lucide-vue-next';

const toast = useToastStore();

const merchants = ref<any[]>([]);
const loading = ref(true);
const showAddModal = ref(false);
const showScanner = ref(false);
const submitting = ref(false);
const validationResult = ref<any>(null);

const newMerchant = reactive({
  name: '',
  store_name: '',
  city: 'JAKARTA',
  postal_code: '12190',
  qris_static: '',
});

const fetchMerchants = async () => {
  loading.value = true;
  try {
    const res = await api.get('/customer/merchants');
    merchants.value = res.data.data;
  } catch (err) {
    console.error('Failed to fetch merchants:', err);
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  validationResult.value = null;
  showScanner.value = false;
  showAddModal.value = true;
};

const handleScanSuccess = (scannedText: string) => {
  newMerchant.qris_static = scannedText;
  showScanner.value = false;
  validateStaticInput();
  toast.success('QRIS Berhasil Dipindai', 'String QRIS statis berhasil diekstrak.');
};

const validateStaticInput = async () => {
  if (newMerchant.qris_static.length < 20) {
    validationResult.value = null;
    return;
  }
  try {
    const res = await api.post('/customer/qris/validate-static', { qris: newMerchant.qris_static });
    validationResult.value = { valid: true, data: res.data.data?.data };
    if (res.data.data?.data?.merchant_name && !newMerchant.name) {
      newMerchant.name = res.data.data.data.merchant_name;
    }
    if (res.data.data?.data?.merchant_city && !newMerchant.city) {
      newMerchant.city = res.data.data.data.merchant_city;
    }
  } catch (err: any) {
    validationResult.value = {
      valid: false,
      message: err.response?.data?.message || 'Payload QRIS tidak memenuhi spesifikasi EMVCo.',
    };
  }
};

const submitMerchant = async () => {
  submitting.value = true;
  try {
    await api.post('/customer/merchants', newMerchant);
    toast.success('Merchant Berhasil Didaftarkan', `Merchant ${newMerchant.name} siap digunakan.`);
    showAddModal.value = false;
    newMerchant.name = '';
    newMerchant.store_name = '';
    newMerchant.qris_static = '';
    fetchMerchants();
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Periksa kembali formulir.');
  } finally {
    submitting.value = false;
  }
};

const deleteMerchant = async (id: number) => {
  if (!confirm('Apakah Anda yakin ingin menghapus merchant ini?')) return;
  try {
    await api.delete(`/customer/merchants/${id}`);
    toast.success('Merchant Dihapus', 'Data merchant telah dihapus dari sistem.');
    fetchMerchants();
  } catch (err: any) {
    toast.error('Gagal Menghapus', err.response?.data?.message || 'Terjadi kesalahan.');
  }
};

onMounted(() => {
  fetchMerchants();
});
</script>
