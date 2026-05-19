import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { UserService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { UploadUrlPipe } from '../../shared/pipes/pipes';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, UploadUrlPipe],
  template: `
<div class="container py-4" style="max-width:640px;">
  <h2 class="fw-bold mb-4" style="font-family:'Playfair Display',serif;">Account Settings</h2>

  <!-- Profile info -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-semibold mb-3">Profile Information</h5>
      @if (profileSuccess()) { <div class="alert alert-success py-2 small">{{ profileSuccess() }}</div> }
      @if (profileError()) { <div class="alert alert-danger py-2 small">{{ profileError() }}</div> }

      <div class="mb-3">
        <label class="form-label small fw-semibold">Username</label>
        <input class="form-control" [(ngModel)]="username" maxlength="50">
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Biography</label>
        <textarea class="form-control" [(ngModel)]="biography" rows="3" maxlength="1000"></textarea>
      </div>
      <button class="btn fw-semibold" style="background:var(--accent);color:#fff;"
              (click)="saveProfile()" [disabled]="savingProfile()">
        @if (savingProfile()) { <span class="spinner-border spinner-border-sm me-2"></span> }
        Save Changes
      </button>
    </div>
  </div>

  <!-- Avatar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-semibold mb-3">Profile Picture</h5>
      <div class="d-flex align-items-center gap-4 mb-3">
        <img [src]="auth.user()?.profile_picture | uploadUrl:'avatars'"
             class="rounded-circle border" width="80" height="80" style="object-fit:cover;" alt="avatar">
        <div>
          <input type="file" class="form-control form-control-sm mb-2" accept="image/*"
                 (change)="onAvatarChange($event)" [disabled]="savingAvatar()">
          @if (auth.user()?.profile_picture) {
            <button class="btn btn-sm btn-outline-danger" (click)="deleteAvatar()" [disabled]="savingAvatar()">
              Remove picture
            </button>
          }
          @if (avatarMsg()) { <div class="small mt-1" [class.text-success]="!avatarError()" [class.text-danger]="avatarError()">{{ avatarMsg() }}</div> }
        </div>
      </div>
    </div>
  </div>

  <!-- Change password -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-semibold mb-3">Change Password</h5>
      <p class="text-muted small">Use the "Forgot Password" flow to change your password.</p>
      <a href="/auth/forgot" class="btn btn-sm btn-outline-secondary">Change Password →</a>
    </div>
  </div>

  <!-- Account status -->
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h5 class="fw-semibold mb-3">Account Status</h5>
      <div class="row g-2">
        <div class="col-6">
          <div class="p-2 rounded small" style="background:var(--card-bg2);">
            <span class="text-muted">Account Ban:</span>
            <span class="ms-2 fw-semibold" [class.text-danger]="auth.user()?.ban_status === 1" [class.text-success]="auth.user()?.ban_status === 0">
              {{ auth.user()?.ban_status === 1 ? 'Banned' : 'Good standing' }}
            </span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded small" style="background:var(--card-bg2);">
            <span class="text-muted">Ad Posting:</span>
            <span class="ms-2 fw-semibold" [class.text-danger]="auth.user()?.ad_ban_status === 1" [class.text-success]="auth.user()?.ad_ban_status === 0">
              {{ auth.user()?.ad_ban_status === 1 ? 'Banned' : 'Allowed' }}
            </span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded small" style="background:var(--card-bg2);">
            <span class="text-muted">Messaging:</span>
            <span class="ms-2 fw-semibold" [class.text-danger]="auth.user()?.mute_status === 1" [class.text-success]="auth.user()?.mute_status === 0">
              {{ auth.user()?.mute_status === 1 ? 'Muted' : 'Allowed' }}
            </span>
          </div>
        </div>
        <div class="col-6">
          <div class="p-2 rounded small" style="background:var(--card-bg2);">
            <span class="text-muted">Honor Points:</span>
            <span class="ms-2 fw-semibold" style="color:var(--accent);">{{ auth.user()?.honor_points }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
  `,
})
export class SettingsComponent {
  username  = this.auth.user()?.username ?? '';
  biography = this.auth.user()?.biography ?? '';

  savingProfile = signal(false);
  profileSuccess = signal('');
  profileError   = signal('');

  savingAvatar = signal(false);
  avatarMsg    = signal('');
  avatarError  = signal(false);

  constructor(private userService: UserService, public auth: AuthService) {}

  saveProfile(): void {
    this.profileSuccess.set(''); this.profileError.set(''); this.savingProfile.set(true);
    this.userService.updateProfile({ username: this.username, biography: this.biography }).subscribe({
      next: res => {
        this.auth.updateLocalUser({ username: this.username, biography: this.biography });
        this.profileSuccess.set('Profile updated!'); this.savingProfile.set(false);
      },
      error: err => { this.profileError.set(err.error?.error || 'Update failed.'); this.savingProfile.set(false); },
    });
  }

  onAvatarChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    this.savingAvatar.set(true); this.avatarMsg.set(''); this.avatarError.set(false);
    this.userService.uploadAvatar(file).subscribe({
      next: res => {
        this.auth.updateLocalUser({ profile_picture: res.file_name });
        this.avatarMsg.set('Avatar updated!'); this.savingAvatar.set(false);
      },
      error: err => { this.avatarMsg.set(err.error?.error || 'Upload failed.'); this.avatarError.set(true); this.savingAvatar.set(false); },
    });
  }

  deleteAvatar(): void {
    this.savingAvatar.set(true);
    this.userService.deleteAvatar().subscribe({
      next: () => { this.auth.updateLocalUser({ profile_picture: null }); this.avatarMsg.set('Picture removed.'); this.savingAvatar.set(false); },
      error: () => this.savingAvatar.set(false),
    });
  }
}
