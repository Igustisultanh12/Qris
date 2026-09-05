import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Public Views
import HomeView from '../views/public/HomeView.vue';
import PricingView from '../views/public/PricingView.vue';
import ApiDocsView from '../views/public/ApiDocsView.vue';
import LegalView from '../views/public/LegalView.vue';

// Auth Views
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';

// Customer Views
import CustomerDashboardView from '../views/customer/DashboardView.vue';
import CustomerGeneratorView from '../views/customer/GeneratorView.vue';
import CustomerMerchantsView from '../views/customer/MerchantsView.vue';
import CustomerTransactionsView from '../views/customer/TransactionsView.vue';
import CustomerApiKeysView from '../views/customer/ApiKeysView.vue';
import CustomerWebhooksView from '../views/customer/WebhooksView.vue';
import CustomerBillingView from '../views/customer/BillingView.vue';
import CustomerTicketsView from '../views/customer/TicketsView.vue';
import CustomerProfileView from '../views/customer/ProfileView.vue';

// Admin Views
import AdminDashboardView from '../views/admin/DashboardView.vue';
import AdminCustomersView from '../views/admin/CustomersView.vue';
import AdminPlansView from '../views/admin/PlansView.vue';
import AdminReportsView from '../views/admin/ReportsView.vue';
import AdminAuditLogsView from '../views/admin/AuditLogsView.vue';
import AdminEmailGatewayView from '../views/admin/EmailGatewayView.vue';
import AdminSettingsView from '../views/admin/SettingsView.vue';

const routes: RouteRecordRaw[] = [
  // Public
  { path: '/', name: 'home', component: HomeView },
  { path: '/pricing', name: 'pricing', component: PricingView },
  { path: '/api-docs', name: 'api-docs', component: ApiDocsView },
  { path: '/legal', name: 'legal', component: LegalView },

  // Auth
  { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: RegisterView, meta: { guestOnly: true } },

  // Customer Portal
  { path: '/dashboard', name: 'customer-dashboard', component: CustomerDashboardView, meta: { requiresAuth: true } },
  { path: '/customer/generator', name: 'customer-generator', component: CustomerGeneratorView, meta: { requiresAuth: true } },
  { path: '/customer/merchants', name: 'customer-merchants', component: CustomerMerchantsView, meta: { requiresAuth: true } },
  { path: '/customer/transactions', name: 'customer-transactions', component: CustomerTransactionsView, meta: { requiresAuth: true } },
  { path: '/customer/api-keys', name: 'customer-api-keys', component: CustomerApiKeysView, meta: { requiresAuth: true } },
  { path: '/customer/webhooks', name: 'customer-webhooks', component: CustomerWebhooksView, meta: { requiresAuth: true } },
  { path: '/customer/billing', name: 'customer-billing', component: CustomerBillingView, meta: { requiresAuth: true } },
  { path: '/customer/tickets', name: 'customer-tickets', component: CustomerTicketsView, meta: { requiresAuth: true } },
  { path: '/customer/profile', name: 'customer-profile', component: CustomerProfileView, meta: { requiresAuth: true } },

  // Admin Portal
  { path: '/admin', redirect: '/admin/dashboard' },
  { path: '/admin/dashboard', name: 'admin-dashboard', component: AdminDashboardView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/customers', name: 'admin-customers', component: AdminCustomersView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/plans', name: 'admin-plans', component: AdminPlansView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/reports', name: 'admin-reports', component: AdminReportsView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/audit-logs', name: 'admin-audit-logs', component: AdminAuditLogsView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/email-gateway', name: 'admin-email-gateway', component: AdminEmailGatewayView, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/settings', name: 'admin-settings', component: AdminSettingsView, meta: { requiresAuth: true, requiresAdmin: true } },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore();

  if (to.meta.guestOnly && auth.isAuthenticated) {
    if (auth.isAdmin) {
      return next('/admin/dashboard');
    }
    return next('/dashboard');
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ path: '/login', query: { redirect: to.fullPath } });
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return next('/dashboard');
  }

  next();
});

export default router;
