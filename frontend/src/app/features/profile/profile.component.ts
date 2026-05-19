import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { UserService, AdService, StoreService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { PublicUser, AdSummary, Review, UserToken } from '../../core/models';
import { UploadUrlPipe } from '../../shared/pipes/pipes';
import { environment } from '../../../environments/environment';

const API = environment.apiUrl;

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, UploadUrlPipe],
  styles: [`
    .ad-sub-tab {
      display:inline-flex;align-items:center;gap:.4rem;
      padding:.38rem 1rem;border-radius:8px;font-size:.82rem;font-weight:600;
      font-family:'Syne',sans-serif;cursor:pointer;border:1px solid var(--border);
      color:var(--muted);background:var(--bg2);transition:all .18s;user-select:none;
    }
    .ad-sub-tab:hover { color:var(--text);border-color:var(--accent); }
    .ad-sub-tab.active { background:var(--accent);color:#0d0f14;border-color:var(--accent); }
    .ad-sub-tab .badge {
      background:rgba(0,0,0,.18);border-radius:99px;
      padding:.05rem .45rem;font-size:.72rem;font-weight:700;
    }
    .ad-sub-tab.active .badge { background:rgba(0,0,0,.2); }
  `],
  template: `
<div class="container">
  @if (loading()) {
    <div style="text-align:center;padding:4rem;"><div class="spinner"></div></div>
  } @else if (!user()) {
    <div class="alert alert-error">User not found.</div>
  } @else {

    <!-- Profile header -->
    <div class="profile-header">
      <img class="profile-avatar"
           [src]="user()!.profile_picture | uploadUrl:'avatars'"
           alt="avatar">
      <div class="profile-info">
        <h2>
          {{ user()!.username }}
          <span style="font-size:.65rem;color:var(--muted);font-weight:400;vertical-align:middle;">#{{ user()!.user_id }}</span>
        </h2>
        @if (user()!.biography) {
          <p class="bio">{{ user()!.biography }}</p>
        }
        @if (user()!.honor_rank) {
          <div class="honor-badge" (click)="showHonor.set(true)" style="margin-top:.5rem;">
            <span class="honor-dot" [style.background]="user()!.honor_rank!.color || '#888'"></span>
            {{ user()!.honor_rank!.name }} — {{ user()!.honor_points }} pts
          </div>
        }
        <p style="font-size:.8rem;color:var(--muted);margin-top:.5rem;">
          <strong>{{ user()!.followers }}</strong> follower{{ user()!.followers !== 1 ? 's' : '' }}
          &nbsp;·&nbsp;
          <strong>{{ user()!.following }}</strong> following
        </p>
        @if (auth.isLoggedIn() && auth.user()?.user_id !== user()!.user_id) {
          <div style="margin-top:.75rem;">
            @if (!following()) {
              <button class="btn btn-primary btn-sm" (click)="follow()" [disabled]="followLoading()">
                @if (followLoading()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
                + Follow
              </button>
            } @else {
              <button class="btn btn-ghost btn-sm btn-following" (click)="unfollow()" [disabled]="followLoading()">
                ✓ Following
              </button>
            }
          </div>
        }
        @if (auth.isStaff()) {
          <a [routerLink]="['/admin/users', user()!.user_id]" class="btn btn-ghost btn-sm" style="margin-top:.5rem;">
            ⚙ Manage
          </a>
        }
        @if (auth.isLoggedIn() && auth.user()?.user_id !== user()!.user_id) {
          <a [routerLink]="['/report']" [queryParams]="{user_id: user()!.user_id, username: user()!.username, type: 'user'}"
             class="btn btn-ghost btn-sm" style="margin-top:.5rem;color:var(--danger);border-color:var(--danger);">🚩 Report</a>
        }
      </div>
    </div>

    @if (error()) { <div class="alert alert-error">{{ error() }}</div> }
    @if (success()) { <div class="alert alert-success">{{ success() }}</div> }

    <!-- Main tabs -->
    <div class="tabs">
      <button class="tab" [class.active]="tab() === 'ads'" (click)="tab.set('ads')">
        Advertisements <span style="font-size:.75rem;color:var(--muted);">({{ allAds().length }})</span>
      </button>
      @if (isOwn) {
        <button class="tab" [class.active]="tab() === 'settings'" (click)="tab.set('settings')">Settings</button>
      }
    </div>

    <!-- ── ADS TAB ────────────────────────────────────────────────── -->
    @if (tab() === 'ads') {
      @if (adsLoading()) {
        <div style="text-align:center;padding:2rem;"><div class="spinner"></div></div>
      } @else if (allAds().length === 0) {
        <div class="panel" style="text-align:center;padding:2.5rem;">
          <p style="color:var(--muted);">
            {{ isOwn ? "You haven't posted any ads yet." : user()!.username + " hasn't posted any ads yet." }}
          </p>
          @if (isOwn) {
            <a routerLink="/ads/new" class="btn btn-primary" style="margin-top:1rem;">Post Your First Ad</a>
          }
        </div>
      } @else {
        <!-- Sub-tab buttons -->
        <div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap;">
          <button class="ad-sub-tab" [class.active]="adTab() === 'active'" (click)="adTab.set('active')">
            ● Active <span class="badge">{{ activeAds().length }}</span>
          </button>
          @if (isOwn) {
            <button class="ad-sub-tab" [class.active]="adTab() === 'hidden'" (click)="adTab.set('hidden')">
              ⏸ Hidden <span class="badge">{{ hiddenAds().length }}</span>
            </button>
          }
          <button class="ad-sub-tab" [class.active]="adTab() === 'expired'" (click)="adTab.set('expired')">
            ○ Inactive <span class="badge">{{ expiredAds().length }}</span>
          </button>
          @if (isOwn && bannedAds().length > 0) {
            <button class="ad-sub-tab" [class.active]="adTab() === 'banned'"
                    (click)="adTab.set('banned')"
                    style="border-color:rgba(220,50,50,.4);color:var(--danger);">
              ⚑ Banned <span class="badge">{{ bannedAds().length }}</span>
            </button>
          }
        </div>

        <!-- Active -->
        @if (adTab() === 'active') {
          @if (activeAds().length === 0) {
            <div class="panel" style="text-align:center;padding:2rem;">
              <p style="color:var(--muted);">No active advertisements.</p>
              @if (isOwn) {
                <a routerLink="/ads/new" class="btn btn-primary" style="margin-top:1rem;font-size:.85rem;">Post an Ad</a>
              }
            </div>
          } @else {
            <div style="display:flex;flex-direction:column;gap:.75rem;">
              @for (ad of activeAds(); track ad.ad_id) {
                <a [routerLink]="['/ads', ad.ad_id]" class="ad-row">
                  <div style="width:68px;height:68px;border-radius:8px;overflow:hidden;flex-shrink:0;background:var(--bg3);">
                    <img [src]="ad.thumbnail | uploadUrl:'images'" style="width:100%;height:100%;object-fit:cover;" alt="">
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.25rem;flex-wrap:wrap;">
                      <span class="ad-cat-tag">{{ ad.subcategory.name }}</span>
                      <span style="font-size:.72rem;color:var(--success);">● Active</span>
                    </div>
                    <div class="ad-row-title" style="font-family:'Syne',sans-serif;font-weight:600;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .2s;">
                      {{ ad.title }}
                    </div>
                    <div style="font-size:.82rem;color:var(--accent);margin-top:.2rem;">
                      {{ ad.price === '0.00' ? 'Free' : (ad.price | currency) }}
                    </div>
                  </div>
                </a>
              }
            </div>
          }
        }

        <!-- Hidden (own only) -->
        @if (adTab() === 'hidden' && isOwn) {
          @if (hiddenAds().length === 0) {
            <div class="panel" style="text-align:center;padding:2rem;">
              <p style="color:var(--muted);">No hidden advertisements.</p>
            </div>
          } @else {
            <div style="display:flex;flex-direction:column;gap:.75rem;">
              @for (ad of hiddenAds(); track ad.ad_id) {
                <a [routerLink]="['/ads', ad.ad_id]" class="ad-row">
                  <div style="width:68px;height:68px;border-radius:8px;overflow:hidden;flex-shrink:0;background:var(--bg3);">
                    <img [src]="ad.thumbnail | uploadUrl:'images'" style="width:100%;height:100%;object-fit:cover;" alt="">
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.25rem;">
                      <span class="ad-cat-tag">{{ ad.subcategory.name }}</span>
                      <span style="font-size:.72rem;color:var(--accent);">⏸ Hidden</span>
                    </div>
                    <div class="ad-row-title" style="font-family:'Syne',sans-serif;font-weight:600;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .2s;">
                      {{ ad.title }}
                    </div>
                    <div style="font-size:.82rem;color:var(--accent);margin-top:.2rem;">
                      {{ ad.price === '0.00' ? 'Free' : (ad.price | currency) }}
                    </div>
                  </div>
                </a>
              }
            </div>
          }
        }

        <!-- Expired -->
        @if (adTab() === 'expired') {
          @if (expiredAds().length === 0) {
            <div class="panel" style="text-align:center;padding:2rem;">
              <p style="color:var(--muted);">No inactive advertisements.</p>
            </div>
          } @else {
            <div style="display:flex;flex-direction:column;gap:.75rem;">
              @for (ad of expiredAds(); track ad.ad_id) {
                <div class="ad-row" style="flex-wrap:wrap;gap:.75rem;">
                  <a [routerLink]="['/ads', ad.ad_id]" style="display:contents;">
                    <div style="width:68px;height:68px;border-radius:8px;overflow:hidden;flex-shrink:0;background:var(--bg3);">
                      <img [src]="ad.thumbnail | uploadUrl:'images'" style="width:100%;height:100%;object-fit:cover;opacity:.65;" alt="">
                    </div>
                    <div style="flex:1;min-width:0;">
                      <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.25rem;flex-wrap:wrap;">
                        <span class="ad-cat-tag">{{ ad.subcategory.name }}</span>
                        <span style="font-size:.72rem;color:var(--muted);">○ Expired</span>
                      </div>
                      <div style="font-family:'Syne',sans-serif;font-weight:600;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;opacity:.7;">
                        {{ ad.title }}
                      </div>
                      <div style="font-size:.82rem;color:var(--muted);margin-top:.2rem;">
                        {{ ad.price === '0.00' ? 'Free' : (ad.price | currency) }}
                      </div>
                    </div>
                  </a>
                  @if (isOwn) {
                    <div style="width:100%;padding-top:.75rem;border-top:1px solid var(--border);">
                      @if (availableTokens().length > 0) {
                        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                          <span style="font-size:.78rem;color:var(--muted);flex-shrink:0;">Re-advertise with:</span>
                          <select #tokenSel style="flex:1;min-width:160px;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:.35rem .6rem;border-radius:6px;font-size:.82rem;"
                                  (change)="setReadvertiseToken(ad.ad_id, +tokenSel.value)">
                            <option value="0">-- Select Token --</option>
                            @for (t of availableTokens(); track t.user_token_id) {
                              <option [value]="t.user_token_id">{{ t.name }} — {{ t.ad_duration }}d · {{ t.max_media }} media</option>
                            }
                          </select>
                          <button class="btn btn-primary" style="font-size:.78rem;padding:.3rem .9rem;"
                                  (click)="readvertise(ad.ad_id)"
                                  [disabled]="readvertising() === ad.ad_id">
                            @if (readvertising() === ad.ad_id) { <span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> }
                            @else { 🔁 Re-Advertise }
                          </button>
                        </div>
                        @if (readvertiseError()) { <p style="font-size:.78rem;color:var(--danger);margin-top:.35rem;">{{ readvertiseError() }}</p> }
                        @if (readvertiseSuccess()) { <p style="font-size:.78rem;color:var(--success);margin-top:.35rem;">{{ readvertiseSuccess() }}</p> }
                      } @else {
                        <a routerLink="/store" style="font-size:.78rem;color:var(--accent);">🪙 Get tokens to re-advertise →</a>
                      }
                    </div>
                  }
                </div>
              }
            </div>
          }
        }

        <!-- Banned -->
        @if (adTab() === 'banned' && isOwn) {
          <div style="display:flex;flex-direction:column;gap:.75rem;">
            @for (ad of bannedAds(); track ad.ad_id) {
              <a [routerLink]="['/ads', ad.ad_id]" class="ad-row" style="opacity:.6;">
                <div style="width:68px;height:68px;border-radius:8px;overflow:hidden;flex-shrink:0;background:var(--bg3);">
                  <img [src]="ad.thumbnail | uploadUrl:'images'" style="width:100%;height:100%;object-fit:cover;" alt="">
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.25rem;">
                    <span class="ad-cat-tag">{{ ad.subcategory.name }}</span>
                    <span style="font-size:.72rem;color:var(--danger);">⚑ Banned</span>
                  </div>
                  <div style="font-family:'Syne',sans-serif;font-weight:600;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ ad.title }}
                  </div>
                  <div style="font-size:.82rem;color:var(--muted);margin-top:.2rem;">
                    {{ ad.price === '0.00' ? 'Free' : (ad.price | currency) }}
                  </div>
                </div>
              </a>
            }
          </div>
        }
      }
    }

    <!-- ── SETTINGS TAB (own only) ───────────────────────────────── -->
    @if (tab() === 'settings' && isOwn) {
      <!-- Profile info -->
      <div class="panel" style="margin-bottom:1.25rem;">
        <h3>Profile Info</h3>
        <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
          <img [src]="auth.user()?.profile_picture | uploadUrl:'avatars'"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;"
               alt="avatar">
          <div style="flex:1;">
            <label style="display:block;font-size:.82rem;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem;">Profile Picture</label>
            <input type="file" accept="image/jpeg,image/png,image/webp" (change)="onAvatarChange($event)" [disabled]="savingAvatar()">
            <p style="font-size:.76rem;color:var(--muted);margin-top:.3rem;">JPG, PNG, WebP — max 2MB</p>
            @if (avatarMsg()) {
              <p [style.color]="avatarError() ? 'var(--danger)' : 'var(--success)'" style="font-size:.8rem;margin-top:.3rem;">{{ avatarMsg() }}</p>
            }
          </div>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input [(ngModel)]="editUsername">
        </div>
        <div class="form-group">
          <label>Biography</label>
          <textarea [(ngModel)]="editBio"></textarea>
        </div>
        @if (profileError()) { <div class="alert alert-error" style="margin-bottom:1rem;">{{ profileError() }}</div> }
        @if (profileSuccess()) { <div class="alert alert-success" style="margin-bottom:1rem;">{{ profileSuccess() }}</div> }
        <button class="btn btn-primary" (click)="saveProfile()" [disabled]="savingProfile()">
          @if (savingProfile()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
          Save Changes
        </button>
      </div>

      <!-- Account status -->
      <div class="panel">
        <h3>Account Status</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem;">
          <div style="padding:.75rem;background:var(--bg3);border-radius:8px;font-size:.85rem;">
            <span style="color:var(--muted);">Account Ban:</span>
            <span style="margin-left:.5rem;font-weight:600;"
                  [style.color]="auth.user()?.ban_status === 1 ? 'var(--danger)' : 'var(--success)'">
              {{ auth.user()?.ban_status === 1 ? 'Banned' : 'Good standing' }}
            </span>
          </div>
          <div style="padding:.75rem;background:var(--bg3);border-radius:8px;font-size:.85rem;">
            <span style="color:var(--muted);">Ad Posting:</span>
            <span style="margin-left:.5rem;font-weight:600;"
                  [style.color]="auth.user()?.ad_ban_status === 1 ? 'var(--danger)' : 'var(--success)'">
              {{ auth.user()?.ad_ban_status === 1 ? 'Banned' : 'Allowed' }}
            </span>
          </div>
          <div style="padding:.75rem;background:var(--bg3);border-radius:8px;font-size:.85rem;">
            <span style="color:var(--muted);">Messaging:</span>
            <span style="margin-left:.5rem;font-weight:600;"
                  [style.color]="auth.user()?.mute_status === 1 ? 'var(--danger)' : 'var(--success)'">
              {{ auth.user()?.mute_status === 1 ? 'Muted' : 'Allowed' }}
            </span>
          </div>
          <div style="padding:.75rem;background:var(--bg3);border-radius:8px;font-size:.85rem;">
            <span style="color:var(--muted);">Honor Points:</span>
            <span style="margin-left:.5rem;font-weight:600;color:var(--accent);">{{ auth.user()?.honor_points }}</span>
          </div>
        </div>
      </div>

      <!-- Change password -->
      <div class="panel">
        <h3>Change Password</h3>
        @if (!pwCodeSent()) {
          <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;">We'll send a verification code to your email address.</p>
          @if (pwError()) { <div class="alert alert-error" style="margin-bottom:.75rem;">{{ pwError() }}</div> }
          <button class="btn btn-ghost btn-sm" (click)="sendPwCode()" [disabled]="pwSending()">
            @if (pwSending()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
            Send Code to Email
          </button>
        } @else {
          @if (pwError()) { <div class="alert alert-error" style="margin-bottom:.75rem;">{{ pwError() }}</div> }
          @if (pwSuccess()) { <div class="alert alert-success" style="margin-bottom:.75rem;">{{ pwSuccess() }}</div> }
          <div class="form-group">
            <label>Verification Code</label>
            <input [(ngModel)]="pwCode" maxlength="6" placeholder="000000" style="letter-spacing:8px;text-align:center;">
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" [(ngModel)]="pwNew" placeholder="At least 8 characters">
          </div>
          <div style="display:flex;gap:.5rem;">
            <button class="btn btn-primary" (click)="changePassword()" [disabled]="pwChanging()">
              @if (pwChanging()) { <span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> }
              Change Password
            </button>
            <button class="btn btn-ghost btn-sm" (click)="pwCodeSent.set(false);pwError.set('');pwSuccess.set('');">Cancel</button>
          </div>
        }
      </div>
    }

    <!-- Honor popup -->
    @if (showHonor()) {
      <div class="modal-overlay" (click)="showHonor.set(false)">
        <div class="modal-box" (click)="$event.stopPropagation()">
          <button (click)="showHonor.set(false)"
                  style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer;">✕</button>
          <h2 style="font-size:1.05rem;margin-bottom:.25rem;">🏅 Honor Ranks</h2>
          <p style="font-size:.8rem;color:var(--muted);margin-bottom:1rem;">
            {{ user()!.username }}'s current rank is highlighted.
          </p>
          @if (user()!.honor_rank) {
            <div style="padding:.9rem 1rem;background:var(--bg3);border-radius:10px;border:1px solid var(--border);margin-bottom:1.25rem;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                <span style="font-size:.8rem;font-weight:600;color:var(--accent);">{{ user()!.honor_rank!.name }}</span>
                <span style="font-size:.8rem;color:var(--muted);">{{ user()!.honor_points }} pts</span>
              </div>
              <div style="background:var(--bg2);border-radius:99px;height:7px;overflow:hidden;">
                <div [style.width.%]="honorProgress()" [style.background]="user()!.honor_rank!.color || 'var(--accent)'"
                     style="height:100%;border-radius:99px;transition:width .4s;"></div>
              </div>
            </div>
          }
        </div>
      </div>
    }

  }
</div>
  `,
})
export class ProfileComponent implements OnInit {
  user          = signal<PublicUser | null>(null);
  allAds        = signal<AdSummary[]>([]);
  activeAds     = signal<AdSummary[]>([]);
  hiddenAds     = signal<AdSummary[]>([]);
  expiredAds    = signal<AdSummary[]>([]);
  bannedAds     = signal<AdSummary[]>([]);
  following     = signal(false);
  tab           = signal<'ads' | 'settings'>('ads');
  adTab         = signal<'active' | 'hidden' | 'expired' | 'banned'>('active');
  loading       = signal(true);
  adsLoading    = signal(false);
  followLoading = signal(false);
  showHonor     = signal(false);
  error         = signal('');
  success       = signal('');

  // Available tokens for re-advertise
  availableTokens = signal<UserToken[]>([]);
  // Per-ad token selection for re-advertise: ad_id -> user_token_id
  readvTokenMap: Map<number, number> = new Map();
  readvertising = signal(0); // ad_id currently being re-advertised
  readvertiseError = signal('');
  readvertiseSuccess = signal('');

  // Settings
  editUsername    = '';
  editBio         = '';
  savingProfile   = signal(false);
  profileError    = signal('');
  profileSuccess  = signal('');
  savingAvatar    = signal(false);
  avatarMsg       = signal('');
  avatarError     = signal(false);

  userId = 0;

  // Change password
  pwCodeSent = signal(false); pwSending = signal(false); pwChanging = signal(false);
  pwCode = ''; pwNew = '';
  pwError = signal(''); pwSuccess = signal('');

  constructor(
    private route: ActivatedRoute,
    private userService: UserService,
    private adService: AdService,
    private storeService: StoreService,
    private http: HttpClient,
    public  auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.userId = +this.route.snapshot.paramMap.get('id')!;
    this.userService.getProfile(this.userId).subscribe({
      next: r => {
        this.user.set(r.user);
        this.editUsername = r.user.username;
        this.editBio      = r.user.biography ?? '';
        // Set following state from API response
        this.following.set(r.user.is_following ?? false);
        this.loading.set(false);
        this.loadAds();
      },
      error: () => this.loading.set(false),
    });
    // Load user tokens if own profile (for re-advertise)
    if (this.auth.user()?.user_id === this.userId) {
      this.storeService.myTokens().subscribe({
        next: r => this.availableTokens.set(r.tokens),
      });
    }
  }

  get isOwn(): boolean {
    return this.auth.user()?.user_id === this.userId;
  }

  honorProgress(): number {
    const u = this.user();
    if (!u?.honor_rank) return 0;
    // honor_rank only carries { name, color } — use honor_points as a simple 0–1000 scale
    return Math.min(100, Math.max(0, (u.honor_points / 1000) * 100));
  }

  private loadAds(): void {
    this.adsLoading.set(true);
    this.adService.userAds(this.userId).subscribe({
      next: r => {
        const ads = r.ads as AdSummary[];
        this.allAds.set(ads);
        this.activeAds.set(ads.filter((a: any) => a.active && !a.ban_status && !a.hidden_by_advertiser));
        this.hiddenAds.set(ads.filter((a: any) => a.active && !a.ban_status && a.hidden_by_advertiser));
        this.expiredAds.set(ads.filter((a: any) => !a.active && !a.ban_status));
        this.bannedAds.set(ads.filter((a: any) => !!a.ban_status));
        this.adsLoading.set(false);
      },
      error: () => this.adsLoading.set(false),
    });
  }

  readvertise(adId: number): void {
    const tokenId = this.readvTokenMap.get(adId);
    if (!tokenId) { this.readvertiseError.set('Please select a token first.'); return; }
    this.readvertising.set(adId); this.readvertiseError.set(''); this.readvertiseSuccess.set('');
    this.userService.readvertise(adId, tokenId).subscribe({
      next: () => {
        this.readvertiseSuccess.set('Ad re-activated successfully!');
        this.readvertising.set(0);
        this.loadAds();
        // Refresh token list
        this.storeService.myTokens().subscribe({ next: r => this.availableTokens.set(r.tokens) });
      },
      error: err => {
        this.readvertiseError.set(err.error?.error || 'Re-advertise failed.');
        this.readvertising.set(0);
      },
    });
  }

  getReadvertiseToken(adId: number): number { return this.readvTokenMap.get(adId) ?? 0; }
  setReadvertiseToken(adId: number, tokenId: number): void { this.readvTokenMap.set(adId, tokenId); }

  follow(): void {
    this.followLoading.set(true);
    this.userService.follow(this.userId).subscribe({
      next: () => {
        this.following.set(true);
        this.followLoading.set(false);
        this.user.update(u => u ? { ...u, followers: u.followers + 1 } : u);
      },
      error: () => this.followLoading.set(false),
    });
  }

  unfollow(): void {
    this.followLoading.set(true);
    this.userService.unfollow(this.userId).subscribe({
      next: () => {
        this.following.set(false);
        this.followLoading.set(false);
        this.user.update(u => u ? { ...u, followers: u.followers - 1 } : u);
      },
      error: () => this.followLoading.set(false),
    });
  }

  saveProfile(): void {
    this.profileError.set(''); this.profileSuccess.set(''); this.savingProfile.set(true);
    this.userService.updateProfile({ username: this.editUsername, biography: this.editBio }).subscribe({
      next: () => {
        this.auth.updateLocalUser({ username: this.editUsername, biography: this.editBio });
        this.profileSuccess.set('Profile updated!');
        this.savingProfile.set(false);
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

  sendPwCode(): void {
    this.pwSending.set(true); this.pwError.set('');
    this.http.post<any>(`${API}/auth/send-change-password-code`, {}).subscribe({
      next: () => { this.pwCodeSent.set(true); this.pwSending.set(false); },
      error: err => { this.pwError.set(err.error?.error || 'Failed to send code.'); this.pwSending.set(false); },
    });
  }

  changePassword(): void {
    if (!this.pwCode || !this.pwNew) { this.pwError.set('Please fill in all fields.'); return; }
    if (this.pwNew.length < 8) { this.pwError.set('Password must be at least 8 characters.'); return; }
    this.pwChanging.set(true); this.pwError.set('');
    this.http.post<any>(`${API}/auth/change-password`, { code: this.pwCode, new_password: this.pwNew }).subscribe({
      next: () => {
        this.pwSuccess.set('Password changed successfully!');
        this.pwChanging.set(false); this.pwCode = ''; this.pwNew = '';
        setTimeout(() => { this.pwCodeSent.set(false); this.pwSuccess.set(''); }, 3000);
      },
      error: err => { this.pwError.set(err.error?.error || 'Failed to change password.'); this.pwChanging.set(false); },
    });
  }
}
