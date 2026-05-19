import { Routes } from '@angular/router';
import { authGuard, guestGuard, staffGuard } from './core/guards/guards';

export const routes: Routes = [
  { path: '', redirectTo: '/home', pathMatch: 'full' },

  // Auth (guests only)
  {
    path: 'auth',
    canActivate: [guestGuard],
    children: [
      { path: 'login',    loadComponent: () => import('./features/auth/login.component').then(m => m.LoginComponent) },
      { path: 'register', loadComponent: () => import('./features/auth/register.component').then(m => m.RegisterComponent) },
      { path: 'verify',   loadComponent: () => import('./features/auth/register.component').then(m => m.VerifyComponent) },
      { path: 'forgot',   loadComponent: () => import('./features/auth/register.component').then(m => m.ForgotComponent) },
      { path: 'reset',    loadComponent: () => import('./features/auth/register.component').then(m => m.ResetComponent) },
      { path: '', redirectTo: 'login', pathMatch: 'full' },
    ],
  },

  // Home feed
  { path: 'home', loadComponent: () => import('./features/home/home.component').then(m => m.HomeComponent), canActivate: [authGuard] },

  // Search / Browse
  { path: 'search', loadComponent: () => import('./features/search/search.component').then(m => m.SearchComponent), canActivate: [authGuard] },

  // Ads (all require login)
  { path: 'ads',          loadComponent: () => import('./features/ads/ad-list.component').then(m => m.AdListComponent),   canActivate: [authGuard] },
  { path: 'ads/new',      loadComponent: () => import('./features/ads/ad-form.component').then(m => m.AdFormComponent),   canActivate: [authGuard] },
  { path: 'ads/:id',      loadComponent: () => import('./features/ads/ad-detail.component').then(m => m.AdDetailComponent), canActivate: [authGuard] },
  { path: 'ads/:id/edit', loadComponent: () => import('./features/ads/ad-form.component').then(m => m.AdFormComponent),   canActivate: [authGuard] },

  // Profile
  { path: 'profile/:id', loadComponent: () => import('./features/profile/profile.component').then(m => m.ProfileComponent),   canActivate: [authGuard] },
  { path: 'settings',    loadComponent: () => import('./features/profile/settings.component').then(m => m.SettingsComponent), canActivate: [authGuard] },

  // Store
  { path: 'store', loadComponent: () => import('./features/store/store.component').then(m => m.StoreComponent), canActivate: [authGuard] },

  // Conversations
  { path: 'conversations',     loadComponent: () => import('./features/conversations/conversation-list.component').then(m => m.ConversationListComponent),     canActivate: [authGuard] },
  { path: 'conversations/:id', loadComponent: () => import('./features/conversations/conversation-detail.component').then(m => m.ConversationDetailComponent), canActivate: [authGuard] },

  // Notifications
  { path: 'notifications', loadComponent: () => import('./features/notifications/notifications.component').then(m => m.NotificationsComponent), canActivate: [authGuard] },

  // Support
  { path: 'support',     loadComponent: () => import('./features/support/support-list.component').then(m => m.SupportListComponent),     canActivate: [authGuard] },
  { path: 'support/new', loadComponent: () => import('./features/support/support-new.component').then(m => m.SupportNewComponent),       canActivate: [authGuard] },
  { path: 'support/:id', loadComponent: () => import('./features/support/support-detail.component').then(m => m.SupportDetailComponent), canActivate: [authGuard] },

  // Report
  { path: 'report', loadComponent: () => import('./features/report/report.component').then(m => m.ReportComponent), canActivate: [authGuard] },

  // Admin (staff only)
  {
    path: 'admin',
    canActivate: [staffGuard],
    children: [
      { path: '',          loadComponent: () => import('./features/admin/admin-dashboard.component').then(m => m.AdminDashboardComponent) },
      { path: 'reports',   loadComponent: () => import('./features/admin/admin-reports.component').then(m => m.AdminReportsComponent) },
      { path: 'support',   loadComponent: () => import('./features/admin/admin-support.component').then(m => m.AdminSupportComponent) },
      { path: 'users/:id', loadComponent: () => import('./features/admin/admin-user.component').then(m => m.AdminUserComponent) },
    ],
  },

  { path: '**', redirectTo: '/home' },
];
