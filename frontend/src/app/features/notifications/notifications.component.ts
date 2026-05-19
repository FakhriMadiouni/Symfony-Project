import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { NotificationService } from '../../core/services/api.services';
import { TimeAgoPipe } from '../../shared/pipes/pipes';

interface Notif {
  notification_id: number;
  category: string;
  reference_type: string;
  reference_id: number | null;
  content: string;
  read_status: number;
  date: string;
}

type GroupKey = 'advertisements' | 'conversations' | 'tokens' | 'social' | 'honor' | 'system';

const GROUP_META: Record<GroupKey, { emoji: string; label: string }> = {
  advertisements: { emoji: '📢', label: 'Advertisements' },
  conversations:  { emoji: '💬', label: 'Conversations'  },
  tokens:         { emoji: '🎟', label: 'Tokens'         },
  social:         { emoji: '👥', label: 'Social'         },
  honor:          { emoji: '🏅', label: 'Honor'          },
  system:         { emoji: '⚙️', label: 'System'         },
};

const ALL_GROUPS: GroupKey[] = ['advertisements', 'conversations', 'tokens', 'social', 'honor', 'system'];

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule, TimeAgoPipe],
  styles: [`
    .notif-item {
      padding: .8rem 0;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: flex-start;
      gap: .75rem;
      cursor: pointer;
      transition: background .15s;
      border-radius: 6px;
    }
    .notif-item:hover { background: var(--bg3); padding-left: .5rem; }
    .notif-item.unread-item { background: rgba(232,160,69,.05); }
    .notif-date { font-size: .72rem; color: var(--muted); margin-top: .25rem; margin-bottom: 0; }
  `],
  template: `
<div class="container" style="max-width:820px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:.75rem;">
    <h1 style="text-align:center;font-family:'Syne',sans-serif;">Notifications</h1>
    @if (hasAny()) {
      <button class="btn btn-ghost btn-sm" (click)="markAllRead()">Mark all read</button>
    }
  </div>

  @if (loading()) {
    <div class="text-center py-5"><span class="spinner"></span></div>
  } @else if (!hasAny()) {
    <div class="panel" style="text-align:center;padding:3rem;">
      <p style="font-size:1.5rem;margin-bottom:.5rem;">🔔</p>
      <p style="color:var(--muted);">You have no notifications yet.</p>
    </div>
  } @else {

    <!-- Category tabs — always show all 6 -->
    <div class="tabs" style="justify-content:center;flex-wrap:wrap;margin-bottom:1.5rem;">
      @for (key of allGroups; track key) {
        <button class="tab" [class.active]="activeTab() === key" (click)="activeTab.set(key)">
          {{ meta(key).emoji }} {{ meta(key).label }}
          <span style="font-size:.73rem;opacity:.7;">({{ countFor(key) }})</span>
        </button>
      }
    </div>

    <!-- Active tab panel -->
    @for (key of allGroups; track key) {
      @if (activeTab() === key) {
        <div class="panel">
          @if (countFor(key) === 0) {
            <div style="text-align:center;padding:2rem;color:var(--muted);">
              <p style="font-size:1.2rem;margin-bottom:.5rem;">{{ meta(key).emoji }}</p>
              <p style="font-size:.88rem;">No {{ meta(key).label.toLowerCase() }} notifications yet.</p>
            </div>
          } @else {
            @for (n of grouped()[key]; track n.notification_id) {
              <div class="notif-item" [class.unread-item]="n.read_status === 0"
                   [id]="'notif-' + n.notification_id"
                   (click)="onNotifClick(n)">
                <span style="font-size:1.1rem;margin-top:.1rem;flex-shrink:0;">{{ notifIcon(n.reference_type) }}</span>
                <div style="flex:1;">
                  <p style="margin:0;font-size:.88rem;line-height:1.5;color:var(--text);">
                    {{ n.content }}
                    @if (notifLink(n)) {
                      <span style="font-size:.78rem;color:var(--accent);"> →</span>
                    }
                  </p>
                  <p class="notif-date">{{ n.date | timeAgo }} · {{ formatDate(n.date) }}</p>
                </div>
                @if (n.read_status === 0) {
                  <span style="width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:6px;"></span>
                }
              </div>
            }
          }
        </div>
      }
    }
  }
</div>
  `,
})
export class NotificationsComponent implements OnInit {
  grouped   = signal<Record<GroupKey, Notif[]>>({} as any);
  loading   = signal(true);
  activeTab = signal<GroupKey>('advertisements');

  readonly allGroups: GroupKey[] = ALL_GROUPS;

  constructor(
    private notifService: NotificationService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.notifService.getAll().subscribe({
      next: r => {
        const g = r.notifications as Record<GroupKey, Notif[]>;
        // Normalise: fill all 6 categories so template never gets undefined
        const norm = {} as Record<GroupKey, Notif[]>;
        for (const k of ALL_GROUPS) { norm[k] = g[k] ?? []; }
        this.grouped.set(norm);

        // Auto-select first non-empty category
        const first = ALL_GROUPS.find(k => norm[k].length > 0);
        if (first) this.activeTab.set(first);

        // Mark all read (PHP does this automatically on page load)
        this.notifService.markAllRead().subscribe();
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  get hasAny(): () => boolean {
    return () => ALL_GROUPS.some(k => (this.grouped()[k]?.length ?? 0) > 0);
  }

  countFor(key: GroupKey): number { return this.grouped()[key]?.length ?? 0; }
  meta(key: GroupKey): { emoji: string; label: string } { return GROUP_META[key]; }

  notifIcon(type: string): string {
    const map: Record<string, string> = {
      ad_expired:       '📢',
      conv_started:     '💬',
      conv_ended:       '🔒',
      token_purchased:  '🎟',
      token_used:       '🚀',
      user_followed:    '👤',
      ad_review:        '⭐',
      honor_rank_up:    '🎉',
      honor_rank_down:  '📉',
      report_resolved:  '✅',
      report_rejected:  '❌',
      user_banned:      '🔴',
      user_ad_banned:   '🔴',
      ad_banned:        '🔴',
      user_unbanned:    '✅',
      user_ad_unbanned: '✅',
      ad_unbanned:      '✅',
      user_muted:       '🔇',
      user_unmuted:     '🔊',
      ad_locked:        '🔒',
    };
    if (type && type.includes('warned')) return '⚠️';
    return map[type] ?? '🔔';
  }

  notifLink(n: Notif): string | null {
    const ref = n.reference_id;
    // Support notifications by content prefix
    if (n.category === 'system' && n.content?.startsWith('🎧') && ref) {
      return `/support/${ref}`;
    }
    switch (n.reference_type) {
      case 'ad_expired':    return ref ? `/ads/${ref}` : null;
      case 'conv_started':
      case 'conv_ended':    return ref ? `/conversations/${ref}` : null;
      case 'token_used':    return ref ? `/ads/${ref}` : null;
      case 'token_purchased': return null;
      case 'user_followed': return ref ? `/profile/${ref}` : null;
      case 'ad_review':     return ref ? `/ads/${ref}` : null;
      case 'honor_rank_up':
      case 'honor_rank_down': return `/profile/${0}`; // own profile with honor
      case 'report_resolved':
      case 'report_rejected': return `/support`;
      case 'ad_banned':
      case 'ad_unbanned':
      case 'ad_locked':     return ref ? `/ads/${ref}` : null;
      default:              return null;
    }
  }

  onNotifClick(n: Notif): void {
    // Mark as read locally
    this.grouped.update(g => {
      const updated = { ...g } as Record<GroupKey, Notif[]>;
      for (const k of ALL_GROUPS) {
        if (updated[k]) {
          updated[k] = updated[k].map(item =>
            item.notification_id === n.notification_id ? { ...item, read_status: 1 } : item
          );
        }
      }
      return updated;
    });

    const link = this.notifLink(n);
    if (link) {
      // Handle honor: navigate to own profile and show honor popup
      if (n.reference_type === 'honor_rank_up' || n.reference_type === 'honor_rank_down') {
        // Navigate to notifications-triggered honor in profile - done via query param
        this.router.navigate(['/profile', n.reference_id ?? 0], { queryParams: { honor: 1 } });
      } else {
        this.router.navigateByUrl(link);
      }
    }
  }

  markAllRead(): void {
    this.notifService.markAllRead().subscribe();
    this.grouped.update(g => {
      const updated = { ...g } as Record<GroupKey, Notif[]>;
      for (const k of ALL_GROUPS) {
        if (updated[k]) {
          updated[k] = updated[k].map(n => ({ ...n, read_status: 1 }));
        }
      }
      return updated;
    });
  }

  formatDate(dateStr: string): string {
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch { return dateStr; }
  }
}
