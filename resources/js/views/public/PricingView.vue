<template>
  <PublicLayout>
    <div class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16">
        <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Pilihan Paket Berlangganan Fleksibel
        </h1>
        <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
          Pilih paket yang sesuai dengan skala dan kebutuhan operasional bisnis Anda. Upgrade kapan saja.
        </p>
      </div>

      <!-- Pricing Cards Grid -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="flex flex-col justify-between rounded-3xl p-8 border transition-all relative"
          :class="plan.is_popular
            ? 'bg-white dark:bg-slate-900 border-indigo-600 shadow-xl shadow-indigo-500/10 ring-2 ring-indigo-600 dark:ring-indigo-500'
            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm'"
        >
          <div v-if="plan.is_popular" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
            <span class="px-3.5 py-1 rounded-full bg-indigo-600 text-white text-[11px] font-bold tracking-wider uppercase shadow-sm">
              Paling Populer
            </span>
          </div>

          <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ plan.name }}</h3>
            <p class="text-xs text-slate-500 mt-1 min-h-[36px]">{{ plan.description }}</p>

            <div class="mt-6 flex items-baseline gap-1">
              <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">
                Rp {{ plan.price.toLocaleString('id-ID') }}
              </span>
              <span class="text-xs text-slate-400">/ bulan</span>
            </div>

            <!-- Limits -->
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs">
              <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Hingga <strong>{{ plan.max_merchants }} Merchant</strong></span>
              </div>

              <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span><strong>{{ plan.max_api_calls_per_month.toLocaleString('id-ID') }}</strong> Panggilan API/bln</span>
              </div>

              <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span><strong>{{ plan.max_transactions_per_month.toLocaleString('id-ID') }}</strong> Transaksi/bln</span>
              </div>

              <div class="flex items-center gap-2.5 text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Rate limit <strong>{{ plan.rate_limit_per_minute }} req/menit</strong></span>
              </div>
            </div>
          </div>

          <div class="mt-8">
            <router-link
              to="/register"
              class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl font-bold text-xs transition-all shadow-sm"
              :class="plan.is_popular
                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/20'
                : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-white'"
            >
              Pilih Paket Ini
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import PublicLayout from '../../layouts/PublicLayout.vue';
import api from '../../api/client';

interface Plan {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  max_merchants: number;
  max_api_calls_per_month: number;
  max_transactions_per_month: number;
  rate_limit_per_minute: number;
  is_popular: boolean;
}

const plans = ref<Plan[]>([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const res = await api.get('/plans');
    plans.value = res.data.data;
  } catch {
    // fallback defaults
  } finally {
    loading.value = false;
  }
});
</script>
