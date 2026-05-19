import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AdService, LocationService } from '../../core/services/api.services';
import { AdSummary, Category, Country } from '../../core/models';
import { TimeAgoPipe, UploadUrlPipe } from '../../shared/pipes/pipes';

@Component({
  selector: 'app-search',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TimeAgoPipe, UploadUrlPipe],
  template: `
<div class="container">
  <h2 style="font-family:'Syne',sans-serif;margin-bottom:1.5rem;">🔍 Browse Ads</h2>

  <!-- Filters -->
  <div class="panel" style="margin-bottom:1.5rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end;flex-wrap:wrap;">
      <div class="form-group" style="margin-bottom:0;">
        <label>Search</label>
        <input [(ngModel)]="search" placeholder="Keywords…" (keyup.enter)="doSearch()">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>Category</label>
        <select [(ngModel)]="selectedCat" (change)="onCatChange()">
          <option value="">All Categories</option>
          @for (c of categories(); track c.category_id) {
            <option [value]="c.category_id">{{ c.name }}</option>
          }
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>Subcategory</label>
        <select [(ngModel)]="selectedSubcat">
          <option value="">All</option>
          @for (s of subcats(); track s.subcategory_id) {
            <option [value]="s.subcategory_id">{{ s.name }}</option>
          }
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>Country</label>
        <select [(ngModel)]="selectedCountry">
          <option value="">All Countries</option>
          @for (c of countries(); track c.country_id) {
            <option [value]="c.country_id">{{ c.name }}</option>
          }
        </select>
      </div>
    </div>
    <div style="display:flex;gap:.5rem;margin-top:.75rem;align-items:center;flex-wrap:wrap;">
      <div class="form-group" style="margin-bottom:0;">
        <label>Sort</label>
        <select [(ngModel)]="sort" style="width:auto;">
          <option value="newest">Newest first</option>
          <option value="oldest">Oldest first</option>
          <option value="price_asc">Price: Low → High</option>
          <option value="price_desc">Price: High → Low</option>
        </select>
      </div>
      <button class="btn btn-primary" style="margin-top:1.4rem;" (click)="doSearch()">Search</button>
      <button class="btn btn-ghost btn-sm" style="margin-top:1.4rem;" (click)="reset()">Clear</button>
      @if (loading()) {
        <span class="spinner" style="width:20px;height:20px;border-width:2px;margin-top:1.4rem;"></span>
      }
    </div>
  </div>

  <!-- Results header -->
  @if (!loading()) {
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;">
      {{ total() }} result{{ total() === 1 ? '' : 's' }} found
    </p>
  }

  <!-- Ad grid -->
  @if (loading() && ads().length === 0) {
    <div class="text-center py-5"><span class="spinner"></span></div>
  } @else if (ads().length === 0 && !loading()) {
    <div class="text-center py-5" style="color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
      <p>No ads found. Try different filters.</p>
    </div>
  } @else {
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
      @for (ad of ads(); track ad.ad_id) {
        <a [routerLink]="['/ads', ad.ad_id]" class="ad-card">
          <div class="ad-card-img"
               [style.backgroundImage]="ad.thumbnail ? 'url(' + (ad.thumbnail | uploadUrl:'images') + ')' : 'none'">
            @if (!ad.thumbnail) {
              <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--muted);">📷</div>
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
          {{ offset() + 1 }}–{{ Math.min(offset() + ads().length, total()) }} of {{ total() }}
        </span>
        @if (offset() + limit < total()) {
          <button class="btn btn-primary btn-sm" (click)="next()">Next →</button>
        }
      </div>
    }
  }
</div>
  `,
})
export class SearchComponent implements OnInit {
  ads      = signal<AdSummary[]>([]);
  loading  = signal(true);
  total    = signal(0);
  offset   = signal(0);
  readonly limit = 20;

  categories = signal<Category[]>([]);
  subcats    = signal<any[]>([]);
  countries  = signal<Country[]>([]);

  search          = '';
  selectedCat     = '';
  selectedSubcat  = '';
  selectedCountry = '';
  sort            = 'newest';

  readonly Math = Math;

  constructor(private adService: AdService, private locService: LocationService) {}

  ngOnInit(): void {
    this.locService.countries().subscribe(r => this.countries.set(r.countries));
    this.locService.categories().subscribe(r => this.categories.set(r.categories));
    this.doSearch();
  }

  onCatChange(): void {
    this.selectedSubcat = '';
    this.subcats.set([]);
    if (this.selectedCat) {
      this.locService.subcategories(+this.selectedCat).subscribe(r => this.subcats.set(r.subcategories));
    }
  }

  doSearch(): void {
    this.offset.set(0);
    this.fetch();
  }

  private fetch(): void {
    this.loading.set(true);
    const params: any = { limit: this.limit, offset: this.offset(), sort: this.sort };
    if (this.search.trim())     params['search']      = this.search.trim();
    if (this.selectedSubcat)    params['subcategory'] = +this.selectedSubcat;
    if (this.selectedCountry)   params['country']     = +this.selectedCountry;

    this.adService.list(params).subscribe({
      next: r => { this.ads.set(r.ads); this.total.set(r.total); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  reset(): void {
    this.search = ''; this.selectedCat = ''; this.selectedSubcat = '';
    this.selectedCountry = ''; this.sort = 'newest'; this.subcats.set([]);
    this.doSearch();
  }

  prev(): void { this.offset.update(o => Math.max(0, o - this.limit)); this.fetch(); }
  next(): void { this.offset.update(o => o + this.limit); this.fetch(); }
}
