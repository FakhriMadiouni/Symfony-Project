import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AdService, ConversationService, ReportService, AdminService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { AdDetail } from '../../core/models';
import { UploadUrlPipe, TimeAgoPipe } from '../../shared/pipes/pipes';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-ad-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, UploadUrlPipe, TimeAgoPipe],
  template: `
<div class="container-xl py-4">
  @if (loading()) {
    <div class="text-center py-5"><div class="spinner-border" style="color:var(--accent);"></div></div>
  } @else if (!ad()) {
    <div class="alert alert-warning">Ad not found or unavailable.</div>
  } @else {
    <div class="row g-4">
      <!-- Media -->
      <div class="col-lg-7">
        <div class="mb-2 rounded overflow-hidden d-flex align-items-center justify-content-center"
             style="background:#111;height:420px;">
          @if (ad()!.media.length > 0) {
            @if (ad()!.media[mediaIndex()].file_type === 'img') {
              <img [src]="ad()!.media[mediaIndex()].file_name | uploadUrl:'images'"
                   class="w-100 h-100" style="object-fit:contain;" alt="media">
            } @else {
              <video [src]="env.uploadsUrl + '/videos/' + ad()!.media[mediaIndex()].file_name"
                     controls class="w-100 h-100"></video>
            }
          } @else {
            <img src="/assets/no-image.png" class="mh-100" style="object-fit:contain;" alt="">
          }
        </div>
        @if (ad()!.media.length > 1) {
          <div class="d-flex gap-2 flex-wrap mt-2">
            @for (m of ad()!.media; track m.media_id; let i = $index) {
              <div (click)="mediaIndex.set(i)" class="rounded overflow-hidden"
                   [style.border]="mediaIndex()===i?'2px solid var(--accent)':'2px solid transparent'"
                   style="width:72px;height:72px;cursor:pointer;">
                @if (m.file_type === 'img') {
                  <img [src]="m.file_name | uploadUrl:'images'" style="width:100%;height:100%;object-fit:cover;" alt="">
                } @else {
                  <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark text-white small">▶</div>
                }
              </div>
            }
          </div>
        }
      </div>

      <!-- Details -->
      <div class="col-lg-5">
        <div class="d-flex gap-2 mb-2 flex-wrap">
          @if (ad()!.active === 0) { <span class="badge bg-secondary">Expired</span> }
          @if (ad()!.ban_status === 1) { <span class="badge bg-danger">Banned</span> }
          <span class="badge" style="background:var(--accent-soft);color:var(--accent);">
            {{ ad()!.category.name }} › {{ ad()!.subcategory.name }}
          </span>
        </div>

        <h1 class="h3 fw-bold mb-2" style="font-family:'Playfair Display',serif;">{{ ad()!.title }}</h1>
        <div class="mb-3 fw-bold" style="font-size:1.8rem;color:var(--accent);">
          {{ ad()!.price === '0.00' ? 'Free' : (ad()!.price | currency) }}
        </div>
        <p class="text-muted small mb-1">📍 {{ ad()!.country.name }}{{ ad()!.region_name ? ', ' + ad()!.region_name : '' }}</p>
        <p class="text-muted small mb-3">🕐 Posted {{ ad()!.creation_date | timeAgo }}</p>

        @if (ad()!.description) {
          <div class="mb-4 p-3 rounded" style="background:var(--card-bg2);white-space:pre-wrap;line-height:1.7;">{{ ad()!.description }}</div>
        }

        <!-- Seller -->
        <a [routerLink]="['/profile', ad()!.user.user_id]" class="text-decoration-none">
          <div class="card border-0 shadow-sm mb-3 p-3 d-flex flex-row align-items-center gap-3">
            <img [src]="ad()!.user.profile_picture | uploadUrl:'avatars'" class="rounded-circle" width="48" height="48" style="object-fit:cover;" alt="seller">
            <div>
              <p class="fw-semibold mb-0" style="color:var(--text);">{{ ad()!.user.username }}</p>
              <p class="mb-0 small text-muted">View profile →</p>
            </div>
          </div>
        </a>

        <!-- Buyer actions -->
        @if (auth.isLoggedIn() && auth.user()?.user_id !== ad()!.user.user_id) {
          @if (ad()!.active === 1 && ad()!.ban_status === 0) {
            @if (!showMsg()) {
              <button class="btn w-100 mb-2 fw-semibold" style="background:var(--accent);color:#fff;" (click)="showMsg.set(true)">
                💬 Contact Seller
              </button>
            } @else {
              <div class="mb-3">
                <textarea class="form-control mb-2" [(ngModel)]="msgText" rows="3" placeholder="Write your first message…" maxlength="1000"></textarea>
                <div class="d-flex gap-2">
                  <button class="btn flex-fill fw-semibold" style="background:var(--accent);color:#fff;" (click)="sendMessage()" [disabled]="sendingMsg()">
                    @if (sendingMsg()) { <span class="spinner-border spinner-border-sm me-1"></span> } Send
                  </button>
                  <button class="btn btn-outline-secondary" (click)="showMsg.set(false)">Cancel</button>
                </div>
                @if (msgSuccess()) { <div class="alert alert-success small mt-2 py-2">{{ msgSuccess() }}</div> }
                @if (msgError()) { <div class="alert alert-danger small mt-2 py-2">{{ msgError() }}</div> }
              </div>
            }
          }
          <button class="btn btn-sm btn-outline-secondary w-100 mt-1" (click)="showReport.set(!showReport())">🚩 Report this ad</button>
          @if (showReport()) {
            <div class="mt-2">
              <textarea class="form-control form-control-sm mb-2" [(ngModel)]="reportReason" rows="2" placeholder="Describe the issue…"></textarea>
              <button class="btn btn-sm btn-danger w-100" (click)="submitReport()" [disabled]="reporting()">Submit Report</button>
              @if (reportDone()) { <div class="alert alert-success small mt-2 py-1">Report submitted. Thank you.</div> }
            </div>
          }
        }

        <!-- Owner actions -->
        @if (auth.user()?.user_id === ad()!.user.user_id) {
          <div class="d-flex gap-2 mt-2">
            <a [routerLink]="['/ads', ad()!.ad_id, 'edit']" class="btn btn-outline-secondary flex-fill">✏ Edit</a>
            <button class="btn btn-outline-danger flex-fill" (click)="deleteAd()">🗑 Delete</button>
          </div>
        }

        <!-- Staff actions -->
        @if (auth.isStaff()) {
          <div class="mt-3 p-3 rounded" style="border:2px solid var(--accent);">
            <p class="small fw-bold mb-2" style="color:var(--accent);">⚙ Staff Panel</p>
            <div class="d-flex gap-2">
              @if (ad()!.ban_status === 0) {
                <button class="btn btn-sm btn-danger flex-fill" (click)="staffBanAd()">Ban Ad</button>
              } @else {
                <button class="btn btn-sm btn-success flex-fill" (click)="staffUnbanAd()">Unban Ad</button>
              }
              <a [routerLink]="['/admin/users', ad()!.user.user_id]" class="btn btn-sm btn-outline-secondary flex-fill">Manage Seller</a>
            </div>
          </div>
        }
      </div>
    </div>
  }
</div>
  `,
})
export class AdDetailComponent implements OnInit {
  env = environment;
  ad = signal<AdDetail | null>(null);
  loading = signal(true);
  mediaIndex = signal(0);
  showMsg = signal(false); msgText = ''; sendingMsg = signal(false); msgSuccess = signal(''); msgError = signal('');
  showReport = signal(false); reportReason = ''; reporting = signal(false); reportDone = signal(false);

  constructor(
    private route: ActivatedRoute, private router: Router,
    private adService: AdService, private convService: ConversationService,
    private reportService: ReportService, private adminService: AdminService,
    public auth: AuthService,
  ) {}

  ngOnInit(): void {
    const id = +this.route.snapshot.paramMap.get('id')!;
    this.adService.get(id).subscribe({ next: r => { this.ad.set(r.ad); this.loading.set(false); }, error: () => this.loading.set(false) });
  }

  sendMessage(): void {
    if (!this.msgText.trim()) return;
    this.sendingMsg.set(true); this.msgError.set('');
    this.convService.start(this.ad()!.ad_id, this.msgText).subscribe({
      next: () => { this.msgSuccess.set('Message sent! Go to Conversations to continue.'); this.sendingMsg.set(false); },
      error: err => { this.msgError.set(err.error?.error || 'Failed to send.'); this.sendingMsg.set(false); },
    });
  }

  submitReport(): void {
    this.reporting.set(true);
    this.reportService.submit({ type: 'ad', reported_user_id: this.ad()!.user.user_id, reference_id: this.ad()!.ad_id, reason: this.reportReason }).subscribe({
      next: () => { this.reportDone.set(true); this.reporting.set(false); this.showReport.set(false); },
      error: () => this.reporting.set(false),
    });
  }

  deleteAd(): void {
    if (!confirm('Delete this ad permanently?')) return;
    this.adService.delete(this.ad()!.ad_id).subscribe({ next: () => this.router.navigate(['/ads']) });
  }

  staffBanAd(): void {
    if (!confirm('Ban this ad?')) return;
    this.adminService.banAd(this.ad()!.ad_id).subscribe({ next: () => this.ngOnInit() });
  }

  staffUnbanAd(): void {
    this.adminService.unbanAd(this.ad()!.ad_id).subscribe({ next: () => this.ngOnInit() });
  }
}
