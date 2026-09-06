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
        {{ twoFactorRequired ? 'Two-Factor Authentication' : 'Masuk ke Platform' }}
      </h2>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
        {{ twoFactorRequired ? 'Masukkan kode 6 digit dari Google Authenticator Anda.' : 'Kelola konversi QRIS statis ke dinamis & pantau transaksi.' }}
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
      <div class="bg-white dark:bg-slate-900 py-8 px-6 shadow-xl border border-slate-200/80 dark:border-slate-800 sm:rounded-2xl sm:px-10">
        
        <!-- Standard Login Form -->
        <form v-if="!twoFactorRequired" @submit.prevent="handleLogin" class="space-y-5">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/50 text-sm text-rose-600 dark:text-rose-400 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
            <div class="relative">
              <input
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="nama@perusahaan.com"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none text-sm"
              />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
              <a href="#" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Lupa password?</a>
            </div>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none text-sm pr-10"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
              >
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-2.5 px-4 rounded-xl text-white font-medium bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20 text-sm"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span>{{ loading ? 'Memverifikasi...' : 'Masuk' }}</span>
          </button>

          <!-- Quick Test Autofill Buttons -->
          <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider text-center">Akun Demo (1-Click)</p>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="quickLogin('admin@kreatifskyabadi.co.id', 'KreatifSkyAbadi2026!')"
                class="px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-center"
              >
                Super Admin
              </button>
              <button
                type="button"
                @click="quickLogin('demo@example.com', 'password')"
                class="px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-center"
              >
                Customer Merchant
              </button>
            </div>
          </div>
        </form>

        <!-- 2FA Prompt -->
        <form v-else @submit.prevent="handleVerify2fa" class="space-y-5">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/50 text-sm text-rose-600 dark:text-rose-400 flex items-start gap-3">
            <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 text-center">Kode OTP / Backup Code</label>
            <input
              v-model="twoFactorCode"
              type="text"
              required
              maxlength="10"
              autofocus
              placeholder="123456"
              class="w-full text-center tracking-[0.5em] text-2xl font-mono py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="loading || !twoFactorCode"
            class="w-full py-2.5 px-4 rounded-xl text-white font-medium bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20 text-sm"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span>{{ loading ? 'Memverifikasi...' : 'Konfirmasi 2FA' }}</span>
          </button>

          <button
            type="button"
            @click="cancel2fa"
            class="w-full py-2 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-center"
          >
            Kembali ke Login
          </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
          Belum punya akun?
          <router-link to="/register" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline">
            Daftar sekarang (Trial 14 Hari)
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import api from '../../api/client';
import { Eye, EyeOff, Loader2, AlertCircle } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();
const toast = useToastStore();

const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  email: '',
  password: '',
});

const twoFactorRequired = ref(false);
const tempToken = ref('');
const twoFactorCode = ref('');

const quickLogin = (email: string, pass: string) => {
  form.email = email;
  form.password = pass;
};

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await api.post('/auth/login', form);
    const data = res.data.data;

    if (data.otp_required) {
      toast.warning('Verifikasi Diperlukan', 'Email Anda belum diverifikasi. Kode OTP telah dikirimkan ke email Anda.');
      router.push({ path: '/register', query: { email: data.email, step: 'otp' } });
      return;
    }

    if (data.two_factor_required) {
      twoFactorRequired.value = true;
      tempToken.value = data.temp_token;
      return;
    }

    auth.setAuth(data.token, data.user);
    toast.success('Berhasil Masuk', `Selamat datang kembali, ${data.user.name}!`);

    if (data.user.role === 'admin' || data.user.is_admin) {
      router.push('/admin/dashboard');
    } else {
      router.push('/dashboard');
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Login gagal. Silakan periksa kredensial Anda.';
  } finally {
    loading.value = false;
  }
};

const handleVerify2fa = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await api.post('/auth/verify-2fa', {
      temp_token: tempToken.value,
      code: twoFactorCode.value,
    });
    const data = res.data.data;

    auth.setAuth(data.token, data.user);
    toast.success('2FA Diverifikasi', `Selamat datang, ${data.user.name}!`);

    if (data.user.role === 'admin' || data.user.is_admin) {
      router.push('/admin/dashboard');
    } else {
      router.push('/dashboard');
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Kode 2FA tidak valid atau kadaluarsa.';
  } finally {
    loading.value = false;
  }
};

const cancel2fa = () => {
  twoFactorRequired.value = false;
  tempToken.value = '';
  twoFactorCode.value = '';
  errorMessage.value = '';
};
</script>
