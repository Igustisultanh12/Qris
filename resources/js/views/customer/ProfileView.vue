<template>
  <DashboardLayout>
    <div class="max-w-4xl mx-auto space-y-8">
      
      <!-- Header -->
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
          Profil & Keamanan Akun
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
          Kelola informasi identitas akun, ubah kata sandi, dan aktifkan autentikasi dua faktor (2FA).
        </p>
      </div>

      <!-- Profile Info Form -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Informasi Pengguna</h3>
        
        <form @submit.prevent="updateProfile" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
              <input
                v-model="profileForm.name"
                type="text"
                required
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp / Telepon</label>
              <input
                v-model="profileForm.phone"
                type="tel"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat Email (Akun Utama)</label>
            <input
              :value="auth.user?.email"
              disabled
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-500 text-sm outline-none cursor-not-allowed"
            />
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="updatingProfile"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="updatingProfile" class="w-3.5 h-3.5 animate-spin" />
              <span>Simpan Profil</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Password Change -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Ubah Password</h3>
        
        <form @submit.prevent="updatePassword" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password Saat Ini</label>
            <input
              v-model="passwordForm.current_password"
              type="password"
              required
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password Baru</label>
              <input
                v-model="passwordForm.password"
                type="password"
                required
                minlength="8"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ulangi Password Baru</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                minlength="8"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none"
              />
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="updatingPassword"
              class="px-5 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="updatingPassword" class="w-3.5 h-3.5 animate-spin" />
              <span>Perbarui Password</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Two-Factor Authentication (2FA) -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Two-Factor Authentication (2FA)</h3>
            <p class="text-xs text-slate-500 mt-1">
              Lindungi akun Anda dengan otentikasi berbasis waktu (TOTP) menggunakan Google Authenticator atau Authy.
            </p>
          </div>
          <span
            :class="[
              'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase',
              twoFactorEnabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
            ]"
          >
            {{ twoFactorEnabled ? 'AKTIF' : 'NON-AKTIF' }}
          </span>
        </div>

        <!-- If not enabled and not in setup -->
        <div v-if="!twoFactorEnabled && !setupData" class="pt-2">
          <button
            @click="initTwoFactor"
            :disabled="loading2fa"
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl flex items-center gap-2"
          >
            <ShieldCheck class="w-4 h-4" />
            <span>Aktifkan 2FA Sekarang</span>
          </button>
        </div>

        <!-- 2FA Setup Flow -->
        <div v-if="setupData" class="space-y-4 p-5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
            1. Pindai kode QR berikut di aplikasi Google Authenticator Anda:
          </div>
          
          <div class="flex flex-col sm:flex-row items-center gap-6">
            <div v-html="setupData.qr_code" class="w-40 h-40 bg-white p-2 rounded-xl border border-slate-200"></div>
            <div class="space-y-2 text-xs">
              <div class="text-slate-500">Atau masukkan kode manual ini:</div>
              <div class="font-mono text-sm font-bold bg-white dark:bg-slate-900 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 select-all">
                {{ setupData.secret }}
              </div>
            </div>
          </div>

          <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 pt-3">
            2. Masukkan kode 6-digit dari aplikasi untuk mengonfirmasi:
          </div>
          <div class="flex items-center gap-3">
            <input
              v-model="totpCode"
              type="text"
              maxlength="6"
              placeholder="123456"
              class="w-36 tracking-[0.3em] font-mono text-center text-lg py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 outline-none"
            />
            <button
              @click="confirmTwoFactor"
              :disabled="confirming2fa || totpCode.length !== 6"
              class="px-5 py-2.5 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 disabled:opacity-50"
            >
              Verifikasi & Aktifkan
            </button>
            <button
              @click="setupData = null"
              class="px-3 py-2 text-xs text-slate-500 hover:text-slate-700"
            >
              Batal
            </button>
          </div>
        </div>

        <!-- If enabled -->
        <div v-if="twoFactorEnabled" class="pt-2">
          <button
            @click="disableTwoFactor"
            class="px-4 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs font-bold"
          >
            Nonaktifkan 2FA
          </button>
        </div>

      </div>

    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import {
  ShieldCheck,
  Loader2,
} from 'lucide-vue-next';

const auth = useAuthStore();
const toast = useToastStore();

const updatingProfile = ref(false);
const updatingPassword = ref(false);
const loading2fa = ref(false);
const confirming2fa = ref(false);

const twoFactorEnabled = ref(false);
const setupData = ref<any>(null);
const totpCode = ref('');

const profileForm = reactive({
  name: auth.user?.name || '',
  phone: '',
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updateProfile = async () => {
  updatingProfile.value = true;
  try {
    const res = await api.put('/user/profile', profileForm);
    toast.success('Profil Diperbarui', 'Data profil Anda telah berhasil disimpan.');
    if (auth.user) {
      auth.user.name = res.data.data?.name || profileForm.name;
    }
  } catch (err: any) {
    toast.error('Gagal Memperbarui Profil', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    updatingProfile.value = false;
  }
};

const updatePassword = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    toast.error('Gagal', 'Konfirmasi password baru tidak cocok.');
    return;
  }
  updatingPassword.value = true;
  try {
    await api.put('/user/password', passwordForm);
    toast.success('Password Diperbarui', 'Kata sandi akun Anda telah berhasil diubah.');
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
  } catch (err: any) {
    toast.error('Gagal Mengubah Password', err.response?.data?.message || 'Password saat ini tidak valid.');
  } finally {
    updatingPassword.value = false;
  }
};

const initTwoFactor = async () => {
  loading2fa.value = true;
  try {
    const res = await api.post('/user/2fa/setup');
    setupData.value = res.data.data;
  } catch (err: any) {
    toast.error('Gagal Inisiasi 2FA', err.response?.data?.message || 'Terjadi kesalahan.');
  } finally {
    loading2fa.value = false;
  }
};

const confirmTwoFactor = async () => {
  confirming2fa.value = true;
  try {
    await api.post('/user/2fa/confirm', { code: totpCode.value });
    toast.success('2FA Aktif', 'Two-Factor Authentication telah berhasil diaktifkan.');
    twoFactorEnabled.value = true;
    setupData.value = null;
    totpCode.value = '';
  } catch (err: any) {
    toast.error('Kode OTP Salah', err.response?.data?.message || 'Kode verifikasi tidak valid.');
  } finally {
    confirming2fa.value = false;
  }
};

const disableTwoFactor = async () => {
  const pwd = prompt('Masukkan password akun Anda untuk mengonfirmasi penonaktifan 2FA:');
  if (!pwd) return;
  try {
    await api.post('/user/2fa/disable', { password: pwd });
    toast.success('2FA Dinonaktifkan', 'Two-factor authentication telah dimatikan.');
    twoFactorEnabled.value = false;
  } catch (err: any) {
    toast.error('Gagal', err.response?.data?.message || 'Password salah.');
  }
};

onMounted(() => {
  if (auth.user) {
    profileForm.name = auth.user.name;
    twoFactorEnabled.value = !!auth.user.two_factor_enabled;
  }
});
</script>
