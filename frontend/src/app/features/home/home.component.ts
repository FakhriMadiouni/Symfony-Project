import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AdService, StoreService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { AdSummary } from '../../core/models';
import { TimeAgoPipe, UploadUrlPipe } from '../../shared/pipes/pipes';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TimeAgoPipe, UploadUrlPipe],
  template: `
<div class="home-layout">

  <!-- ── Sidebar ───────────────────────────────────────────── -->
  <aside class="home-sidebar">
    <!-- User card -->
    <div class="panel" style="text-align:center;padding:1.5rem 1rem;">
      <img [src]="auth.user()?.profile_picture | uploadUrl:'avatars'"
           style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);margin-bottom:.75rem;" alt="avatar">
      <div style="font-weight:700;font-family:'Syne',sans-serif;">{{ auth.user()?.username }}</div>
      <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">✨ {{ auth.user()?.honor_points }} honor points</div>
      <a [routerLink]="['/profile', auth.user()?.user_id]" class="btn btn-ghost btn-sm" style="margin-top:.75rem;width:100%;">My Profile</a>
    </div>

    <!-- Token wallet -->
    <div class="panel">
      <h3 style="animation:neon-tokens 2s infinite;border:1px solid var(--border);padding:.3rem .6rem;border-radius:6px;display:inline-block;margin-bottom:1rem;">
        🎟 Ad Tokens
      </h3>
      @if (tokenLoading()) {
        <div class="text-center"><span class="spinner" style="width:20px;height:20px;border-width:2px;"></span></div>
      } @else if (myTokens().length === 0) {
        <p style="font-size:.82rem;color:var(--muted);">No tokens yet.</p>
        <a routerLink="/store" class="btn btn-primary btn-sm btn-block" style="margin-top:.5rem;">Buy Tokens</a>
      } @else {
        @for (t of myTokens(); track t.user_token_id) {
          <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.6rem .8rem;margin-bottom:.5rem;font-size:.82rem;">
            <div style="font-weight:600;">{{ t.name }}</div>
            <div style="color:var(--muted);font-size:.72rem;">{{ t.ad_duration }} days · max {{ t.max_media }} media</div>
          </div>
        }
        <a routerLink="/ads/new" class="btn btn-primary btn-sm btn-block" style="margin-top:.5rem;">📢 Post an Ad</a>
        <a routerLink="/store" class="btn btn-ghost btn-sm btn-block" style="margin-top:.4rem;">+ Buy More</a>
      }
    </div>

    <!-- Quick nav -->
    <div class="panel">
      <h3>Quick Links</h3>
      <nav style="display:flex;flex-direction:column;gap:.4rem;">
        <a routerLink="/search" style="color:var(--text);font-size:.88rem;">🔍 Browse All Ads</a>
        <a routerLink="/conversations" style="color:var(--text);font-size:.88rem;">💬 My Conversations</a>
        <a routerLink="/store" style="color:var(--text);font-size:.88rem;">🏪 Token Store</a>
        <a routerLink="/support" style="color:var(--text);font-size:.88rem;">🎧 Support</a>
        <a routerLink="/settings" style="color:var(--text);font-size:.88rem;">⚙ Settings</a>
      </nav>
    </div>
  </aside>

  <!-- ── Main feed ─────────────────────────────────────────── -->
  <main class="home-main">
    <div class="home-feed-header">
      <div>
        <h2 style="font-family:'Syne',sans-serif;font-size:1.4rem;margin-bottom:.2rem;">
          @if (tab() === 'feed') { 📌 Your Feed }
          @else { 🌐 Discover }
        </h2>
        <p style="font-size:.83rem;color:var(--muted);">
          @if (tab() === 'feed') { Ads from people you follow }
          @else { Latest ads from everyone }
        </p>
      </div>

      <!-- Search bar -->
      <div style="display:flex;gap:.5rem;flex:1;max-width:340px;">
        <input style="flex:1;background:var(--bg3);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:.5rem .9rem;font-size:.88rem;"
               [(ngModel)]="searchQ" placeholder="Search ads…" (keyup.enter)="load()">
        <button class="btn btn-primary btn-sm" (click)="load()">Go</button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs" style="margin-bottom:1.25rem;">
      <span class="tab" [class.active]="tab()==='feed'" (click)="setTab('feed')">📌 Following</span>
      <span class="tab" [class.active]="tab()==='discover'" (click)="setTab('discover')">🌐 Discover</span>
    </div>

    @if (loading()) {
      <div class="text-center py-5">
        <span class="spinner" style="width:40px;height:40px;"></span>
      </div>
    } @else if (ads().length === 0 && tab() === 'feed') {
      <div class="text-center py-5" style="color:var(--muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
        <p>Your feed is empty. Follow some users to see their ads here.</p>
        <a routerLink="/search" class="btn btn-primary" style="margin-top:1rem;">Browse & Follow Users</a>
      </div>
    } @else if (ads().length === 0) {
      <div class="text-center py-5" style="color:var(--muted);">
        <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
        <p>No ads found.</p>
      </div>
    } @else {
      <div class="ad-grid">
        @for (ad of ads(); track ad.ad_id) {
          <a [routerLink]="['/ads', ad.ad_id]" class="ad-card" [class.seen]="isSeen(ad.ad_id)">
            <div class="ad-card-img"
                 [style.backgroundImage]="ad.thumbnail ? 'url(' + (ad.thumbnail | uploadUrl:'images') + ')' : 'none'"
                 style="position:relative;">
              @if (!ad.thumbnail) {
                <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--muted);">📷</div>
              }
              @if (isSeen(ad.ad_id)) {
                <span style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);border-radius:4px;padding:2px 6px;font-size:.65rem;color:var(--muted);">SEEN</span>
              }
            </div>
            <div class="ad-card-body">
              <span class="ad-cat-tag">{{ ad.subcategory.name }}</span>
              <p class="ad-card-title">{{ ad.title }}</p>
              <div class="ad-card-footer">
                <span style="font-weight:700;color:var(--accent);">{{ +ad.price > 0 ? '$' + ad.price : 'Free' }}</span>
                <span style="font-size:.72rem;color:var(--muted);">{{ ad.country.name }}</span>
              </div>
              <div style="font-size:.72rem;color:var(--muted);margin-top:.4rem;">by {{ ad.user.username }} · {{ ad.creation_date | timeAgo }}</div>
            </div>
          </a>
        }
      </div>

      <!-- Pagination -->
      @if (total() > limit) {
        <div style="display:flex;justify-content:center;gap:.5rem;margin-top:2rem;flex-wrap:wrap;">
          @if (offset() > 0) {
            <button class="btn btn-ghost btn-sm" (click)="prev()">← Previous</button>
          }
          <span style="color:var(--muted);font-size:.85rem;align-self:center;">
            {{ offset() + 1 }}–{{ min(offset() + ads().length, total()) }} of {{ total() }}
          </span>
          @if (offset() + limit < total()) {
            <button class="btn btn-primary btn-sm" (click)="next()">Next →</button>
          }
        </div>
      }
    }
  </main>
</div>
  `,
  styles: [`
    .home-layout {
      display:grid;
      grid-template-columns:240px 1fr;
      gap:1.5rem;
      max-width:1100px;
      margin:0 auto;
      padding:1.5rem;
    }
    @media(max-width:768px){
      .home-layout { grid-template-columns:1fr; }
      .home-sidebar { display:none; }
    }
    .home-sidebar { display:flex;flex-direction:column;gap:.75rem; }
    .home-main { min-width:0; }
    .home-feed-header {
      display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;
      flex-wrap:wrap;margin-bottom:1rem;
    }
    .ad-grid {
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(220px,1fr));
      gap:1rem;
    }
    .ad-card.seen { opacity:.65; }
  `],
})
export class HomeComponent implements OnInit {
  ads          = signal<AdSummary[]>([]);
  myTokens     = signal<any[]>([]);
  loading      = signal(true);
  tokenLoading = signal(true);
  total        = signal(0);
  offset       = signal(0);
  tab          = signal<'feed'|'discover'>('feed');
  searchQ      = '';
  readonly limit = 20;

  constructor(
    private adService: AdService,
    private storeService: StoreService,
    public auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.load();
    this.loadTokens();
  }

  setTab(t: 'feed'|'discover'): void {
    if (this.tab() === t) return;
    this.tab.set(t);
    this.offset.set(0);
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const params: any = { limit: this.limit, offset: this.offset(), sort: 'newest' };
    if (this.searchQ.trim()) params['search'] = this.searchQ.trim();
    if (this.tab() === 'feed') params['followed_only'] = true;

    this.adService.list(params).subscribe({
      next: r => {
        r.ads.forEach(a => this.markSeen(a.ad_id));
        this.ads.set(r.ads);
        this.total.set(r.total);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private loadTokens(): void {
    this.storeService.myTokens().subscribe({
      next: r => { this.myTokens.set(r.tokens); this.tokenLoading.set(false); },
      error: () => this.tokenLoading.set(false),
    });
  }

  prev(): void { this.offset.update(o => Math.max(0, o - this.limit)); this.load(); }
  next(): void { this.offset.update(o => o + this.limit); this.load(); }
  min(a: number, b: number): number { return Math.min(a, b); }

  // Seen tracking via localStorage
  private seenKey = 'mpm_seen_ads';
  private seenSet: Set<number> = new Set(
    JSON.parse(localStorage.getItem('mpm_seen_ads') || '[]')
  );

  markSeen(adId: number): void {
    this.seenSet.add(adId);
    localStorage.setItem(this.seenKey, JSON.stringify([...this.seenSet]));
  }
  isSeen(adId: number): boolean { return this.seenSet.has(adId); }
}
