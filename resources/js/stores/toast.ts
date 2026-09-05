import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ToastItem {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message?: string;
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<ToastItem[]>([]);

  const add = (type: ToastItem['type'], title: string, message?: string, duration = 4000) => {
    const id = Math.random().toString(36).substring(2, 9);
    const toast: ToastItem = { id, type, title, message };
    toasts.value.push(toast);

    if (duration > 0) {
      setTimeout(() => {
        remove(id);
      }, duration);
    }
  };

  const success = (title: string, message?: string) => add('success', title, message);
  const error = (title: string, message?: string) => add('error', title, message, 6000);
  const warning = (title: string, message?: string) => add('warning', title, message);
  const info = (title: string, message?: string) => add('info', title, message);

  const remove = (id: string) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  };

  return {
    toasts,
    add,
    success,
    error,
    warning,
    info,
    remove,
  };
});
