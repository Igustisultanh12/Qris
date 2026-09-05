import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  const isDark = ref(localStorage.getItem('qmis_theme') === 'dark');

  const applyTheme = () => {
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
