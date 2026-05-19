import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AdService, LocationService } from '../../core/services/api.services';
import { AdSummary, Category, Country, Subcategory } from '../../core/models';
import { UploadUrlPipe, TruncatePipe } from '../../shared/pipes/pipes';

@Component({
  selector: 'app-ad-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, UploadUrlPipe, TruncatePipe],
  template: `
<div class="container-xl py-4">
  <!-- Search bar -->
  <div class="row g-2 mb-4 align-items-end">
    <div class="col-md-4">
      <input class="form-control" [(ngModel)]="search" placeholder="Search ads…" (keyup.enter)="applyFilters()">
    </div>
    <div class="col-md-2">
      <select class="form-select" [(ngModel)]="selectedCategory" (change)="onCategoryChange()">
        <option value="">All categories</option>
        @for (c of categories(); track c.category_id) {
          <option [value]="c.category_id">{{ c.name }}</option>
        }
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" [(ngModel)]="selectedSubcat">
        <option value="">All subcategories</option>
        @for (s of subcategories(); track s.subcategory_id) {
          <option [value]="s.subcategory_id">{{ s.name }}</option>
        }
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" [(ngModel)]="selectedCountry">
        <option value="">All countries</option>
        @for (c of countries(); track c.country_id) {
          <option [value]="c.country_id">{{ c.name }}</option>
        }
      </select>
    </div>
    <div class="col-md-1">
      <select class="form-select" [(ngModel)]="sort">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="price_asc">Price ↑</option>
        <option value="price_desc">Price ↓</option>
      </select>
    </div>
    <div class="col-md-1">
      <button class="btn w-100 fw-semibold" style="background:var(--accent);color:#fff;" (click)="applyFilters()">
        Search
      </button>
    </div>
  </div>

  <!-- Results header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">{{ total() }} ads found</span>
    @if (loading()) { <div class="spinner-border spinner-border-sm" style="color:var(--accent);"></div> }
  </div>

  <!-- Ad grid -->
  @if (!loading() && ads().length === 0) {
    <div class="text-center py-5 text-muted">
      <div style="font-size:3rem;">🔍</div>
      <p class="mt-2">No ads found. Try different filters.</p>
    </div>
  }

  <div class="row g-3">
    @for (ad of ads(); track ad.ad_id) {
      <div class="col-sm-6 col-md-4 col-lg-3">
        <a [routerLink]="['/ads', ad.ad_id]" class="text-decoration-none">
          <div class="card h-100 border-0 shadow-sm ad-card">
            <div style="height:180px;overflow:hidden;background:#f8f8f8;">
              <img [src]="ad.thumbnail | uploadUrl:'images'"
                   class="w-100 h-100" style="object-fit:cover;" alt="{{ ad.title }}">
            </div>
            <div class="card-body p-3">
              <p class="mb-1 fw-semibold small" style="color:var(--text);">{{ ad.title | truncate:60 }}</p>
              <p class="mb-1" style="color:var(--accent);font-weight:700;font-size:1rem;">
                {{ ad.price == '0.00' ? 'Free' : (ad.price | currency:'USD':'symbol':'1.2-2') }}
              </p>
              <p class="mb-0 small text-muted">
                📍 {{ ad.country.name }}{{ ad.region_name ? ', ' + ad.region_name : '' }}
              </p>
              <p class="mb-0 small text-muted">{{ ad.subcategory.name }}</p>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-2 px-3">
              <small class="text-muted">by {{ ad.user.username }}</small>
            </div>
          </div>
        </a>
      </div>
    }
  </div>

  <!-- Pagination -->
  @if (total() > pageSize) {
    <div class="d-flex justify-content-center gap-2 mt-4">
      <button class="btn btn-sm btn-outline-secondary" [disabled]="currentPage === 0" (click)="prevPage()">← Prev</button>
      <span class="btn btn-sm disabled" style="background:var(--accent);color:#fff;">
        Page {{ currentPage + 1 }} / {{ totalPages() }}
      </span>
      <button class="btn btn-sm btn-outline-secondary" [disabled]="currentPage >= totalPages() - 1" (click)="nextPage()">Next →</button>
    </div>
  }
</div>
  `,
  styles: [`
    .ad-card { transition: transform .15s, box-shadow .15s; cursor:pointer; }
    .ad-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.12)!important; }
  `]
})
export class AdListComponent implements OnInit {
  ads        = signal<AdSummary[]>([]);
  total      = signal(0);
  categories = signal<Category[]>([]);
  subcategories = signal<Subcategory[]>([]);
  countries  = signal<Country[]>([]);
  loading    = signal(false);

  search = ''; selectedCategory: any = ''; selectedSubcat: any = '';
  selectedCountry: any = ''; sort = 'newest';
  currentPage = 0; pageSize = 20;

  totalPages = () => Math.ceil(this.total() / this.pageSize);

  constructor(private adService: AdService, private locService: LocationService) {}

  ngOnInit(): void {
    this.locService.categories().subscribe(r => this.categories.set(r.categories));
    this.locService.countries().subscribe(r => this.countries.set(r.countries));
    this.load();
  }

  onCategoryChange(): void {
    this.selectedSubcat = '';
    this.subcategories.set([]);
    if (this.selectedCategory) {
      this.locService.subcategories(+this.selectedCategory).subscribe(r => this.subcategories.set(r.subcategories));
    }
  }

  applyFilters(): void { this.currentPage = 0; this.load(); }
  prevPage(): void { this.currentPage--; this.load(); window.scrollTo(0,0); }
  nextPage(): void { this.currentPage++; this.load(); window.scrollTo(0,0); }

  private load(): void {
    this.loading.set(true);
    this.adService.list({
      search: this.search || undefined,
      subcategory: this.selectedSubcat || undefined,
      country: this.selectedCountry || undefined,
      sort: this.sort,
      limit: this.pageSize,
      offset: this.currentPage * this.pageSize,
    }).subscribe({
      next: r => { this.ads.set(r.ads); this.total.set(r.total); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
}
