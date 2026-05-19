import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
<div class="page-center">
  <div class="auth-card">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1>Welcome back</h1>
    <p class="subtitle">Sign in to your account to continue</p>

    @if (error()) {
      <div class="alert alert-error">{{ error() }}</div>
    }
    @if (needsVerify()) {
      <div class="alert alert-info">
        Email not verified.
        <a [routerLink]="['/auth/verify']" [queryParams]="{user_id: pendingUserId()}">Verify now →</a>
      </div>
    }

    <div class="form-group">
      <label>Email</label>
      <input type="email" [(ngModel)]="email" placeholder="you@example.com" autocomplete="email">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" [(ngModel)]="password" placeholder="••••••••" autocomplete="current-password"
             (keyup.enter)="submit()">
    </div>

    <div style="text-align:right;margin-bottom:1.5rem;">
      <a routerLink="/auth/forgot" style="font-size:.85rem;color:var(--muted);">Forgot password?</a>
    </div>

    <button class="btn btn-primary btn-block" (click)="submit()" [disabled]="loading()">
      @if (loading()) {
        <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span>
      }
      Sign In
    </button>

    <p style="text-align:center;margin-top:1.5rem;font-size:.9rem;color:var(--muted);">
      Don't have an account?
      <a routerLink="/auth/register" style="color:var(--accent);font-weight:600;">Register</a>
    </p>
  </div>
</div>
  `,
})
export class LoginComponent {
  email    = '';
  password = '';
  loading  = signal(false);
  error    = signal('');
  needsVerify   = signal(false);
  pendingUserId = signal<number | null>(null);

  constructor(private auth: AuthService, private router: Router) {}

  submit(): void {
    if (!this.email || !this.password) { this.error.set('Please fill in all fields.'); return; }
    this.error.set('');
    this.needsVerify.set(false);
    this.loading.set(true);
    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigate(['/home']),
      error: err => {
        const body = err.error;
        this.error.set(body?.error || 'Login failed.');
        if (body?.needs_verification) { this.needsVerify.set(true); this.pendingUserId.set(body.user_id ?? null); }
        this.loading.set(false);
      },
    });
  }
}
