import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/client';

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'customer';
  is_admin: boolean;
  two_factor_enabled?: boolean;
  customer?: {
    id: number;
    uuid: string;
    name?: string;
    business_name: string;
    status?: string;
    active_subscription?: {
      status: string;
      plan: string;
      ends_at: string;
    };
  };
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('kreatif_auth_token'));
  const user = ref<User | null>(
    localStorage.getItem('kreatif_user')
      ? JSON.parse(localStorage.getItem('kreatif_user')!)
      : null
  );

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin');

  const setAuth = (newToken: string, newUser: User) => {
    token.value = newToken;
    user.value = newUser;
    localStorage.setItem('kreatif_auth_token', newToken);
    localStorage.setItem('kreatif_user', JSON.stringify(newUser));
  };

  const clearAuth = () => {
    token.value = null;
    user.value = null;
    localStorage.removeItem('kreatif_auth_token');
    localStorage.removeItem('kreatif_user');
  };

  const fetchUser = async () => {
    if (!token.value) return;
    try {
      const response = await api.get('/auth/me');
      user.value = response.data.data;
      localStorage.setItem('kreatif_user', JSON.stringify(user.value));
    } catch {
      clearAuth();
    }
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Ignore
    } finally {
      clearAuth();
      window.location.href = '/login';
    }
  };

  return {
    token,
    user,
    isAuthenticated,
    isAdmin,
    setAuth,
    clearAuth,
    fetchUser,
    logout,
  };
});
