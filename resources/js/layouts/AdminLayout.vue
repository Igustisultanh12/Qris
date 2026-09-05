<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col md:flex-row">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between shrink-0">
      <div>
        <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800">
          <router-link to="/admin" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white font-bold text-sm">
              AD
            </div>
            <div>
              <span class="font-bold text-sm text-white block leading-tight">Super Admin</span>
              <span class="text-[10px] text-slate-400 block uppercase tracking-wider">PT Kreatif Sky Abadi</span>
            </div>
          </router-link>
        </div>

        <nav class="p-3 space-y-1 text-xs font-semibold">
          <router-link
            to="/admin"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path === '/admin' ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Overview & Monitoring</span>
          </router-link>

          <router-link
            to="/admin/customers"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/customers') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Manajemen Pelanggan</span>
          </router-link>

          <router-link
            to="/admin/plans"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/plans') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Paket & Tarif Subscription</span>
          </router-link>

          <router-link
            to="/admin/reports"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/reports') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Laporan Keuangan & Export</span>
          </router-link>

          <router-link
            to="/admin/audit-logs"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/audit-logs') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Audit Trail & Security</span>
          </router-link>

          <router-link
            to="/admin/email-gateway"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/email-gateway') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Email Gateway & SMTP</span>
          </router-link>

          <router-link
            to="/admin/settings"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="$route.path.startsWith('/admin/settings') ? 'bg-red-600/20 text-red-400 border border-red-500/30' : 'text-slate-400 hover:bg-slate-900'"
          >
            <span>Pengaturan Sistem</span>
          </router-link>
        </nav>
      </div>

      <div class="p-4 border-t border-slate-800">
        <button
          @click="authStore.logout"
          class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-red-400 hover:bg-red-950/40 transition-colors"
        >
          <span>Keluar Admin</span>
        </button>
      </div>
    </aside>

    <!-- Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 px-6 bg-slate-950/80 backdrop-blur-sm border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">System Normal • Engine V1</span>
        </div>

        <router-link to="/dashboard" class="text-xs text-indigo-400 hover:text-indigo-300">
          Buka Customer View &rarr;
        </router-link>
      </header>

      <main class="flex-1 p-6 max-w-7xl w-full mx-auto">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
</script>
