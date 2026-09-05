import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('kreatif_auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Generate client-side request ID if not present
  if (!config.headers['X-Request-ID']) {
    config.headers['X-Request-ID'] = 'req_' + Math.random().toString(36).substring(2, 15);
  }

  return config;
});

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !window.location.pathname.startsWith('/login')) {
      localStorage.removeItem('kreatif_auth_token');
      localStorage.removeItem('kreatif_user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
