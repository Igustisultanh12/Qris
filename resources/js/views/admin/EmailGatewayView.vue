<template>
  <AdminLayout>
    <div class="max-w-5xl space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Email Gateway & Relay SMTP
          </h1>
          <p class="text-slate-400 text-sm mt-1">
            Konfigurasi server SMTP transaksional untuk pengiriman resi pembayaran QRIS, faktur langganan, dan notifikasi keamanan.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
              form.is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400'
            ]"
          >
            {{ form.is_active ? 'GATEWAY AKTIF' : 'GATEWAY NON-AKTIF' }}
          </span>
        </div>
      </div>

      <div v-if="loading" class="py-16 flex justify-center text-slate-500">
        <Loader2 class="w-8 h-8 animate-spin" />
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Configuration Form -->
        <div class="lg:col-span-7 space-y-6">
          <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-5">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Mail class="w-4 h-4 text-red-500" />
              <span>Konfigurasi Server SMTP</span>
            </h3>

            <form @submit.prevent="saveConfig" class="space-y-4">
              
              <!-- Driver Selection -->
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Mailer Driver</label>
                <div class="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    @click="form.mailer = 'smtp'"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold transition-all border',
                      form.mailer === 'smtp' ? 'bg-red-600/20 text-red-400 border-red-500/50' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                    ]"
                  >
                    SMTP Relay
                  </button>
                  <button
                    type="button"
                    @click="form.mailer = 'sendmail'"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold transition-all border',
                      form.mailer === 'sendmail' ? 'bg-red-600/20 text-red-400 border-red-500/50' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                    ]"
                  >
                    Sendmail
                  </button>
                  <button
                    type="button"
                    @click="form.mailer = 'log'"
                    :class="[
                      'py-2 px-3 rounded-xl text-xs font-bold transition-all border',
                      form.mailer === 'log' ? 'bg-red-600/20 text-red-400 border-red-500/50' : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'
                    ]"
                  >
                    Log Only (Debug)
                  </button>
                </div>
              </div>

              <!-- Host & Port -->
              <div v-if="form.mailer === 'smtp'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-400 mb-1">SMTP Host</label>
                  <input
                    v-model="form.host"
                    type="text"
                    required
                    placeholder="smtp.mailgun.org / smtp.gmail.com"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs font-mono outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-400 mb-1">Port</label>
                  <input
                    v-model.number="form.port"
                    type="number"
                    required
                    placeholder="587"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs font-mono outline-none"
                  />
                </div>
              </div>

              <!-- Encryption -->
              <div v-if="form.mailer === 'smtp'">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Protokol Enkripsi</label>
                <div class="flex gap-2">
                  <button
                    type="button"
                    @click="form.encryption = 'tls'"
                    :class="[
                      'py-1.5 px-3 rounded-lg text-xs font-bold transition-all',
                      form.encryption === 'tls' ? 'bg-red-600 text-white' : 'bg-slate-900 text-slate-400'
                    ]"
                  >
                    TLS (Port 587)
                  </button>
                  <button
                    type="button"
                    @click="form.encryption = 'ssl'"
                    :class="[
                      'py-1.5 px-3 rounded-lg text-xs font-bold transition-all',
                      form.encryption === 'ssl' ? 'bg-red-600 text-white' : 'bg-slate-900 text-slate-400'
                    ]"
                  >
                    SSL (Port 465)
                  </button>
                  <button
                    type="button"
                    @click="form.encryption = 'none'"
                    :class="[
                      'py-1.5 px-3 rounded-lg text-xs font-bold transition-all',
                      form.encryption === 'none' ? 'bg-red-600 text-white' : 'bg-slate-900 text-slate-400'
                    ]"
                  >
                    None (Port 25)
                  </button>
                </div>
              </div>

              <!-- Username & Password -->
              <div v-if="form.mailer === 'smtp'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-400 mb-1">SMTP Username</label>
                  <input
                    v-model="form.username"
                    type="text"
                    placeholder="user@kreatifskyabadi.co.id"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs font-mono outline-none"
                  />
                </div>
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-400">SMTP Password</label>
                    <span v-if="form.password_set" class="text-[10px] text-emerald-400 font-semibold">(Tersimpan)</span>
                  </div>
                  <input
                    v-model="form.password"
                    type="password"
                    :placeholder="form.password_set ? '•••••••• (Biarkan kosong jika tidak diubah)' : 'Masukkan password SMTP'"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs font-mono outline-none"
                  />
                </div>
              </div>

              <!-- Sender Identity -->
              <div class="pt-2 border-t border-slate-900 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Pengirim (From Name)</label>
                  <input
                    v-model="form.from_name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-400 mb-1">Alamat Email Pengirim (From Address)</label>
                  <input
                    v-model="form.from_address"
                    type="email"
                    required
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none font-mono"
                  />
                </div>
              </div>

              <!-- Active switch -->
              <div class="pt-2 flex items-center justify-between">
                <div>
                  <div class="text-xs font-bold text-white">Aktifkan Pengiriman Email</div>
                  <div class="text-[11px] text-slate-500">Kirim email otomatis untuk resi transaksi dan faktur</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                  <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
              </div>

              <!-- Submit Button -->
              <div class="pt-3 border-t border-slate-900 flex justify-end">
                <button
                  type="submit"
                  :disabled="saving"
                  class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 disabled:opacity-50"
                >
                  <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                  <span>Simpan Konfigurasi</span>
                </button>
              </div>

            </form>
          </div>
        </div>

        <!-- Right: Live Test Email & Presets -->
        <div class="lg:col-span-5 space-y-6">
          
          <!-- Test Send Box -->
          <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Send class="w-4 h-4 text-emerald-400" />
              <span>Kirim Email Pengujian (Test Ping)</span>
            </h3>
            <p class="text-xs text-slate-400">
              Verifikasi apakah server SMTP Anda dapat dihubungi dan email diterima dengan baik di inbox tujuan.
            </p>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Kirim Ke Email</label>
                <input
                  v-model="testRecipient"
                  type="email"
                  placeholder="admin@perusahaan.com"
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-800 bg-slate-900 text-white text-xs outline-none"
                />
              </div>

              <button
                @click="sendTestEmail"
                :disabled="testing || !testRecipient"
                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 disabled:opacity-50"
              >
                <Loader2 v-if="testing" class="w-3.5 h-3.5 animate-spin" />
                <Send v-else class="w-3.5 h-3.5" />
                <span>{{ testing ? 'Mengirim Test Email...' : 'Kirim Test Email Sekarang' }}</span>
              </button>
            </div>

            <!-- Test Result Banner -->
            <div
              v-if="testResult"
              :class="[
                'p-4 rounded-xl text-xs space-y-1.5 border',
                testResult.success ? 'bg-emerald-950/50 border-emerald-800 text-emerald-300' : 'bg-rose-950/50 border-rose-800 text-rose-300'
              ]"
            >
              <div class="font-bold flex items-center gap-1.5">
                <CheckCircle v-if="testResult.success" class="w-4 h-4 text-emerald-400" />
                <AlertCircle v-else class="w-4 h-4 text-rose-400" />
                <span>{{ testResult.success ? 'Sukses Terkirim!' : 'Gagal Terkirim!' }}</span>
              </div>
              <p class="text-[11px] leading-relaxed">{{ testResult.message }}</p>
              <div v-if="testResult.latency_ms" class="text-[10px] text-slate-400 pt-1">
                Roundtrip Latency: <strong>{{ testResult.latency_ms }} ms</strong>
              </div>
            </div>
          </div>

          <!-- Quick Presets -->
          <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-3">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Preset Cepat SMTP Populer</h4>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                type="button"
                @click="applyPreset('gmail')"
                class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-left border border-slate-800 transition-colors"
              >
                <div class="font-bold text-white">Gmail / G-Suite</div>
                <div class="text-[10px] text-slate-500">smtp.gmail.com:587</div>
              </button>

              <button
                type="button"
                @click="applyPreset('mailgun')"
                class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-left border border-slate-800 transition-colors"
              >
                <div class="font-bold text-white">Mailgun</div>
                <div class="text-[10px] text-slate-500">smtp.mailgun.org:587</div>
              </button>

              <button
                type="button"
                @click="applyPreset('brevo')"
                class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-left border border-slate-800 transition-colors"
              >
                <div class="font-bold text-white">Brevo (Sendinblue)</div>
                <div class="text-[10px] text-slate-500">smtp-relay.brevo.com:587</div>
              </button>

              <button
                type="button"
                @click="applyPreset('cpanel')"
                class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-left border border-slate-800 transition-colors"
              >
                <div class="font-bold text-white">aaPanel / cPanel</div>
                <div class="text-[10px] text-slate-500">mail.domainanda.com:465</div>
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import api from '../../api/client';
import { useToastStore } from '../../stores/toast';
import {
  Mail,
  Send,
  Loader2,
  CheckCircle,
  AlertCircle,
} from 'lucide-vue-next';

const toast = useToastStore();

const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const testRecipient = ref('');
const testResult = ref<any>(null);

const form = reactive({
  mailer: 'smtp',
  host: 'smtp.mailtrap.io',
  port: 587,
  username: '',
  password: '',
  password_set: false,
  encryption: 'tls',
  from_address: 'noreply@kreatifskyabadi.co.id',
  from_name: 'Qmis - PT Kreatif Sky Abadi',
  is_active: true,
});

const fetchConfig = async () => {
  loading.value = true;
  try {
    const res = await api.get('/admin/email-gateway');
    const data = res.data.data;
    form.mailer = data.mailer || 'smtp';
    form.host = data.host || '';
    form.port = data.port || 587;
    form.username = data.username || '';
    form.password_set = !!data.password_set;
    form.encryption = data.encryption || 'tls';
    form.from_address = data.from_address || 'noreply@kreatifskyabadi.co.id';
    form.from_name = data.from_name || 'Qmis - PT Kreatif Sky Abadi';
    form.is_active = data.is_active !== false;
  } catch (err) {
    console.error('Failed to load email gateway config:', err);
  } finally {
    loading.value = false;
  }
};

const saveConfig = async () => {
  saving.value = true;
  try {
    await api.post('/admin/email-gateway', form);
    toast.success('Disimpan', 'Konfigurasi Email Gateway berhasil disimpan.');
    form.password = '';
    fetchConfig();
  } catch (err: any) {
    toast.error('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    saving.value = false;
  }
};

const sendTestEmail = async () => {
  if (!testRecipient.value) return;
  testing.value = true;
  testResult.value = null;
  try {
    const res = await api.post('/admin/email-gateway/test', {
      recipient_email: testRecipient.value,
    });
    testResult.value = res.data.data;
    toast.success('Test Email Terkirim', `Berhasil dikirim ke ${testRecipient.value}`);
  } catch (err: any) {
    testResult.value = err.response?.data?.data || {
      success: false,
      message: err.response?.data?.message || 'Gagal mengirim email.',
    };
    toast.error('Gagal Mengirim Test', testResult.value.message);
  } finally {
    testing.value = false;
  }
};

const applyPreset = (preset: string) => {
  form.mailer = 'smtp';
  switch (preset) {
    case 'gmail':
      form.host = 'smtp.gmail.com';
      form.port = 587;
      form.encryption = 'tls';
      break;
    case 'mailgun':
      form.host = 'smtp.mailgun.org';
      form.port = 587;
      form.encryption = 'tls';
      break;
    case 'brevo':
      form.host = 'smtp-relay.brevo.com';
      form.port = 587;
      form.encryption = 'tls';
      break;
    case 'cpanel':
      form.host = 'mail.domainanda.com';
      form.port = 465;
      form.encryption = 'ssl';
      break;
  }
  toast.info('Preset Diterapkan', `Pengaturan host untuk ${preset} telah dimuat.`);
};

onMounted(() => {
  fetchConfig();
});
</script>
