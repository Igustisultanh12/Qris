<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 w-full backdrop-blur-md bg-white/80 dark:bg-slate-900/80 border-b border-slate-200/80 dark:border-slate-800/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-black text-xl shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
            Q
          </div>
          <div>
            <span class="text-base font-extrabold tracking-tight text-slate-900 dark:text-white block leading-tight">Qmis</span>
            <span class="text-[10px] font-semibold tracking-wider text-indigo-600 dark:text-indigo-400 block uppercase">PT Kreatif Sky Abadi</span>
          </div>
        </router-link>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600 dark:text-slate-300">
          <router-link to="/" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Beranda</router-link>
          <router-link to="/pricing" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Harga Paket</router-link>
          <router-link to="/api-docs" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dokumentasi API</router-link>
          <router-link to="/terms" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Syarat & Ketentuan</router-link>
        </nav>

        <!-- Right Side: Dark Mode & Auth -->
        <div class="flex items-center gap-3">
          <!-- Dark mode toggle -->
          <button
            @click="themeStore.toggle"
            class="p-2 rounded-xl text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="Toggle Theme"
          >
            <svg v-if="themeStore.isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <template v-if="authStore.isAuthenticated">
            <router-link
              :to="authStore.isAdmin ? '/admin' : '/dashboard'"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition-all"
            >
              <span>Dashboard</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </router-link>
          </template>
          <template v-else>
            <router-link
              to="/login"
              class="hidden sm:inline-flex px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-indigo-600 transition-colors"
            >
              Masuk
            </router-link>
            <router-link
              to="/register"
              class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition-all shadow-indigo-500/20"
            >
              Daftar Gratis
            </router-link>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
      <slot></slot>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div class="col-span-1 md:col-span-1">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black">Q</div>
            <span class="font-bold text-slate-900 dark:text-white">PT Kreatif Sky Abadi</span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Platform SaaS API & Solusi QRIS Static ke Dynamic terdepan di Indonesia untuk UMKM, retail, F&B, dan enterprise.
          </p>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-3">Produk & Solusi</h4>
          <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
            <li><router-link to="/pricing" class="hover:text-indigo-600">Paket Harga</router-link></li>
            <li><router-link to="/api-docs" class="hover:text-indigo-600">REST API Platform</router-link></li>
            <li><a href="/docs/openapi.json" target="_blank" class="hover:text-indigo-600">OpenAPI Spec (JSON)</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-3">Legal & Keamanan</h4>
          <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
            <li><router-link to="/terms" class="hover:text-indigo-600">Syarat & Ketentuan</router-link></li>
            <li><router-link to="/privacy" class="hover:text-indigo-600">Kebijakan Privasi</router-link></li>
            <li><span class="text-xs">Sesuai Standar QRIS EMVCo & ASPI</span></li>
          </ul>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-3">Kontak Perusahaan</h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Jl. Sudirman No. 88, Jakarta Pusat<br>
            Email: support@kreatifskyabadi.co.id<br>
            Telp: +62 21 555 0199
          </p>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-8 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-400">
        &copy; 2026 PT Kreatif Sky Abadi. Seluruh Hak Cipta Dilindungi Undang-Undang.
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';

const authStore = useAuthStore();
const themeStore = useThemeStore();
</script>
