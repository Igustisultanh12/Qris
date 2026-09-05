import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  const getInitialTheme = (): boolean => {
    if (typeof window === 'undefined') return false;
    const saved = localStorage.getItem('qmis_theme');
    if (saved === 'dark') return true;
    if (saved === 'light') return false;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  };

  const isDark = ref<boolean>(getInitialTheme());

  const applyTheme = () => {
    if (typeof document === 'undefined') return;
    if (isDark.value) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('qmis_theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('qmis_theme', 'light');
    }
  };

  const toggle = () => {
    isDark.value = !isDark.value;
    applyTheme();
  };

  const init = () => {
    applyTheme();
  };

  return {
    isDark,
    toggle,
    init,
  };
});
