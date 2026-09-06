<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <router-link to="/" class="inline-flex items-center gap-2 mb-6">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-primary-600/30">
          Q
        </div>
        <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Q<span class="text-primary-600 dark:text-primary-400">mis</span></span>
      </router-link>
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
        {{ step === 'otp' ? 'Verifikasi Email Akun Anda' : 'Mulai Pendaftaran Akun Qmis' }}
      </h2>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
        {{ step === 'otp' ? 'Masukkan 6 digit kode OTP yang kami kirimkan ke email Anda.' : 'Daftar tanpa kartu kredit. Akses API dinamis QRIS instan.' }}
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg px-4 sm:px-0">
      <div class="bg-white dark:bg-slate-900 py-8 px-6 shadow-xl border border-slate-200/80 dark:border-slate-800 sm:rounded-2xl sm:px-10">
        
        <!-- STEP 1: REGISTRATION FORM -->
        <form v-if="step === 'form'" @submit.prevent="handleRegister" class="space-y-4">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/50 text-sm text-rose-600 dark:text-rose-400 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Budi Santoso"
              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Bisnis / Perusahaan</label>
            <input
              v-model="form.business_name"
              type="text"
              required
              placeholder="PT Maju Bersama / Toko Berkah"
              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email Bisnis</label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="budi@perusahaan.com"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="081234567890"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                placeholder="Min. 8 karakter"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                minlength="8"
                placeholder="Ketik ulang password"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none text-sm"
              />
            </div>
          </div>

          <div class="flex items-start gap-2 pt-2">
            <input
              id="terms"
              v-model="agreed"
              type="checkbox"
              required
              class="w-4 h-4 mt-0.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
            />
            <label for="terms" class="text-xs text-slate-600 dark:text-slate-400">
              Saya menyetujui <router-link to="/legal" class="text-primary-600 dark:text-primary-400 hover:underline">Syarat & Ketentuan Layanan</router-link> serta <router-link to="/legal" class="text-primary-600 dark:text-primary-400 hover:underline">Kebijakan Privasi</router-link> PT Kreatif Sky Abadi.
            </label>
          </div>

          <button
            type="submit"
            :disabled="loading || !agreed"
            class="w-full mt-4 py-2.5 px-4 rounded-xl text-white font-medium bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20 text-sm"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span>{{ loading ? 'Mendaftarkan Akun...' : 'Daftar Akun Sekarang' }}</span>
          </button>
        </form>

        <!-- STEP 2: EMAIL OTP VERIFICATION -->
        <form v-else @submit.prevent="handleVerifyOtp" class="space-y-5">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/50 text-sm text-rose-600 dark:text-rose-400 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-start gap-3">
            <Mail class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
            <div>
              Kami telah mengirimkan 6 digit kode verifikasi ke email <strong>{{ registeredEmail }}</strong>. Silakan periksa kotak masuk atau spam email Anda.
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 text-center">Masukkan 6 Digit Kode OTP</label>
            <input
              v-model="otpCode"
              type="text"
              required
              maxlength="6"
              autofocus
              placeholder="123456"
              class="w-full text-center tracking-[0.5em] text-3xl font-mono py-3.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="loading || otpCode.length !== 6"
            class="w-full py-2.5 px-4 rounded-xl text-white font-medium bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20 text-sm"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span>{{ loading ? 'Memverifikasi Kode...' : 'Verifikasi & Lanjutkan' }}</span>
          </button>

          <div class="flex items-center justify-between text-xs pt-2">
            <button
              type="button"
              @click="handleResendOtp"
              :disabled="resendCountdown > 0 || resending"
              class="text-primary-600 dark:text-primary-400 font-semibold hover:underline disabled:opacity-40 disabled:no-underline"
            >
              {{ resendCountdown > 0 ? `Kirim ulang kode (${resendCountdown}s)` : (resending ? 'Mengirim...' : 'Kirim Ulang Kode OTP') }}
            </button>

            <button
              type="button"
              @click="step = 'form'"
              class="text-slate-500 hover:text-slate-800 dark:hover:text-white"
            >
              Ganti Email / Kembali
            </button>
          </div>
        </form>

        <div class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
          Sudah punya akun?
          <router-link to="/login" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">
            Masuk sekarang
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import api from '../../api/client';
import { Loader2, AlertCircle, Mail } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const toast = useToastStore();

const step = ref<'form' | 'otp'>('form');
const loading = ref(false);
const resending = ref(false);
const agreed = ref(false);
const errorMessage = ref('');
const registeredEmail = ref('');
const otpCode = ref('');
const resendCountdown = ref(0);
let timerInterval: any = null;

const form = reactive({
  name: '',
  business_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
});

const startCountdown = () => {
  resendCountdown.value = 60;
  clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (resendCountdown.value > 0) {
      resendCountdown.value--;
    } else {
      clearInterval(timerInterval);
    }
  }, 1000);
};

onUnmounted(() => {
  clearInterval(timerInterval);
});

onMounted(() => {
  if (route.query.step === 'otp' && route.query.email) {
    registeredEmail.value = String(route.query.email);
    step.value = 'otp';
    startCountdown();
  }
});

const handleRegister = async () => {
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Konfirmasi password tidak cocok.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await api.post('/auth/register', form);
    const data = res.data.data;

    registeredEmail.value = data?.email || form.email;
    step.value = 'otp';
    startCountdown();
    toast.success('Pendaftaran Berhasil!', 'Kode verifikasi OTP telah dikirimkan ke email Anda.');
  } catch (err: any) {
    if (err.response?.data?.errors) {
      const errList = Object.values(err.response.data.errors).flat();
      errorMessage.value = errList.join(', ');
    } else {
      errorMessage.value = err.response?.data?.message || 'Pendaftaran gagal. Silakan coba lagi.';
    }
  } finally {
    loading.value = false;
  }
};

const handleVerifyOtp = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await api.post('/auth/verify-otp', {
      email: registeredEmail.value,
      otp: otpCode.value,
    });
    const data = res.data.data;

    auth.setAuth(data.token, data.user);
    toast.success('Akun Berhasil Diverifikasi', 'Selamat datang di platform Qmis (PT Kreatif Sky Abadi).');
    router.push('/dashboard');
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Kode OTP tidak valid atau kadaluarsa.';
  } finally {
    loading.value = false;
  }
};

const handleResendOtp = async () => {
  if (resendCountdown.value > 0 || resending.value) return;
  resending.value = true;
  errorMessage.value = '';

  try {
    await api.post('/auth/resend-otp', {
      email: registeredEmail.value,
    });
    toast.success('Kode Terkirim', 'Kode OTP baru telah dikirimkan ke email Anda.');
    startCountdown();
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengirim ulang kode OTP.';
  } finally {
    resending.value = false;
  }
};
</script>

