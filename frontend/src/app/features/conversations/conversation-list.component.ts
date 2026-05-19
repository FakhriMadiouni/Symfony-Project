import { Component, OnInit, OnDestroy, signal, ElementRef, ViewChild, AfterViewChecked } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ConversationService, ReviewService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { ConversationSummary, ConversationDetail, Message } from '../../core/models';
import { TimeAgoPipe, TruncatePipe, UploadUrlPipe } from '../../shared/pipes/pipes';

// ── List ──────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-conversation-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TimeAgoPipe, TruncatePipe],
  template: `
<div class="container" style="max-width:700px;">
  <h2 style="font-family:'Syne',sans-serif;margin-bottom:1.5rem;">💬 My Conversations</h2>

  @if (loading()) {
    <div class="text-center py-5"><div class="spinner"></div></div>
  } @else if (convs().length === 0) {
    <div class="panel text-center" style="padding:3rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">💬</div>
      <p style="color:var(--muted);">No conversations yet. Browse ads and contact sellers.</p>
      <a routerLink="/search" class="btn btn-primary" style="margin-top:1rem;">Browse Ads</a>
    </div>
  } @else {
    <div style="display:flex;flex-direction:column;gap:.5rem;">
      @for (c of convs(); track c.conversation_id) {
        <a [routerLink]="['/conversations', c.conversation_id]"
           class="panel" style="display:flex;align-items:center;gap:1rem;padding:.85rem 1rem;text-decoration:none;color:var(--text);">
          <div style="flex:1;min-width:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem;">
              <span style="font-weight:600;font-size:.92rem;">{{ c.other_user.username }}</span>
              <span style="font-size:.72rem;color:var(--muted);">{{ c.last_message_date | timeAgo }}</span>
            </div>
            <p style="margin:0;font-size:.82rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              Re: {{ c.ad_title | truncate:60 }}
            </p>
          </div>
          @if (c.unread > 0) {
            <span style="background:var(--accent);color:#0d0f14;border-radius:99px;padding:.15rem .55rem;font-size:.72rem;font-weight:700;">{{ c.unread }}</span>
          }
          @if (c.lock_status === 1) {
            <span style="font-size:.75rem;color:var(--muted);">🔒</span>
          }
        </a>
      }
    </div>
  }
</div>
  `,
})
export class ConversationListComponent implements OnInit {
  convs   = signal<ConversationSummary[]>([]);
  loading = signal(true);

  constructor(private convService: ConversationService) {}

  ngOnInit(): void {
    this.convService.list().subscribe({
      next: r => { this.convs.set(r.conversations); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
}

// ── Detail ────────────────────────────────────────────────────────────────────
@Component({
  selector: 'app-conversation-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TimeAgoPipe, UploadUrlPipe],
  styles: [`
    .msg-bubble-me  { background:var(--accent);color:#0d0f14;border-radius:14px 14px 4px 14px; }
    .msg-bubble-them{ background:var(--bg3);color:var(--text);border-radius:14px 14px 14px 4px; }
  `],
  template: `
<div class="container" style="max-width:860px;">

  <!-- Back link -->
  <div style="margin-bottom:1rem;">
    <a routerLink="/conversations"
       style="font-size:.84rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:8px;background:var(--bg2);">
      ← Back to Conversations
    </a>
  </div>

  @if (convLoading()) {
    <div style="text-align:center;padding:4rem;"><div class="spinner"></div></div>
  } @else if (!conv()) {
    <div class="alert alert-error">Conversation not found.</div>
  } @else {

    <!-- Ad reference bar (matches PHP) -->
    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.75rem 1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
      <span style="font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Ad</span>
      <a [routerLink]="['/ads', conv()!.ad_id]" style="font-weight:600;color:var(--text);font-size:.9rem;">
        📌 {{ conv()!.ad_title }}
      </a>
      @if (conv()!.ad_ban_status === 1) {
        <span style="font-size:.75rem;background:rgba(220,50,50,.15);color:var(--danger);border:1px solid var(--danger);border-radius:20px;padding:.15rem .6rem;">🚫 Ad Banned</span>
      } @else if (conv()!.ad_active !== 1 && conv()!.ad_ban_status !== 1) {
        <span style="font-size:.75rem;background:rgba(128,128,128,.12);color:var(--muted);border:1px solid var(--border);border-radius:20px;padding:.15rem .6rem;">⏱ Ad Expired</span>
      } @else if (conv()!.ad_hidden === 1) {
        <span style="font-size:.75rem;background:rgba(224,160,0,.12);color:#e0a000;border:1px solid rgba(224,160,0,.3);border-radius:20px;padding:.15rem .6rem;">🙈 Hidden by Advertiser</span>
      }
      <span style="margin-left:auto;font-size:.78rem;color:var(--muted);">
        {{ conv()!.is_advertiser ? 'You are the advertiser' : 'You contacted this advertiser' }}
      </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 260px;gap:1.5rem;align-items:start;">

      <!-- ── Chat area ──────────────────────────────────────────────── -->
      <div>
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);">

          <!-- Chat header -->
          <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.75rem;">
            <img [src]="conv()!.other_user.profile_picture | uploadUrl:'avatars'"
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);" alt="">
            <div>
              <a [routerLink]="['/profile', conv()!.other_user.user_id]"
                 style="font-weight:600;font-size:.95rem;color:var(--text);text-decoration:none;">
                {{ conv()!.other_user.username }}
              </a>
              @if (conv()!.start_date) {
                <div style="font-size:.75rem;color:var(--muted);">Started {{ conv()!.start_date | timeAgo }}</div>
              }
            </div>
          </div>

          <!-- Messages -->
          <div #msgContainer style="height:420px;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.75rem;">
            @if (msgLoading()) {
              <div style="text-align:center;color:var(--muted);margin:auto;"><span class="spinner" style="width:28px;height:28px;"></span></div>
            } @else if (messages().length === 0) {
              <div style="text-align:center;color:var(--muted);font-size:.88rem;margin:auto;">Start the conversation!</div>
            } @else {
              @for (m of messages(); track m.message_id) {
                <div [attr.data-msg-id]="m.message_id"
                     style="display:flex;flex-direction:column;"
                     [style.align-items]="m.sender_id === myId() ? 'flex-end' : 'flex-start'">
                  @if (m.sender_id !== myId()) {
                    <span style="font-size:.72rem;color:var(--muted);margin-left:4px;margin-bottom:2px;">{{ m.sender_name }}</span>
                  }
                  <div style="display:flex;align-items:flex-end;gap:.4rem;min-width:0;"
                       [style.flex-direction]="m.sender_id === myId() ? 'row-reverse' : 'row'">
                    <div [class]="m.sender_id === myId() ? 'msg-bubble-me' : 'msg-bubble-them'"
                         style="max-width:72%;min-width:0;padding:.5rem .9rem;font-size:.88rem;line-height:1.5;word-break:break-word;overflow-wrap:anywhere;white-space:pre-wrap;">
                      {{ m.content }}
                    </div>
                    @if (m.sender_id !== myId()) {
                      <a [routerLink]="['/report']"
                         [queryParams]="{user_id: conv()!.other_user.user_id, msg_id: m.message_id, conv_id: conv()!.conversation_id, type: 'message', reference_id: m.message_id}"
                         title="Report this message"
                         style="color:var(--muted);font-size:.8rem;text-decoration:none;flex-shrink:0;opacity:.5;">⚑</a>
                    }
                  </div>
                  <span style="font-size:.68rem;color:var(--muted);margin-top:2px;">{{ m.timestamp.substring(11,16) }}</span>
                </div>
              }
            }
          </div>

          <!-- Input area (multiple locked states matching PHP) -->
          @if (conv()!.ad_ban_status === 1) {
            <div style="padding:.75rem;text-align:center;font-size:.85rem;border-top:1px solid var(--border);background:rgba(220,50,50,.06);">
              <span style="color:var(--danger);">🚫 This conversation is closed — the ad was banned.</span>
            </div>
          } @else if (conv()!.my_mute_status === 1) {
            <div style="padding:.75rem;text-align:center;font-size:.85rem;border-top:1px solid var(--border);background:rgba(220,50,50,.06);">
              <span style="color:#e05555;">🔇 You are muted and cannot send messages.</span>
            </div>
          } @else if (conv()!.other_user.ban_status === 1) {
            <div style="padding:.75rem;text-align:center;font-size:.85rem;border-top:1px solid var(--border);background:rgba(220,50,50,.06);">
              <span style="color:#e05555;">⚠️ This user's account is currently banned.</span>
              <span style="color:var(--muted);display:block;margin-top:.2rem;font-size:.78rem;">You cannot send new messages, but you can still read existing ones.</span>
            </div>
          } @else if (conv()!.other_user.mute_status === 1) {
            <div style="padding:.75rem;text-align:center;font-size:.85rem;border-top:1px solid var(--border);background:rgba(220,50,50,.06);">
              <span style="color:#e05555;">🔇 This user is currently muted by staff.</span>
              <span style="color:var(--muted);display:block;margin-top:.2rem;font-size:.78rem;">You cannot send new messages while they are muted.</span>
            </div>
          } @else if (conv()!.lock_status !== 1) {
            <div style="padding:.75rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:.5rem;">
              @if (conv()!.ad_hidden === 1 && !conv()!.is_advertiser) {
                <div style="padding:.4rem .75rem;background:rgba(224,160,0,.08);border:1px solid rgba(224,160,0,.2);border-radius:6px;font-size:.78rem;color:#c89000;">
                  🙈 This ad is currently hidden by the advertiser. You can still chat.
                </div>
              }
              <div style="display:flex;gap:.5rem;">
                <input [(ngModel)]="newMsg" type="text" placeholder="Type a message…"
                       style="flex:1;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:.6rem .9rem;border-radius:8px;font-size:.9rem;"
                       (keydown.enter)="sendMsg()" maxlength="2000">
                <button (click)="sendMsg()" class="btn btn-primary" [disabled]="sending()">
                  @if (sending()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> } @else { Send }
                </button>
              </div>
              @if (sendError()) { <div style="font-size:.8rem;color:var(--danger);">{{ sendError() }}</div> }
            </div>
          } @else {
            <div style="padding:.75rem;text-align:center;color:var(--muted);font-size:.85rem;border-top:1px solid var(--border);">
              @if (conv()!.ad_active !== 1) { ⏱ This conversation is closed — the ad has expired. }
              @else { This conversation is locked. }
            </div>
          }
        </div>
      </div>

      <!-- ── Right panel: Review + Report ───────────────────────────── -->
      <div>
        @if (!conv()!.is_advertiser && conv()!.ad_hidden !== 1) {
          @if (!reviewDone() && !hasReviewed()) {
            <div class="panel">
              <h3>Leave a Review</h3>
              <p style="color:var(--muted);font-size:.83rem;margin-bottom:1rem;">
                Rate your experience with {{ conv()!.other_user.username }} on this ad.
              </p>
              <div class="form-group">
                <label>Rating</label>
                <div style="display:flex;gap:.75rem;">
                  <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                    <input type="radio" name="rate" value="positive" [(ngModel)]="reviewRate" style="accent-color:var(--success);">
                    <span style="color:var(--success);">👍 Positive</span>
                  </label>
                  <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                    <input type="radio" name="rate" value="negative" [(ngModel)]="reviewRate" style="accent-color:var(--danger);">
                    <span style="color:var(--danger);">👎 Negative</span>
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label>Comment</label>
                <textarea [(ngModel)]="reviewComment" rows="3" placeholder="Describe your experience…"></textarea>
              </div>
              <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--muted);margin-bottom:1rem;cursor:pointer;">
                <input type="checkbox" [(ngModel)]="reviewAnon"> Post anonymously
              </label>
              @if (reviewError()) { <div class="alert alert-error" style="margin-bottom:.75rem;font-size:.82rem;">{{ reviewError() }}</div> }
              <button class="btn btn-primary btn-block" (click)="submitReview()" [disabled]="submittingReview()">
                @if (submittingReview()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
                Submit Review
              </button>
            </div>
          } @else if (reviewDone() || hasReviewed()) {
            <div class="panel" style="text-align:center;">
              <p style="color:var(--success);">✓ You already reviewed this conversation.</p>
            </div>
          }
        } @else if (!conv()!.is_advertiser && conv()!.ad_hidden === 1) {
          <div class="panel" style="text-align:center;">
            <p style="color:var(--muted);font-size:.85rem;">🙈 Reviews are unavailable while the ad is hidden.</p>
          </div>
        }

        <div class="panel" style="margin-top:1rem;">
          <h3>Report</h3>
          <a [routerLink]="['/report']"
             [queryParams]="{user_id: conv()!.other_user.user_id, conv_id: conv()!.conversation_id, type: 'conversation', reference_id: conv()!.conversation_id}"
             class="btn btn-ghost btn-block" style="font-size:.83rem;margin-bottom:.5rem;">
            ⚑ Report Conversation
          </a>
        </div>
      </div>

    </div>
  }
</div>
  `,
})
export class ConversationDetailComponent implements OnInit, OnDestroy, AfterViewChecked {
  @ViewChild('msgContainer') scrollEl!: ElementRef;

  conv        = signal<ConversationDetail | null>(null);
  messages    = signal<Message[]>([]);
  convLoading = signal(true);
  msgLoading  = signal(true);
  myId        = signal(0);

  // Messaging
  newMsg    = '';
  sending   = signal(false);
  sendError = signal('');

  // Review
  hasReviewed      = signal(false);
  reviewRate       = 'positive';
  reviewComment    = '';
  reviewAnon       = false;
  submittingReview = signal(false);
  reviewError      = signal('');
  reviewDone       = signal(false);

  private convId     = 0;
  private pollTimer: any;
  private shouldScroll = false;

  constructor(
    private route: ActivatedRoute,
    private convService: ConversationService,
    private reviewService: ReviewService,
    public auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.convId = +this.route.snapshot.paramMap.get('id')!;
    this.myId.set(this.auth.user()?.user_id ?? 0);

    // Load conversation details
    this.convService.get(this.convId).subscribe({
      next: r => {
        this.conv.set(r.conversation);
        this.convLoading.set(false);
        this.loadMessages();

        // Check if already reviewed
        this.reviewService.check(this.convId).subscribe({
          next: rc => this.hasReviewed.set(rc.reviewed),
        });
      },
      error: () => this.convLoading.set(false),
    });

    // Poll for new messages every 3s (matches PHP's 3000ms)
    this.pollTimer = setInterval(() => this.loadMessages(true), 3000);
  }

  ngOnDestroy(): void { clearInterval(this.pollTimer); }

  ngAfterViewChecked(): void {
    if (this.shouldScroll) { this.scrollToBottom(); this.shouldScroll = false; }
  }

  private loadMessages(silent = false): void {
    if (!silent) this.msgLoading.set(true);
    this.convService.messages(this.convId).subscribe({
      next: r => {
        const prev = this.messages().length;
        this.messages.set(r.messages);
        if (r.messages.length !== prev) this.shouldScroll = true;
        this.msgLoading.set(false);
      },
      error: () => this.msgLoading.set(false),
    });
  }

  private scrollToBottom(): void {
    try { this.scrollEl.nativeElement.scrollTop = this.scrollEl.nativeElement.scrollHeight; } catch {}
  }

  sendMsg(): void {
    if (!this.newMsg.trim()) return;
    this.sending.set(true); this.sendError.set('');
    this.convService.send(this.convId, this.newMsg).subscribe({
      next: res => {
        this.messages.update(m => [...m, res.message]);
        this.newMsg = ''; this.sending.set(false); this.shouldScroll = true;
      },
      error: err => { this.sendError.set(err.error?.error || 'Send failed.'); this.sending.set(false); },
    });
  }

  submitReview(): void {
    if (!this.reviewRate) return;
    this.submittingReview.set(true); this.reviewError.set('');
    this.reviewService.create({
      conversation_id: this.convId,
      rate: this.reviewRate,
      comment: this.reviewComment,
      anonymous: this.reviewAnon,
    }).subscribe({
      next: () => { this.reviewDone.set(true); this.submittingReview.set(false); },
      error: err => { this.reviewError.set(err.error?.error || 'Failed to submit review.'); this.submittingReview.set(false); },
    });
  }
}
