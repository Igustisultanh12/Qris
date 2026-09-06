<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
            Generator QRIS Dinamis
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
            Ubah QRIS Statis merchant menjadi QRIS dinamis dengan nominal dan fee tertentu.
          </p>
        </div>
        <button
          v-if="generatedResult"
          @click="resetForm"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 text-sm font-medium transition-colors"
        >
          <RotateCcw class="w-4 h-4" />
          <span>Buat Transaksi Baru</span>
        </button>
      </div>

      <!-- Result View if Generated -->
      <div v-if="generatedResult" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: QR Code Display -->
        <div class="lg:col-span-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col items-center">
          <QrCodeViewer
            :qris-string="generatedResult.qris_dynamic"
            :svg-content="generatedResult.qr_svg"
            :png-data-uri="generatedResult.qr_png"
            :amount="generatedResult.transaction?.amount"
            :merchant-name="generatedResult.transaction?.merchant?.name || selectedMerchantName"
            :reference="generatedResult.transaction?.reference"
            :expires-at="generatedResult.transaction?.expires_at"
          />
        </div>

        <!-- Right: Transaction Details & TLV Breakdown -->
        <div class="lg:col-span-6 space-y-6">
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Rincian Transaksi</h3>
            
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
              <div class="py-2.5 flex justify-between">
                <span class="text-slate-500">ID Transaksi</span>
                <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ generatedResult.transaction?.uuid || generatedResult.transaction?.id }}</span>
              </div>
              <div class="py-2.5 flex justify-between">
                <span class="text-slate-500">Nomor Referensi</span>
                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ generatedResult.transaction?.reference }}</span>
              </div>
              <div class="py-2.5 flex justify-between">
                <span class="text-slate-500">Nominal Pokok</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ formatRupiah(generatedResult.transaction?.amount) }}</span>
              </div>
              <div v-if="generatedResult.transaction?.fee_amount > 0" class="py-2.5 flex justify-between">
                <span class="text-slate-500">Biaya Tambahan (Convenience Fee)</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ formatRupiah(generatedResult.transaction?.fee_amount) }}</span>
              </div>
              <div class="py-2.5 flex justify-between text-base font-bold">
                <span class="text-slate-900 dark:text-white">Total Tagihan</span>
                <span class="text-primary-600 dark:text-primary-400">{{ formatRupiah(generatedResult.transaction?.total_amount) }}</span>
              </div>
              <div class="py-2.5 flex justify-between items-center">
                <span class="text-slate-500">Status Pembayaran</span>
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-xs font-semibold',
                    generatedResult.transaction?.status === 'paid'
                      ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                      : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300'
                  ]"
                >
                  {{ (generatedResult.transaction?.status || 'GENERATED').toUpperCase() }}
                </span>
              </div>

              <!-- Simulation & Actions -->
              <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <button
                  v-if="generatedResult.transaction?.status !== 'paid'"
                  type="button"
                  @click="simulatePaymentNow"
                  :disabled="simulatingPayment"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-600/20 disabled:opacity-50"
                >
                  <Loader2 v-if="simulatingPayment" class="w-3.5 h-3.5 animate-spin" />
                  <CheckCircle2 v-else class="w-4 h-4" />
                  <span>Simulasikan Pembayaran Sukses (PAID)</span>
                </button>
                <div v-else class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                  <CheckCircle2 class="w-4 h-4" />
                  <span>Pembayaran Lunas & Webhook Terkirim</span>
                </div>

                <button
                  type="button"
                  @click="resetForm"
                  class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline flex items-center gap-1"
                >
                  <span>+ Buat QRIS Baru</span>
                </button>
              </div>
            </div>
          </div>

          <!-- EMVCo / ASPI Breakdown Accordion -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">
              EMVCo Tag Breakdown
            </h3>
            <div class="space-y-2 text-xs font-mono bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-100 dark:border-slate-800 overflow-x-auto max-h-64">
              <div><strong class="text-primary-600">Tag 00:</strong> 01 (EMVCo Version)</div>
              <div><strong class="text-primary-600">Tag 01:</strong> 12 (Dynamic QRIS)</div>
              <div v-if="generatedResult.parsed?.merchant_name"><strong class="text-primary-600">Tag 59:</strong> {{ generatedResult.parsed.merchant_name }}</div>
              <div v-if="generatedResult.parsed?.merchant_city"><strong class="text-primary-600">Tag 60:</strong> {{ generatedResult.parsed.merchant_city }}</div>
              <div><strong class="text-primary-600">Tag 54:</strong> {{ generatedResult.transaction?.total_amount }} (Amount)</div>
              <div><strong class="text-primary-600">Tag 58:</strong> ID (Country Code)</div>
              <div><strong class="text-primary-600">Tag 53:</strong> 360 (Currency: IDR)</div>
              <div><strong class="text-primary-600">Tag 63:</strong> {{ generatedResult.qris_dynamic.slice(-4) }} (CRC16-CCITT)</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Generator Form Wizard -->
      <div v-else class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-10 shadow-sm">
        
        <form @submit.prevent="generateDynamicQris" class="space-y-8">
          
          <!-- Step 1: Select Merchant -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-bold text-slate-900 dark:text-white">
                1. Pilih Merchant QRIS
              </label>
              <router-link to="/customer/merchants" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                + Tambah Merchant Baru
              </router-link>
            </div>
            
            <div v-if="loadingMerchants" class="py-4 text-center text-slate-400">
              <Loader2 class="w-6 h-6 animate-spin mx-auto" />
            </div>

            <div v-else-if="merchants.length === 0" class="p-6 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
              <Store class="w-8 h-8 mx-auto text-slate-400 mb-2" />
              <p class="text-sm text-slate-600 dark:text-slate-400">Anda belum mendaftarkan merchant QRIS.</p>
              <router-link to="/customer/merchants" class="mt-3 inline-block px-4 py-2 bg-primary-600 text-white rounded-xl text-xs font-semibold">
                Daftarkan Merchant Sekarang
              </router-link>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="m in merchants"
                :key="m.id"
                @click="form.merchant_id = m.id"
                :class="[
                  'p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between',
                  form.merchant_id === m.id
                    ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20'
                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                ]"
              >
                <div>
                  <div class="font-bold text-slate-900 dark:text-white text-sm">{{ m.name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ m.store_name || m.city || 'Indonesia' }}</div>
                </div>
                <div class="mt-3 text-[11px] font-mono text-slate-400 truncate">
                  NMID: {{ m.primary_qris?.nmid || m.merchant_code }}
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Nominal Transaksi -->
          <div>
            <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
              2. Nominal Transaksi (IDR)
            </label>
            <div class="relative max-w-md">
              <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 font-bold text-base">Rp</span>
              <input
                v-model.number="form.amount"
                type="number"
                min="1"
                max="100000000"
                required
                placeholder="10000"
                class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold text-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
              />
            </div>

            <!-- Quick Amount Chips -->
            <div class="flex flex-wrap gap-2 mt-3">
              <button
                v-for="preset in [10000, 25000, 50000, 100000, 250000, 500000]"
                :key="preset"
                type="button"
                @click="form.amount = preset"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-slate-700 transition-colors"
              >
                +{{ formatRupiah(preset) }}
              </button>
            </div>
          </div>

          <!-- Step 3: Nomor Referensi & Expiry -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-bold text-slate-900 dark:text-white">
                  3. Nomor Referensi / Order ID
                </label>
                <button type="button" @click="generateRef" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                  Auto-generate
                </button>
              </div>
              <input
                v-model="form.reference"
                type="text"
                required
                placeholder="INV-20260905-XXXX"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none text-sm font-mono"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 dark:text-white mb-1">
                Masa Berlaku QRIS (Menit)
              </label>
              <select
                v-model.number="form.expiry_minutes"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none text-sm"
              >
                <option :value="5">5 Menit (Super Cepat)</option>
                <option :value="15">15 Menit (Standar Kasir)</option>
                <option :value="30">30 Menit</option>
                <option :value="60">1 Jam</option>
                <option :value="1440">24 Jam (1 Hari)</option>
              </select>
            </div>
          </div>

          <!-- Step 4: Konfigurasi Biaya / Fee (Optional) -->
          <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">4. Biaya Tambahan (Convenience Fee)</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">Sisipkan tip / biaya admin EMVCo Tag 55/56/57</p>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="form.fee_type = 'none'"
                  :class="[
                    'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                    form.fee_type === 'none' ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                  ]"
                >
                  Tanpa Fee
                </button>
                <button
                  type="button"
                  @click="form.fee_type = 'fixed'"
                  :class="[
                    'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                    form.fee_type === 'fixed' ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                  ]"
                >
                  Nominal Tetap (Rp)
                </button>
                <button
                  type="button"
                  @click="form.fee_type = 'percentage'"
                  :class="[
                    'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                    form.fee_type === 'percentage' ? 'bg-primary-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                  ]"
                >
                  Persentase (%)
                </button>
              </div>
            </div>

            <div v-if="form.fee_type !== 'none'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                  {{ form.fee_type === 'fixed' ? 'Nominal Fee (Rp)' : 'Persentase Fee (%)' }}
                </label>
                <input
                  v-model.number="form.fee_value"
                  type="number"
                  step="any"
                  min="0"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Beban Biaya</label>
                <select
                  v-model="form.fee_mode"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
                >
                  <option value="charged_to_customer">Dibebankan ke Pembeli (Surcharge)</option>
                  <option value="absorbed">Dipotong dari Merchant (Absorbed)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="generating || !form.merchant_id || !form.amount"
            class="w-full py-4 rounded-xl text-white font-bold bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-500/20 shadow-lg shadow-primary-600/25 transition-all flex items-center justify-center gap-2 text-base disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Loader2 v-if="generating" class="w-5 h-5 animate-spin" />
            <QrCode v-else class="w-5 h-5" />
            <span>{{ generating ? 'Memproses Konversi QRIS...' : 'Buat QRIS Dinamis Sekarang' }}</span>
          </button>
        </form>
      </div>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import QrCodeViewer from '../../components/QrCodeViewer.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  QrCode,
  RotateCcw,
  Store,
  Loader2,
  CheckCircle2,
} from 'lucide-vue-next';

const toast = useToastStore();

const merchants = ref<any[]>([]);
const loadingMerchants = ref(true);
const generating = ref(false);
const generatedResult = ref<any>(null);
const simulatingPayment = ref(false);

const form = reactive({
  merchant_id: '',
  amount: 10000,
  reference: '',
  expiry_minutes: 15,
  fee_type: 'none',
  fee_value: 0,
  fee_mode: 'charged_to_customer',
});

const generateRef = () => {
  const rand = Math.floor(100000 + Math.random() * 900000);
  form.reference = `INV-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${rand}`;
};

const selectedMerchantName = computed(() => {
  const m = merchants.value.find((item) => item.id === form.merchant_id);
  return m ? m.name : 'Merchant';
});

const fetchMerchants = async () => {
  loadingMerchants.value = true;
  try {
    const res = await api.get('/customer/merchants');
    const raw = res.data.data;
    merchants.value = Array.isArray(raw) ? raw : (raw?.data || []);
    if (merchants.value.length > 0) {
      form.merchant_id = merchants.value[0].id;
    }
  } catch (err) {
    console.error('Failed to fetch merchants:', err);
  } finally {
    loadingMerchants.value = false;
  }
};

const generateDynamicQris = async () => {
  generating.value = true;
  try {
    const payload = {
      merchant_id: form.merchant_id,
      amount: form.amount,
      reference: form.reference || `INV-${Date.now()}`,
      expiry_minutes: form.expiry_minutes,
      fee_type: form.fee_type,
      fee_value: form.fee_value,
      fee_mode: form.fee_mode,
    };

    const res = await api.post('/customer/qris/generate', payload);
    generatedResult.value = res.data.data;
    toast.success('QRIS Berhasil Dibuat', `Dynamic payload siap dipindai untuk nominal ${formatRupiah(form.amount)}`);
  } catch (err: any) {
    toast.error('Gagal Membuat QRIS', err.response?.data?.message || 'Periksa kembali parameter input.');
  } finally {
    generating.value = false;
  }
};

const simulatePaymentNow = async () => {
  if (!generatedResult.value) return;
  const tx = generatedResult.value.transaction || generatedResult.value;
  const txId = tx.uuid || tx.reference || tx.id;
  if (!txId) return;

  simulatingPayment.value = true;
  try {
    const res = await api.post(`/customer/transactions/${txId}/simulate-paid`);
    if (generatedResult.value.transaction) {
      generatedResult.value.transaction.status = 'paid';
    } else {
      generatedResult.value.status = 'paid';
    }
    toast.success('Pembayaran Dikonfirmasi!', 'Status transaksi berhasil diubah menjadi LUNAS (PAID) dan webhook telah dikirim.');
  } catch (err: any) {
    toast.error('Gagal Simulasi', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    simulatingPayment.value = false;
  }
};

const resetForm = () => {
  generatedResult.value = null;
  generateRef();
};

const formatRupiah = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

onMounted(() => {
  generateRef();
  fetchMerchants();
});
</script>
