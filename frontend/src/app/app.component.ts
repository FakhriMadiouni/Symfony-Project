import { Component, OnInit, OnDestroy, signal, computed } from '@angular/core';
import { RouterOutlet, RouterLink, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from './core/services/auth.service';
import { NotificationService, ConversationService } from './core/services/api.services';
import { UploadUrlPipe, TimeAgoPipe } from './shared/pipes/pipes';
import { ConversationSummary, Message } from './core/models';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, CommonModule, FormsModule, UploadUrlPipe, TimeAgoPipe],
  template: `
@if (auth.isLoggedIn()) {
  <nav>
    <!-- Logo -->
    <a class="nav-logo" routerLink="/home">MPM<span>CS</span></a>

    <!-- Left links -->
    <div class="nav-links">
      <a routerLink="/home">🏠 Home</a>
      <a routerLink="/search">🔍 Browse</a>
      <a routerLink="/store">🏪 Store</a>
      @if (auth.isStaff()) {
        <a routerLink="/admin">⚙ Staff</a>
      }
      <a routerLink="/support">🎧 Support</a>

      <!-- Conversations badge -->
      <a (click)="toggleChat()" style="cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
        💬 Messages
        @if (unreadMsgs() > 0) {
          <span class="nav-badge">{{ unreadMsgs() }}</span>
        }
      </a>

      <!-- Notifications badge -->
      <a routerLink="/notifications" style="display:inline-flex;align-items:center;gap:4px;">
        🔔
        @if (unreadNotifs() > 0) {
          <span class="nav-badge">{{ unreadNotifs() }}</span>
        }
      </a>

      <!-- Avatar → profile -->
      <a [routerLink]="['/profile', auth.user()?.user_id]" style="display:inline-flex;align-items:center;">
        <img
          [src]="auth.user()?.profile_picture | uploadUrl:'avatars'"
          class="nav-avatar"
          alt="avatar"
        >
      </a>
      <a (click)="auth.logout()" style="cursor:pointer;color:var(--muted);">Logout</a>
    </div>
  </nav>

  <!-- ── Chat popup sidebar ─────────────────────────────────────── -->
  @if (chatOpen()) {
    <div class="chat-popup-overlay" (click)="chatOpen.set(false)"></div>
    <div class="chat-popup">
      <div class="chat-popup-header">
        <span class="fw-bold">Messages</span>
        <a routerLink="/conversations" (click)="chatOpen.set(false)" class="small" style="color:var(--accent);">See all →</a>
        <button class="chat-popup-close" (click)="chatOpen.set(false)">✕</button>
      </div>

      @if (!activeConvId()) {
        <!-- Conversation list -->
        <div class="chat-popup-list">
          @if (chatLoading()) {
            <div class="text-center py-3"><span class="spinner" style="width:20px;height:20px;border-width:2px;"></span></div>
          } @else if (convs().length === 0) {
            <div class="text-center py-4 text-muted" style="font-size:.85rem;">
              No conversations yet.<br>Contact a seller to start one.
            </div>
          } @else {
            @for (c of convs(); track c.conversation_id) {
              <div class="chat-conv-item" (click)="openConv(c)">
                <div style="font-weight:600;font-size:.88rem;">{{ c.other_user.username }}</div>
                <div class="text-muted" style="font-size:.75rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Re: {{ c.ad_title }}</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:2px;">
                  <span style="font-size:.7rem;color:var(--muted);">{{ c.last_message_date | timeAgo }}</span>
                  @if (c.unread > 0) { <span class="nav-badge">{{ c.unread }}</span> }
                </div>
              </div>
            }
          }
        </div>
      } @else {
        <!-- Active conversation pane -->
        <div class="chat-popup-header" style="border-bottom:1px solid var(--border);padding-bottom:.5rem;margin-bottom:.5rem;">
          <button (click)="activeConvId.set(null)" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:.85rem;">← Back</button>
          <span style="font-size:.85rem;font-weight:600;">{{ activeConvUsername() }}</span>
        </div>
        <div class="chat-popup-messages" #popupScroll>
          @for (m of activeMessages(); track m.message_id) {
            <div [class.msg-me]="m.sender_id === myId()" [class.msg-other]="m.sender_id !== myId()" class="chat-msg">
              <div class="chat-msg-bubble" [class.bubble-me]="m.sender_id === myId()" [class.bubble-other]="m.sender_id !== myId()">
                <p style="margin:0;font-size:.82rem;white-space:pre-wrap;word-break:break-word;">{{ m.content }}</p>
                <p style="margin:0;font-size:.6rem;opacity:.65;margin-top:2px;">{{ m.timestamp | timeAgo }}</p>
              </div>
            </div>
          }
        </div>
        @if (activeConvLocked()) {
          <div style="font-size:.75rem;color:var(--muted);text-align:center;padding:.5rem;">🔒 Conversation locked</div>
        } @else {
          <div style="display:flex;gap:.4rem;padding:.5rem;">
            <input style="flex:1;background:var(--bg3);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:.4rem .7rem;font-size:.82rem;"
                   [(ngModel)]="popupMsg" placeholder="Message…" (keyup.enter)="sendPopupMsg()">
            <button (click)="sendPopupMsg()" [disabled]="popupSending()"
                    style="background:var(--accent);color:#000;border:none;border-radius:8px;padding:.4rem .8rem;cursor:pointer;font-weight:600;">➤</button>
          </div>
        }
      }
    </div>
  }
}

<router-outlet />
  `,
  styles: [`
    .chat-popup-overlay {
      position:fixed;inset:0;z-index:199;
    }
    .chat-popup {
      position:fixed;bottom:1.5rem;right:1.5rem;width:310px;
      background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
      box-shadow:0 8px 32px rgba(0,0,0,.5);z-index:200;display:flex;flex-direction:column;
      max-height:480px;overflow:hidden;
    }
    .chat-popup-header {
      display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;
      border-bottom:1px solid var(--border);
    }
    .chat-popup-header span { flex:1; }
    .chat-popup-close {
      background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem;line-height:1;
      transition:color .2s;
    }
    .chat-popup-close:hover { color:var(--text); }
    .chat-popup-list { overflow-y:auto;flex:1; }
    .chat-conv-item {
      padding:.65rem 1rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;
    }
    .chat-conv-item:hover { background:var(--bg3); }
    .chat-popup-messages {
      flex:1;overflow-y:auto;padding:.5rem .75rem;display:flex;flex-direction:column;gap:.4rem;max-height:280px;
    }
    .chat-msg { display:flex; }
    .msg-me { justify-content:flex-end; }
    .msg-other { justify-content:flex-start; }
    .chat-msg-bubble { padding:.4rem .7rem;border-radius:10px;max-width:80%; }
    .bubble-me { background:var(--accent);color:#0d0f14; }
    .bubble-other { background:var(--bg3);color:var(--text); }
  `],
})
export class AppComponent implements OnInit, OnDestroy {
  unreadNotifs = signal(0);
  unreadMsgs   = signal(0);
  chatOpen     = signal(false);
  chatLoading  = signal(false);
  convs        = signal<ConversationSummary[]>([]);
  myId         = computed(() => this.auth.user()?.user_id ?? 0);

  activeConvId       = signal<number | null>(null);
  activeConvUsername = signal('');
  activeConvLocked   = signal(false);
  activeMessages     = signal<Message[]>([]);
  popupMsg   = '';
  popupSending = signal(false);
  private msgPoll: any;
  private notifPoll: any;

  constructor(
    public auth: AuthService,
    private notifService: NotificationService,
    private convService: ConversationService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    if (this.auth.isLoggedIn()) {
      this.auth.refreshMe();
      this.pollNotifs();
    }
  }

  ngOnDestroy(): void {
    clearTimeout(this.notifPoll);
    clearInterval(this.msgPoll);
  }

  private pollNotifs(): void {
    this.notifService.unreadCount().subscribe({
      next: res => {
        this.unreadNotifs.set(res.count);
        this.notifPoll = setTimeout(() => this.pollNotifs(), 30000);
      },
      error: () => { this.notifPoll = setTimeout(() => this.pollNotifs(), 60000); },
    });
  }

  toggleChat(): void {
    this.chatOpen.update(v => !v);
    if (this.chatOpen()) { this.loadConvs(); }
  }

  private loadConvs(): void {
    this.chatLoading.set(true);
    this.convService.list().subscribe({
      next: r => {
        this.convs.set(r.conversations);
        this.unreadMsgs.set(r.conversations.reduce((s, c) => s + (c.unread || 0), 0));
        this.chatLoading.set(false);
      },
      error: () => this.chatLoading.set(false),
    });
  }

  openConv(c: ConversationSummary): void {
    this.activeConvId.set(c.conversation_id);
    this.activeConvUsername.set(c.other_user.username);
    this.activeConvLocked.set(c.lock_status === 1);
    this.activeMessages.set([]);
    this.loadPopupMessages();
    clearInterval(this.msgPoll);
    this.msgPoll = setInterval(() => this.loadPopupMessages(), 5000);
  }

  private loadPopupMessages(): void {
    const id = this.activeConvId();
    if (!id) return;
    this.convService.messages(id).subscribe({
      next: r => this.activeMessages.set(r.messages),
    });
  }

  sendPopupMsg(): void {
    const id = this.activeConvId();
    if (!id || !this.popupMsg.trim() || this.popupSending()) return;
    this.popupSending.set(true);
    this.convService.send(id, this.popupMsg).subscribe({
      next: res => {
        this.activeMessages.update(m => [...m, res.message]);
        this.popupMsg = '';
        this.popupSending.set(false);
      },
      error: () => this.popupSending.set(false),
    });
  }
}
