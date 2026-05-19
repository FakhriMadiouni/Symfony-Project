import { Injectable, signal, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User, LoginResponse } from '../models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly TOKEN_KEY = 'mpm_token';
  private readonly USER_KEY  = 'mpm_user';

  private _user = signal<User | null>(this.loadStoredUser());
  private _token = signal<string | null>(localStorage.getItem(this.TOKEN_KEY));

  readonly user     = this._user.asReadonly();
  readonly token    = this._token.asReadonly();
  readonly isLoggedIn = computed(() => !!this._token());
  readonly isStaff    = computed(() => this._user()?.is_staff ?? false);

  constructor(private http: HttpClient, private router: Router) {}

  private loadStoredUser(): User | null {
    const raw = localStorage.getItem(this.USER_KEY);
    try { return raw ? JSON.parse(raw) : null; } catch { return null; }
  }

  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, { email, password }).pipe(
      tap(res => {
        localStorage.setItem(this.TOKEN_KEY, res.token);
        localStorage.setItem(this.USER_KEY, JSON.stringify(res.user));
        this._token.set(res.token);
        this._user.set(res.user);
      })
    );
  }

  logout(): void {
    const token = this._token();
    if (token) {
      this.http.post(`${environment.apiUrl}/auth/logout`, {}).subscribe();
    }
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
    this._token.set(null);
    this._user.set(null);
    this.router.navigate(['/auth/login']);
  }

  refreshMe(): void {
    this.http.get<{ user: User }>(`${environment.apiUrl}/auth/me`).subscribe({
      next: res => {
        this._user.set(res.user);
        localStorage.setItem(this.USER_KEY, JSON.stringify(res.user));
      }
    });
  }

  updateLocalUser(partial: Partial<User>): void {
    const current = this._user();
    if (!current) return;
    const updated = { ...current, ...partial };
    this._user.set(updated);
    localStorage.setItem(this.USER_KEY, JSON.stringify(updated));
  }
}
