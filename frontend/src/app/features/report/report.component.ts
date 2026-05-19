import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink, ActivatedRoute, Router } from '@angular/router';
import { ReportService } from '../../core/services/api.services';

@Component({
  selector: 'app-report',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
<div class="page-center">
  <div class="auth-card" style="max-width:520px;">
    <span class="auth-logo">MPM<span style="color:var(--text);">CS</span></span>
    <h1 style="font-size:1.5rem;margin-bottom:.25rem;">🚩 Report</h1>
    <p class="subtitle" style="margin-bottom:1.5rem;">
      Help us keep MPM safe. Reports are reviewed by our moderation team.
    </p>

    @if (done()) {
      <div class="alert alert-success">
        ✅ Report submitted! Our team will review it shortly.
      </div>
      <button class="btn btn-ghost btn-block" (click)="goBack()">← Go Back</button>
    } @else {
      @if (error()) { <div class="alert alert-error">{{ error() }}</div> }

      <!-- Context info (read-only) -->
      @if (contextLabel()) {
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.6rem .9rem;margin-bottom:1.2rem;font-size:.85rem;color:var(--muted);">
          Reporting: <strong style="color:var(--text);">{{ contextLabel() }}</strong>
        </div>
      }

      <div class="form-group">
        <label>Report Type</label>
        <select [(ngModel)]="type">
          <option value="user">User account</option>
          <option value="ad">Advertisement</option>
          <option value="message">Message</option>
          <option value="review">Review</option>
          <option value="conversation">Conversation</option>
        </select>
      </div>

      <div class="form-group">
        <label>Reason</label>
        <select [(ngModel)]="reason">
          <option value="">Select a reason…</option>
          <option value="spam">Spam / Unsolicited content</option>
          <option value="scam">Scam / Fraud</option>
          <option value="harassment">Harassment / Threats</option>
          <option value="illegal_content">Illegal or prohibited content</option>
          <option value="fake_review">Fake / Manipulated review</option>
          <option value="impersonation">Impersonation</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div class="form-group">
        <label>Additional Details (optional)</label>
        <textarea [(ngModel)]="details" rows="4" maxlength="1000"
                  placeholder="Provide any additional context that might help our team…"></textarea>
      </div>

      <button class="btn btn-danger btn-block" (click)="submit()" [disabled]="submitting()">
        @if (submitting()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        Submit Report
      </button>
      <button class="btn btn-ghost btn-block" style="margin-top:.5rem;" (click)="goBack()">Cancel</button>
    }
  </div>
</div>
  `,
})
export class ReportComponent implements OnInit {
  type    = 'user';
  reason  = '';
  details = '';

  reportedUserId = 0;
  referenceId    = 0;
  contextLabel   = signal('');

  submitting = signal(false);
  error      = signal('');
  done       = signal(false);

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private reportService: ReportService,
  ) {}

  ngOnInit(): void {
    const p = this.route.snapshot.queryParamMap;
    this.reportedUserId = +(p.get('user_id') ?? 0);
    this.referenceId    = +(p.get('ref_id')  ?? 0);
    this.type           =   p.get('type')    ?? 'user';

    const username = p.get('username');
    const adTitle  = p.get('ad_title');
    if (username) this.contextLabel.set(`@${username}`);
    if (adTitle)  this.contextLabel.set(`Ad: "${adTitle}"`);
  }

  submit(): void {
    if (!this.reason) { this.error.set('Please select a reason.'); return; }
    if (!this.reportedUserId) { this.error.set('No user to report.'); return; }

    this.error.set(''); this.submitting.set(true);

    const data: any = {
      type: this.type,
      reported_user_id: this.reportedUserId,
      reason: this.reason + (this.details ? ': ' + this.details : ''),
    };
    if (this.referenceId) data['reference_id'] = this.referenceId;

    this.reportService.submit(data).subscribe({
      next: () => { this.done.set(true); this.submitting.set(false); },
      error: err => { this.error.set(err.error?.error || 'Report failed. Please try again.'); this.submitting.set(false); },
    });
  }

  goBack(): void { history.back(); }
}
