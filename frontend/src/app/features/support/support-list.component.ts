import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SupportService } from '../../core/services/api.services';
import { SupportTicket, SupportMessage } from '../../core/models';
import { TimeAgoPipe } from '../../shared/pipes/pipes';

// ── TOS data ─────────────────────────────────────────────────────────────────
const TOS_SECTIONS = [
  ['1. Acceptance', 'By accessing or using My Pocket Market, you agree to be bound by these Terms. If you do not agree, please do not use the platform.'],
  ['2. Eligibility', 'You must be at least 18 years old to create an account. By registering, you confirm that all information you provide is accurate and truthful.'],
  ['3. Advertisements', 'Users may publish advertisements using tokens. You are solely responsible for the content of your advertisements. Listings for illegal items, counterfeit goods, or deceptive content are strictly prohibited.'],
  ['4. Prohibited Conduct', 'The following are strictly forbidden:\n• Scamming or defrauding other users\n• Harassment or abusive communication\n• Creating fake reviews or manipulating the Honor System\n• Selling or advertising illegal products or services\n• Impersonating other users or staff\n• Any attempt to exploit, hack, or disrupt the platform'],
  ['5. Honor System', 'Your Honor Score reflects your reputation on the platform. It is affected by reviews, reports, and staff actions. Attempting to manipulate the Honor System through fake reviews or coordinated abuse will result in permanent account termination.'],
  ['6. Token Purchases', 'Tokens are purchased for use on this platform only. All purchases are final and non-refundable unless otherwise required by law. Tokens have no monetary value outside the platform.'],
  ['7. Privacy', 'We collect only the data necessary to operate the platform. We do not sell your personal data to third parties.'],
  ['8. Content Ownership', 'You retain ownership of content you post. By posting, you grant My Pocket Market a non-exclusive licence to display that content on the platform.'],
  ['9. Termination', 'We reserve the right to suspend or permanently ban any account that violates these Terms, at our discretion.'],
  ['10. Limitation of Liability', 'My Pocket Market is not responsible for transactions or disputes between users. We provide the platform as a marketplace.'],
  ['11. Changes to Terms', 'We may update these Terms at any time. Continued use of the platform after changes constitutes acceptance of the new Terms.'],
  ['12. Contact', 'For any questions about these Terms, contact us via the support system.'],
];

const FAQS = [
  { key: 'honor', title: '⭐ How does the Honor System work?', body: 'The Honor System measures your trustworthiness on the platform.\n\nYour Honor Score increases when:\n• Other users leave positive reviews on your ads\n• You maintain good standing with no violations\n\nYour Honor Score decreases when:\n• You receive negative reviews\n• Staff confirms reports against you\n• Punishments are applied to your account\n\nBased on your score you are assigned a rank — from Risky (0 pts) up to Legend (1000+ pts).' },
  { key: 'tokens', title: '🪙 What are tokens and how do I use them?', body: 'Tokens are what allow you to publish advertisements on the platform.\n\nEach token type defines:\n• How many days your ad stays active (Visibility)\n• How many media files (images/videos) you can attach\n\nTo post an ad go to Post Ad, select a token type, fill in the ad details and publish. One token is consumed per ad.\n\nYou can purchase tokens from the Store.' },
  { key: 'banned', title: '⚠️ My account is banned — what do I do?', body: 'If your account has been banned, you will still be able to log in but your access will be restricted to this Support page and your Notifications.\n\nTo appeal your ban, contact our support team using the form below. Our Justice Division will review your case.\n\nPlease note that bans for serious violations may be permanent.' },
  { key: 'reviews', title: '📝 How do reviews affect my account?', body: 'Reviews are left by users you have interacted with through your advertisements. They can be positive or negative and include an optional comment.\n\nReviews directly affect your Honor Score. Reviewers can choose to post anonymously. You will be notified when you receive a review.' },
  { key: 'report', title: '🚩 How do I report a user or an ad?', body: 'You can report a user or advertisement by clicking the Report button on the ad page or conversation page.\n\nOur Enforcement Division reviews all reports. Confirmed violations result in warnings, muting, ad removal, or account bans depending on severity.\n\nFalse reports are themselves a violation of our Terms of Service.' },
];

// ── List ──────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-support-list',
  standalone: true,
  imports: [CommonModule, FormsModule, TimeAgoPipe],
  styles: [`
    .faq-details { border-bottom: 1px solid var(--border); padding: .65rem 0; }
    .faq-summary { cursor: pointer; font-size: .9rem; font-weight: 600; list-style: none; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .faq-summary::-webkit-details-marker { display: none; }
    .faq-body { font-size: .86rem; color: var(--muted); line-height: 1.7; margin-top: .65rem; white-space: pre-line; padding-left: .1rem; }
    .support-conv-item { display: flex; align-items: center; gap: 1rem; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem 1.25rem; text-decoration: none; color: inherit; transition: border-color .2s; }
    .support-conv-item:hover { border-color: var(--accent); }
    .msg-wrap { display: flex; flex-direction: column; gap: .75rem; max-height: 420px; overflow-y: auto; margin-bottom: 1.25rem; padding-right: .25rem; }
  `],
  template: `
<div class="container" style="max-width:860px;">
  <div style="margin-bottom:2rem;">
    <h1>Support Center</h1>
    <p style="color:var(--muted);font-size:.9rem;">Read our Terms of Service or get help from our team.</p>
  </div>

  <!-- Section tabs -->
  <div class="tabs" style="margin-bottom:1.5rem;">
    <button class="tab" [class.active]="section()==='tos'" (click)="section.set('tos')">📄 Terms of Service</button>
    <button class="tab" [class.active]="section()==='contact'" (click)="section.set('contact')">
      🎧 Contact Support
      @if (openTicket()) {
        <span style="background:var(--success);color:white;font-size:.65rem;padding:.1rem .45rem;border-radius:99px;margin-left:.3rem;">Open</span>
      }
    </button>
    <button class="tab" [class.active]="section()==='history'" (click)="section.set('history');viewTicketId.set(0)">📋 My Requests</button>
  </div>

  @if (error()) { <div class="alert alert-error">{{ error() }}</div> }
  @if (successMsg()) { <div class="alert alert-success">{{ successMsg() }}</div> }

  <!-- ── TOS ── -->
  @if (section() === 'tos') {
    <div class="panel">
      <h2 style="font-size:1.2rem;margin-bottom:1.5rem;">Terms of Service</h2>
      @for (sec of tosSections; track sec[0]) {
        <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
          <h3 style="font-size:.95rem;margin-bottom:.5rem;">{{ sec[0] }}</h3>
          <p style="font-size:.88rem;color:var(--muted);line-height:1.7;white-space:pre-line;">{{ sec[1] }}</p>
        </div>
      }
      <div style="margin-top:1.5rem;text-align:center;">
        <button class="btn btn-ghost" style="font-size:.85rem;" (click)="section.set('contact')">Questions? Contact Support →</button>
      </div>
    </div>
  }

  <!-- ── CONTACT ── -->
  @if (section() === 'contact') {
    <!-- FAQ accordion -->
    <div class="panel" style="margin-bottom:1.25rem;">
      <h3 style="margin-bottom:1.1rem;">💡 Common Questions</h3>
      @for (faq of faqs; track faq.key) {
        <details class="faq-details" [attr.id]="'cfaq-' + faq.key">
          <summary class="faq-summary">
            {{ faq.title }}
            <span style="color:var(--muted);font-size:1rem;flex-shrink:0;">﹢</span>
          </summary>
          <p class="faq-body">{{ faq.body }}</p>
        </details>
      }
    </div>

    <!-- Open ticket or new ticket form -->
    @if (ticketLoading()) {
      <div style="text-align:center;padding:2rem;"><span class="spinner"></span></div>
    } @else if (openTicket()) {
      <!-- Active open ticket chat -->
      <div class="panel" style="margin-bottom:1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--border);">
          <div>
            <h3 style="font-size:1rem;font-family:'Syne',sans-serif;">{{ openTicket()!.subject }}</h3>
            <span style="font-size:.75rem;color:var(--success);">● Open</span>
          </div>
        </div>
        <div class="msg-wrap" #msgWrap>
          @if (openMessages().length === 0) {
            <p style="color:var(--muted);font-size:.88rem;">No messages yet.</p>
          } @else {
            @for (m of openMessages(); track m.support_msg_id) {
              <div style="display:flex;flex-direction:column;" [style.align-items]="m.is_staff ? 'flex-start' : 'flex-end'">
                <span style="font-size:.7rem;color:var(--muted);">{{ m.is_staff ? '🎧 Support Team' : 'You' }}</span>
                <div [style.background]="m.is_staff ? 'var(--bg3)' : 'var(--accent)'"
                     [style.color]="m.is_staff ? 'var(--text)' : '#0d0f14'"
                     style="max-width:78%;padding:.55rem .9rem;border-radius:12px;font-size:.88rem;line-height:1.5;word-break:break-word;">
                  {{ m.content }}
                </div>
                <span style="font-size:.68rem;color:var(--muted);">{{ m.sent_date | timeAgo }}</span>
              </div>
            }
          }
        </div>
        <div style="display:flex;gap:.5rem;">
          <input type="text" [(ngModel)]="replyMsg" placeholder="Type your message…"
                 style="flex:1;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:.6rem .9rem;border-radius:8px;font-size:.9rem;outline:none;"
                 (keyup.enter)="sendReply()">
          <button class="btn btn-primary" (click)="sendReply()" [disabled]="replying()">Send</button>
        </div>
        @if (replyError()) { <p style="color:var(--danger);font-size:.82rem;margin-top:.4rem;">{{ replyError() }}</p> }
      </div>
      <p style="font-size:.8rem;color:var(--muted);text-align:center;">
        You can only have one open support conversation at a time.
        <button class="btn btn-ghost btn-sm" style="margin-left:.5rem;" (click)="section.set('history');viewTicketId.set(0)">View all requests →</button>
      </p>
    } @else {
      <!-- New ticket form -->
      <div class="panel">
        <h3>Contact Support Team</h3>
        <p style="color:var(--muted);font-size:.87rem;margin-bottom:1.5rem;">Our staff will respond as soon as possible. You'll be notified when they reply.</p>
        <div class="form-group">
          <label>Subject</label>
          <input type="text" [(ngModel)]="newSubject" placeholder="Brief description of your issue" maxlength="200">
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea [(ngModel)]="newMessage" rows="5" placeholder="Describe your issue in detail…"></textarea>
        </div>
        <button class="btn btn-primary" (click)="openTicket_()" [disabled]="submitting()">
          @if (submitting()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
          Submit Request
        </button>
      </div>
    }
  }

  <!-- ── HISTORY ── -->
  @if (section() === 'history') {
    @if (histLoading()) {
      <div style="text-align:center;padding:2rem;"><span class="spinner"></span></div>
    } @else if (viewTicketId() > 0 && viewTicket()) {
      <!-- Single ticket detail -->
      <div style="margin-bottom:1rem;">
        <button class="btn btn-ghost btn-sm" (click)="viewTicketId.set(0)">← Back</button>
      </div>
      <div class="panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--border);">
          <div>
            <h3 style="font-size:1rem;font-family:'Syne',sans-serif;">{{ viewTicket()!.subject }}</h3>
            <span [style.color]="viewTicket()!.status === 'open' ? 'var(--success)' : 'var(--muted)'" style="font-size:.75rem;">
              {{ viewTicket()!.status === 'open' ? '● Open' : '○ Closed' }}
            </span>
          </div>
        </div>
        <div class="msg-wrap">
          @for (m of viewMessages(); track m.support_msg_id) {
            <div style="display:flex;flex-direction:column;" [style.align-items]="m.is_staff ? 'flex-start' : 'flex-end'">
              <span style="font-size:.7rem;color:var(--muted);">{{ m.is_staff ? '🎧 Support Team' : 'You' }}</span>
              <div [style.background]="m.is_staff ? 'var(--bg3)' : 'var(--accent)'"
                   [style.color]="m.is_staff ? 'var(--text)' : '#0d0f14'"
                   style="max-width:78%;padding:.55rem .9rem;border-radius:12px;font-size:.88rem;line-height:1.5;word-break:break-word;">
                {{ m.content }}
              </div>
              <span style="font-size:.68rem;color:var(--muted);">{{ m.sent_date | timeAgo }}</span>
            </div>
          }
        </div>
        @if (viewTicket()!.status === 'open') {
          <div style="display:flex;gap:.5rem;">
            <input type="text" [(ngModel)]="viewReplyMsg" placeholder="Type your message…"
                   style="flex:1;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:.6rem .9rem;border-radius:8px;font-size:.9rem;outline:none;"
                   (keyup.enter)="sendViewReply()">
            <button class="btn btn-primary" (click)="sendViewReply()" [disabled]="viewReplying()">Send</button>
          </div>
          @if (viewReplyError()) { <p style="color:var(--danger);font-size:.82rem;margin-top:.4rem;">{{ viewReplyError() }}</p> }
        } @else {
          <div style="text-align:center;padding:.75rem;background:var(--bg3);border-radius:8px;font-size:.85rem;color:var(--muted);">
            This conversation is closed.
            @if (!openTicket()) {
              <button class="btn btn-ghost btn-sm" style="margin-left:.5rem;" (click)="section.set('contact')">Open a new request →</button>
            }
          </div>
        }
      </div>
    } @else if (tickets().length === 0) {
      <div class="panel" style="text-align:center;padding:2.5rem 2rem;">
        <p style="font-size:1.5rem;margin-bottom:.75rem;">📭</p>
        <h3 style="margin-bottom:.5rem;">No requests yet</h3>
        <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.25rem;">You haven't submitted any support requests yet.</p>
        <button class="btn btn-primary" style="font-size:.88rem;" (click)="section.set('contact')">Contact Support</button>
      </div>
    } @else {
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        @for (t of tickets(); track t.support_conv_id) {
          <div class="support-conv-item" (click)="openViewTicket(t.support_conv_id)" style="cursor:pointer;">
            <span style="font-size:1.4rem;">🎧</span>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:.92rem;font-family:'Syne',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ t.subject }}</div>
              <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">{{ t.opened_date | timeAgo }}</div>
            </div>
            <span style="font-size:.75rem;padding:.25rem .7rem;border-radius:99px;font-weight:600;"
                  [style.background]="t.status === 'open' ? 'rgba(76,175,125,.15)' : 'var(--bg3)'"
                  [style.color]="t.status === 'open' ? 'var(--success)' : 'var(--muted)'">
              {{ t.status === 'open' ? '● Open' : '○ Closed' }}
            </span>
          </div>
        }
      </div>
    }
  }
</div>
  `,
})
export class SupportListComponent implements OnInit {
  readonly tosSections = TOS_SECTIONS;
  readonly faqs = FAQS;

  section = signal<'tos' | 'contact' | 'history'>('tos');
  error = signal(''); successMsg = signal('');

  // Contact tab
  ticketLoading = signal(true);
  openTicket = signal<SupportTicket | null>(null);
  openMessages = signal<SupportMessage[]>([]);
  newSubject = ''; newMessage = '';
  submitting = signal(false);
  replyMsg = ''; replying = signal(false); replyError = signal('');

  // History tab
  histLoading = signal(false);
  tickets = signal<SupportTicket[]>([]);
  viewTicketId = signal(0);
  viewTicket = signal<SupportTicket | null>(null);
  viewMessages = signal<SupportMessage[]>([]);
  viewReplyMsg = ''; viewReplying = signal(false); viewReplyError = signal('');

  constructor(
    private supportService: SupportService,
    private route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    // Check for open ticket
    this.supportService.list().subscribe({
      next: r => {
        const all = r.conversations;
        const open = all.find(t => t.status === 'open') ?? null;
        this.openTicket.set(open);
        this.ticketLoading.set(false);
        if (open) {
          this.loadOpenMessages(open.support_conv_id);
          this.section.set('contact');
        }
        this.tickets.set(all);
      },
      error: () => this.ticketLoading.set(false),
    });
    // Handle ?s= param
    const s = this.route.snapshot.queryParamMap.get('s');
    if (s === 'contact' || s === 'history') this.section.set(s as any);
  }

  private loadOpenMessages(id: number): void {
    this.supportService.get(id).subscribe({
      next: r => this.openMessages.set(r.messages),
    });
  }

  openTicket_(): void {
    if (!this.newSubject.trim() || !this.newMessage.trim()) {
      this.error.set('Subject and message are required.'); return;
    }
    this.submitting.set(true); this.error.set('');
    this.supportService.open(this.newSubject, this.newMessage).subscribe({
      next: res => {
        this.submitting.set(false);
        this.successMsg.set("Your support request has been submitted. We'll get back to you shortly.");
        this.supportService.get(res.support_conv_id).subscribe({
          next: r => {
            this.openTicket.set(r.conversation);
            this.openMessages.set(r.messages);
            this.tickets.update(t => [r.conversation, ...t]);
          },
        });
        this.newSubject = ''; this.newMessage = '';
      },
      error: err => { this.error.set(err.error?.error || 'Failed to submit.'); this.submitting.set(false); },
    });
  }

  sendReply(): void {
    if (!this.replyMsg.trim() || !this.openTicket()) return;
    this.replying.set(true); this.replyError.set('');
    this.supportService.reply(this.openTicket()!.support_conv_id, this.replyMsg).subscribe({
      next: () => {
        this.replyMsg = ''; this.replying.set(false);
        this.loadOpenMessages(this.openTicket()!.support_conv_id);
      },
      error: err => { this.replyError.set(err.error?.error || 'Failed to send.'); this.replying.set(false); },
    });
  }

  openViewTicket(id: number): void {
    this.viewTicketId.set(id);
    this.viewReplyError.set('');
    this.supportService.get(id).subscribe({
      next: r => { this.viewTicket.set(r.conversation); this.viewMessages.set(r.messages); },
    });
  }

  sendViewReply(): void {
    if (!this.viewReplyMsg.trim() || !this.viewTicketId()) return;
    this.viewReplying.set(true); this.viewReplyError.set('');
    this.supportService.reply(this.viewTicketId(), this.viewReplyMsg).subscribe({
      next: () => {
        this.viewReplyMsg = ''; this.viewReplying.set(false);
        this.supportService.get(this.viewTicketId()).subscribe({
          next: r => { this.viewTicket.set(r.conversation); this.viewMessages.set(r.messages); },
        });
      },
      error: err => { this.viewReplyError.set(err.error?.error || 'Failed to send.'); this.viewReplying.set(false); },
    });
  }
}

// ── New (alias) ───────────────────────────────────────────────────────────────
export { SupportListComponent as SupportNewComponent };

// ── Detail (alias) ────────────────────────────────────────────────────────────
export { SupportListComponent as SupportDetailComponent };
