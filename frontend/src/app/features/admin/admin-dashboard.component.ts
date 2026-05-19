import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { AdminService, UserService, SupportService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { Report, AdminSupportTicket, PublicUser } from '../../core/models';
import { TimeAgoPipe } from '../../shared/pipes/pipes';

// ── Dashboard ─────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  template: `
<div class="container py-4">
  <h2 class="fw-bold mb-1" style="font-family:'Playfair Display',serif;">⚙ Staff Panel</h2>
  <p class="text-muted mb-4">Welcome, {{ auth.user()?.username }} — {{ auth.user()?.staff_division }} / {{ auth.user()?.staff_rank }}</p>

  <div class="row g-4">
    <div class="col-md-4">
      <a routerLink="/admin/reports" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 p-4 text-center admin-card">
          <div style="font-size:2.5rem;">🚩</div>
          <h5 class="mt-2 fw-semibold">Reports</h5>
          <p class="text-muted small mb-0">Review and close user reports</p>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a routerLink="/admin/support" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 p-4 text-center admin-card">
          <div style="font-size:2.5rem;">🎧</div>
          <h5 class="mt-2 fw-semibold">Support Tickets</h5>
          <p class="text-muted small mb-0">Reply to open support requests</p>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-4 text-center" style="opacity:.6;">
        <div style="font-size:2.5rem;">👤</div>
        <h5 class="mt-2 fw-semibold">User Search</h5>
        <p class="text-muted small mb-3">Find a user to moderate</p>
        <input class="form-control form-control-sm text-center" [(ngModel)]="searchId"
               placeholder="Enter user ID" type="number" [ngModel]="searchId">
        <a [routerLink]="['/admin/users', searchId]" class="btn btn-sm mt-2 w-100"
           style="background:var(--accent);color:#fff;" [class.disabled]="!searchId">Go to User →</a>
      </div>
    </div>
  </div>
</div>
  `,
  styles: [`.admin-card { transition:.15s; cursor:pointer; } .admin-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1)!important; }`]
})
export class AdminDashboardComponent {
  searchId: any = '';
  constructor(public auth: AuthService) {}
}

// ── Reports ───────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-admin-reports',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TimeAgoPipe],
  template: `
<div class="container py-4">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a routerLink="/admin" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h2 class="fw-bold mb-0" style="font-family:'Playfair Display',serif;">🚩 Open Reports</h2>
  </div>

  @if (loading()) { <div class="text-center py-3"><div class="spinner-border" style="color:var(--accent);"></div></div> }
  @else if (reports().length === 0) { <p class="text-muted">No open reports.</p> }
  @else {
    <div class="list-group shadow-sm">
      @for (r of reports(); track r.report_id) {
        <div class="list-group-item border-0 py-3">
          <div class="d-flex flex-wrap gap-3 align-items-start">
            <div class="flex-fill">
              <div class="d-flex gap-2 align-items-center mb-1">
                <span class="badge" style="background:var(--accent);">{{ r.type }}</span>
                <span class="small text-muted">{{ r.date | timeAgo }}</span>
              </div>
              <p class="mb-1 small">
                Reporter: <a [routerLink]="['/admin/users', r.reporter.user_id]" class="fw-semibold">{{ r.reporter.username }}</a>
                → Reported: <a [routerLink]="['/admin/users', r.reported_user.user_id]" class="fw-semibold" style="color:var(--accent);">{{ r.reported_user.username }}</a>
              </p>
              @if (r.reason) { <p class="mb-0 small text-muted">Reason: {{ r.reason }}</p> }
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
              <button class="btn btn-sm btn-success" (click)="close(r.report_id, 'resolve')">✅ Resolve</button>
              <button class="btn btn-sm btn-outline-secondary" (click)="close(r.report_id, 'reject')">✗ Reject</button>
            </div>
          </div>
        </div>
      }
    </div>
  }
</div>
  `,
})
export class AdminReportsComponent implements OnInit {
  reports = signal<Report[]>([]);
  loading = signal(true);
  constructor(private adminService: AdminService) {}
  ngOnInit(): void {
    this.adminService.getReports().subscribe({
      next: r => { this.reports.set(r.reports); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
  close(id: number, action: 'resolve' | 'reject'): void {
    this.adminService.closeReport(id, action).subscribe({
      next: () => this.reports.update(rs => rs.filter(r => r.report_id !== id)),
    });
  }
}

// ── Support ───────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-admin-support',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TimeAgoPipe],
  template: `
<div class="container py-4" style="max-width:800px;">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a routerLink="/admin" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h2 class="fw-bold mb-0" style="font-family:'Playfair Display',serif;">🎧 Support Tickets</h2>
  </div>

  @if (loading()) { <div class="text-center py-3"><div class="spinner-border" style="color:var(--accent);"></div></div> }
  @else if (tickets().length === 0) { <p class="text-muted">No open tickets.</p> }
  @else {
    @if (!selected()) {
      <div class="list-group shadow-sm">
        @for (t of tickets(); track t.support_conv_id) {
          <div class="list-group-item list-group-item-action border-0 py-3" (click)="select(t)" style="cursor:pointer;">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="fw-semibold mb-1">{{ t.subject }}</p>
                <span class="small text-muted">by <strong>{{ t.user.username }}</strong> · {{ t.last_reply_date | timeAgo }}</span>
              </div>
              <span class="badge bg-success ms-3">open</span>
            </div>
          </div>
        }
      </div>
    } @else {
      <div class="d-flex align-items-center gap-3 mb-3">
        <button class="btn btn-sm btn-outline-secondary" (click)="selected.set(null)">← Tickets</button>
        <span class="fw-semibold">{{ selected()!.subject }}</span>
        <button class="btn btn-sm btn-outline-danger ms-auto" (click)="closeTicket(selected()!.support_conv_id)">Close Ticket</button>
      </div>
      <div class="d-flex flex-column gap-3 mb-3" style="max-height:400px;overflow-y:auto;">
        @for (m of selMsgs(); track m.support_msg_id) {
          <div class="d-flex" [class.justify-content-end]="m.is_staff === 1">
            <div class="card border-0 shadow-sm p-3" style="max-width:80%;"
                 [style.background]="m.is_staff ? 'var(--accent-soft)' : 'var(--card-bg2)'">
              @if (m.is_staff === 1) { <p class="mb-1 small fw-semibold" style="color:var(--accent);">Staff Reply</p> }
              <p class="mb-1 small" style="white-space:pre-wrap;">{{ m.content }}</p>
              <p class="mb-0 small text-muted">{{ m.sent_date | timeAgo }}</p>
            </div>
          </div>
        }
      </div>
      <div class="d-flex gap-2">
        <textarea class="form-control" [(ngModel)]="replyText" rows="3" placeholder="Your reply…"></textarea>
        <button class="btn px-3" style="background:var(--accent);color:#fff;" (click)="sendReply()" [disabled]="replying()">
          @if (replying()) { <span class="spinner-border spinner-border-sm"></span> } @else { ➤ }
        </button>
      </div>
    }
  }
</div>
  `,
})
export class AdminSupportComponent implements OnInit {
  tickets  = signal<AdminSupportTicket[]>([]);
  selected = signal<AdminSupportTicket | null>(null);
  selMsgs  = signal<any[]>([]);
  loading  = signal(true);
  replyText = ''; replying = signal(false);

  constructor(private adminService: AdminService, private supportService: SupportService) {}

  ngOnInit(): void {
    this.adminService.getSupportTickets().subscribe({
      next: r => { this.tickets.set(r.conversations); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  select(t: AdminSupportTicket): void {
    this.selected.set(t);
    this.supportService.get(t.support_conv_id).subscribe(r => this.selMsgs.set(r.messages));
  }

  sendReply(): void {
    if (!this.replyText.trim() || !this.selected()) return;
    this.replying.set(true);
    this.adminService.supportReply(this.selected()!.support_conv_id, this.replyText).subscribe({
      next: () => { this.replyText = ''; this.replying.set(false); this.select(this.selected()!); },
      error: () => this.replying.set(false),
    });
  }

  closeTicket(id: number): void {
    if (!confirm('Close this ticket?')) return;
    this.adminService.supportClose(id).subscribe({
      next: () => { this.tickets.update(ts => ts.filter(t => t.support_conv_id !== id)); this.selected.set(null); },
    });
  }
}

// ── User management ───────────────────────────────────────────────────────────
@Component({
  selector: 'app-admin-user',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  template: `
<div class="container py-4" style="max-width:700px;">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a routerLink="/admin" class="btn btn-sm btn-outline-secondary">← Admin</a>
    <h2 class="fw-bold mb-0" style="font-family:'Playfair Display',serif;">Manage User</h2>
  </div>

  @if (loading()) { <div class="text-center py-5"><div class="spinner-border" style="color:var(--accent);"></div></div> }
  @else if (!user()) { <div class="alert alert-warning">User not found.</div> }
  @else {
    <!-- Profile summary -->
    <div class="card border-0 shadow-sm mb-4 p-3">
      <div class="d-flex align-items-center gap-3">
        <div>
          <h5 class="fw-bold mb-0">{{ user()!.username }}</h5>
          <p class="text-muted small mb-0">ID: {{ userId }} · Honor: {{ user()!.honor_points }}</p>
          <a [routerLink]="['/profile', userId]" class="small">View public profile →</a>
        </div>
      </div>
    </div>

    @if (actionMsg()) { <div class="alert alert-success py-2 small">{{ actionMsg() }}</div> }
    @if (actionErr()) { <div class="alert alert-danger py-2 small">{{ actionErr() }}</div> }

    <!-- Action groups -->
    <div class="accordion" id="adminAccordion">

      <!-- Account ban -->
      <div class="accordion-item border-0 shadow-sm mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#banGroup">
            🔴 Account Ban
          </button>
        </h2>
        <div id="banGroup" class="accordion-collapse collapse" data-bs-parent="#adminAccordion">
          <div class="accordion-body">
            <div class="row g-2 mb-3">
              <div class="col-8"><input class="form-control form-control-sm" [(ngModel)]="reason" placeholder="Reason"></div>
              <div class="col-4"><input type="number" class="form-control form-control-sm" [(ngModel)]="minutes" placeholder="Minutes (0=perm)"></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-outline-warning" (click)="act('warn')">⚠ Warn</button>
              <button class="btn btn-sm btn-outline-secondary" (click)="act('unwarn')">Remove Warn</button>
              <button class="btn btn-sm btn-danger" (click)="act('ban')">Ban</button>
              <button class="btn btn-sm btn-success" (click)="act('unban')">Unban</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mute -->
      <div class="accordion-item border-0 shadow-sm mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#muteGroup">
            🔇 Mute (Messaging)
          </button>
        </h2>
        <div id="muteGroup" class="accordion-collapse collapse" data-bs-parent="#adminAccordion">
          <div class="accordion-body">
            <div class="row g-2 mb-3">
              <div class="col-8"><input class="form-control form-control-sm" [(ngModel)]="reason" placeholder="Reason"></div>
              <div class="col-4"><input type="number" class="form-control form-control-sm" [(ngModel)]="minutes" placeholder="Minutes"></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-outline-warning" (click)="act('mute-warn')">⚠ Mute Warn</button>
              <button class="btn btn-sm btn-outline-secondary" (click)="act('mute-unwarn')">Remove Warn</button>
              <button class="btn btn-sm btn-danger" (click)="act('mute')">Mute</button>
              <button class="btn btn-sm btn-success" (click)="act('unmute')">Unmute</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Ad ban -->
      <div class="accordion-item border-0 shadow-sm mb-2">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#adBanGroup">
            📢 Ad Posting Ban
          </button>
        </h2>
        <div id="adBanGroup" class="accordion-collapse collapse" data-bs-parent="#adminAccordion">
          <div class="accordion-body">
            <div class="row g-2 mb-3">
              <div class="col-8"><input class="form-control form-control-sm" [(ngModel)]="reason" placeholder="Reason"></div>
              <div class="col-4"><input type="number" class="form-control form-control-sm" [(ngModel)]="minutes" placeholder="Minutes"></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-outline-warning" (click)="act('ad-warn')">⚠ Ad Warn</button>
              <button class="btn btn-sm btn-outline-secondary" (click)="act('ad-unwarn')">Remove Warn</button>
              <button class="btn btn-sm btn-danger" (click)="act('ad-ban')">Ad Ban</button>
              <button class="btn btn-sm btn-success" (click)="act('ad-unban')">Ad Unban</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  }
</div>
  `,
})
export class AdminUserComponent implements OnInit {
  user    = signal<PublicUser | null>(null);
  loading = signal(true);
  userId  = 0;
  reason  = ''; minutes = 0;
  actionMsg = signal(''); actionErr = signal('');

  constructor(private route: ActivatedRoute, private userService: UserService, private adminService: AdminService) {}

  ngOnInit(): void {
    this.userId = +this.route.snapshot.paramMap.get('id')!;
    this.userService.getProfile(this.userId).subscribe({
      next: r => { this.user.set(r.user); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  act(action: string): void {
    this.actionMsg.set(''); this.actionErr.set('');
    let obs: any;
    const uid = this.userId; const r = this.reason; const m = this.minutes;

    switch (action) {
      case 'warn':      obs = this.adminService.warnUser(uid, r); break;
      case 'unwarn':    obs = this.adminService.unwarnUser(uid); break;
      case 'ban':       obs = this.adminService.banUser(uid, m, r); break;
      case 'unban':     obs = this.adminService.unbanUser(uid); break;
      case 'mute-warn': obs = this.adminService.muteWarnUser(uid, r); break;
      case 'mute':      obs = this.adminService.muteUser(uid, m, r); break;
      case 'unmute':    obs = this.adminService.unmuteUser(uid); break;
      case 'ad-warn':   obs = this.adminService.adWarnUser(uid, r); break;
      case 'ad-ban':    obs = this.adminService.adBanUser(uid, m, r); break;
      case 'ad-unban':  obs = this.adminService.adUnbanUser(uid); break;
      default: return;
    }

    obs.subscribe({
      next: () => { this.actionMsg.set(`Action "${action}" applied successfully.`); this.reason = ''; this.minutes = 0; },
      error: (err: any) => this.actionErr.set(err.error?.error || 'Action failed.'),
    });
  }
}
