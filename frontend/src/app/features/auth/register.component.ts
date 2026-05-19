import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

const API = environment.apiUrl;

// ── Register ──────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
<div class="page-center">
  <div class="auth-card">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1>Create account</h1>
    <p class="subtitle">Join MyPocketMarket today</p>

    @if (error()) { <div class="alert alert-error">{{ error() }}</div> }

    @if (!done()) {
      <div class="form-group">
        <label>Username</label>
        <input [(ngModel)]="username" placeholder="your_username" minlength="3" maxlength="50">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" [(ngModel)]="email" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" [(ngModel)]="password" placeholder="At least 8 characters"
               (keyup.enter)="submit()">
      </div>
      <button class="btn btn-primary btn-block" (click)="submit()" [disabled]="loading()">
        @if (loading()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        Create Account
      </button>
    } @else {
      <div style="text-align:center;padding:1rem 0;">
        <div style="font-size:3rem;margin-bottom:1rem;">📧</div>
        <p style="color:var(--muted);margin-bottom:1.5rem;">Check your email for a 6-digit verification code.</p>
        <a [routerLink]="['/auth/verify']" [queryParams]="{user_id: userId()}"
           class="btn btn-primary btn-block">Enter Verification Code</a>
      </div>
    }

    <p style="text-align:center;margin-top:1.5rem;font-size:.9rem;color:var(--muted);">
      Already have an account?
      <a routerLink="/auth/login" style="color:var(--accent);font-weight:600;">Sign in</a>
    </p>
  </div>
</div>
  `,
})
export class RegisterComponent {
  username = ''; email = ''; password = '';
  loading = signal(false); error = signal('');
  done = signal(false); userId = signal(0);

  constructor(private http: HttpClient) {}

  submit(): void {
    if (!this.username || !this.email || !this.password) { this.error.set('Please fill in all fields.'); return; }
    this.error.set(''); this.loading.set(true);
    this.http.post<any>(`${API}/auth/register`, { username: this.username, email: this.email, password: this.password }).subscribe({
      next: res => { this.userId.set(res.user_id); this.done.set(true); this.loading.set(false); },
      error: err => { this.error.set(err.error?.error || 'Registration failed.'); this.loading.set(false); },
    });
  }
}

// ── Verify ────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-verify',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
<div class="page-center">
  <div class="auth-card">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1>Verify email</h1>
    <p class="subtitle">Enter the 6-digit code sent to your email</p>

    @if (error()) { <div class="alert alert-error">{{ error() }}</div> }
    @if (success()) {
      <div class="alert alert-success">{{ success() }}</div>
      <a routerLink="/auth/login" class="btn btn-primary btn-block" style="margin-top:.5rem;">Continue to Login</a>
    } @else {
      <div class="form-group">
        <label>Verification Code</label>
        <input [(ngModel)]="code" maxlength="6" placeholder="000000"
               style="letter-spacing:10px;font-size:1.4rem;text-align:center;"
               (keyup.enter)="submit()">
      </div>
      <button class="btn btn-primary btn-block" (click)="submit()" [disabled]="loading()">
        @if (loading()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        Verify
      </button>
      <button class="btn btn-ghost btn-block" style="margin-top:.75rem;" (click)="resend()" [disabled]="resending()">
        {{ resending() ? 'Sending…' : 'Resend code' }}
      </button>
    }
  </div>
</div>
  `,
})
export class VerifyComponent {
  code = '';
  loading = signal(false); error = signal(''); success = signal(''); resending = signal(false);
  userId = 0; type = 'registration';

  constructor(private http: HttpClient, private router: Router) {
    const params = new URLSearchParams(window.location.search);
    this.userId = +(params.get('user_id') || 0);
    this.type   = params.get('type') || 'registration';
  }

  submit(): void {
    this.error.set(''); this.loading.set(true);
    this.http.post<any>(`${API}/auth/verify-code`, { user_id: this.userId, code: this.code, type: this.type }).subscribe({
      next: () => { this.success.set('Email verified! You can now log in.'); this.loading.set(false); },
      error: err => { this.error.set(err.error?.error || 'Verification failed.'); this.loading.set(false); },
    });
  }

  resend(): void {
    this.resending.set(true);
    this.http.post<any>(`${API}/auth/send-verification`, { user_id: this.userId, type: this.type }).subscribe({
      next: () => this.resending.set(false),
      error: () => this.resending.set(false),
    });
  }
}

// ── Forgot ────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-forgot',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
<div class="page-center">
  <div class="auth-card">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1>Forgot password</h1>
    <p class="subtitle">We'll send a reset code to your email</p>

    @if (sent()) {
      <div class="alert alert-success">Reset code sent! Check your email.</div>
      <a routerLink="/auth/reset" class="btn btn-primary btn-block" style="margin-top:.5rem;">Enter Reset Code</a>
    } @else {
      <div class="form-group">
        <label>Email</label>
        <input type="email" [(ngModel)]="email" placeholder="you@example.com" (keyup.enter)="submit()">
      </div>
      <button class="btn btn-primary btn-block" (click)="submit()" [disabled]="loading()">
        @if (loading()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        Send Reset Code
      </button>
    }
    <p style="text-align:center;margin-top:1.5rem;font-size:.9rem;">
      <a routerLink="/auth/login" style="color:var(--muted);">← Back to login</a>
    </p>
  </div>
</div>
  `,
})
export class ForgotComponent {
  email = ''; loading = signal(false); sent = signal(false);
  constructor(private http: HttpClient) {}
  submit(): void {
    this.loading.set(true);
    this.http.post<any>(`${API}/auth/forgot-password`, { email: this.email }).subscribe({
      next: () => { this.sent.set(true); this.loading.set(false); },
      error: () => { this.sent.set(true); this.loading.set(false); },
    });
  }
}

// ── Reset ─────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-reset',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
<div class="page-center">
  <div class="auth-card">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1>Reset password</h1>
    <p class="subtitle">Enter the code from your email and your new password</p>

    @if (error()) { <div class="alert alert-error">{{ error() }}</div> }
    @if (done()) {
      <div class="alert alert-success">Password reset! You can now log in.</div>
      <a routerLink="/auth/login" class="btn btn-primary btn-block" style="margin-top:.5rem;">Login</a>
    } @else {
      <div class="form-group">
        <label>Email</label>
        <input type="email" [(ngModel)]="email" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label>Reset Code</label>
        <input [(ngModel)]="code" maxlength="6" placeholder="000000"
               style="letter-spacing:10px;font-size:1.2rem;text-align:center;">
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" [(ngModel)]="password" placeholder="At least 8 characters" (keyup.enter)="submit()">
      </div>
      <button class="btn btn-primary btn-block" (click)="submit()" [disabled]="loading()">
        @if (loading()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        Reset Password
      </button>
    }
  </div>
</div>
  `,
})
export class ResetComponent {
  email = ''; code = ''; password = '';
  loading = signal(false); error = signal(''); done = signal(false);
  constructor(private http: HttpClient) {}
  submit(): void {
    this.error.set(''); this.loading.set(true);
    this.http.post<any>(`${API}/auth/reset-password`, { email: this.email, code: this.code, password: this.password }).subscribe({
      next: () => { this.done.set(true); this.loading.set(false); },
      error: err => { this.error.set(err.error?.error || 'Reset failed.'); this.loading.set(false); },
    });
  }
}
