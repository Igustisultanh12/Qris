<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans transition-colors duration-200">
    <router-view />
    <ToastContainer />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import ToastContainer from './components/ToastContainer.vue';
import { useThemeStore } from './stores/theme';
import { useAuthStore } from './stores/auth';

const theme = useThemeStore();
const auth = useAuthStore();

onMounted(() => {
  theme.init();
  auth.fetchUser();
});
</script>
