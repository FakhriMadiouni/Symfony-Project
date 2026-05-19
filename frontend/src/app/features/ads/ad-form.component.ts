import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AdService, LocationService, StoreService } from '../../core/services/api.services';
import { AuthService } from '../../core/services/auth.service';
import { AdDetail, Category, Country, StoreToken, Subcategory } from '../../core/models';
import { UploadUrlPipe } from '../../shared/pipes/pipes';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-ad-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, UploadUrlPipe],
  template: `
<div class="container py-4" style="max-width:760px;">
  <div class="d-flex align-items-center gap-3 mb-4">
    <button class="btn btn-sm btn-outline-secondary" (click)="router.navigate(['/ads'])">← Back</button>
    <h2 class="mb-0 fw-bold" style="font-family:'Playfair Display',serif;">
      {{ isEdit ? 'Edit Ad' : 'Post New Ad' }}
    </h2>
  </div>

  @if (error()) { <div class="alert alert-danger">{{ error() }}</div> }
  @if (success()) { <div class="alert alert-success">{{ success() }}</div> }

  <!-- Token selector (only for new ads) -->
  @if (!isEdit) {
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">1. Choose an Ad Package</h5>
        @if (loadingTokens()) {
          <div class="spinner-border spinner-border-sm" style="color:var(--accent);"></div>
        } @else if (tokens().length === 0) {
          <div class="alert alert-warning small">No packages available right now.</div>
        } @else {
          <div class="row g-3">
            @for (t of tokens(); track t.id_store_ad_token) {
              <div class="col-md-4">
                <div class="card h-100 border-2"
                     [style.border-color]="selectedToken === t.id_store_ad_token ? 'var(--accent)' : '#dee2e6'"
                     style="cursor:pointer;transition:.15s;"
                     (click)="selectedToken = t.id_store_ad_token; maxMedia = t.max_media">
                  <div class="card-body p-3">
                    <p class="fw-semibold mb-1">{{ t.name }}</p>
                    <p class="text-muted small mb-2">{{ t.description }}</p>
                    <p class="mb-1 small">📅 {{ t.ad_duration }} days visibility</p>
                    <p class="mb-1 small">🖼 Up to {{ t.max_media }} media files</p>
                    <p class="fw-bold mb-0" style="color:var(--accent);">
                      {{ +t.discount > 0 ? 'Was $' + t.price_per_unit + ' — ' : '' }}
                      {{ getPrice(t) }}
                    </p>
                    @if (t.offer_expiration_date) {
                      <p class="mb-0 small text-danger">Offer ends {{ t.offer_expiration_date | date:'mediumDate' }}</p>
                    }
                  </div>
                </div>
              </div>
            }
          </div>
        }
      </div>
    </div>
  }

  <!-- Main form -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-semibold mb-3">{{ isEdit ? 'Ad Details' : '2. Ad Details' }}</h5>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Title *</label>
        <input class="form-control" [(ngModel)]="title" maxlength="200" placeholder="e.g. iPhone 14 Pro — excellent condition">
        <div class="form-text">{{ title.length }}/200</div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Description</label>
        <textarea class="form-control" [(ngModel)]="description" rows="5"
                  placeholder="Describe your item in detail — condition, dimensions, why you're selling, etc."></textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Price (USD)</label>
          <input type="number" class="form-control" [(ngModel)]="price" min="0" step="0.01" placeholder="0 = Free">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Category</label>
          <select class="form-select" [(ngModel)]="selectedCategory" (change)="onCategoryChange()">
            <option value="">Select…</option>
            @for (c of categories(); track c.category_id) {
              <option [value]="c.category_id">{{ c.name }}</option>
            }
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Subcategory</label>
          <select class="form-select" [(ngModel)]="selectedSubcat">
            <option value="">Select…</option>
            @for (s of subcategories(); track s.subcategory_id) {
              <option [value]="s.subcategory_id">{{ s.name }}</option>
            }
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Country</label>
          <select class="form-select" [(ngModel)]="selectedCountry">
            <option value="">Select…</option>
            @for (c of countries(); track c.country_id) {
              <option [value]="c.country_id">{{ c.name }}</option>
            }
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Region / City (optional)</label>
          <input class="form-control" [(ngModel)]="regionName" placeholder="e.g. London, Ontario">
        </div>
      </div>

      @if (isEdit) {
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" [(ngModel)]="hidden" id="hiddenCheck">
          <label class="form-check-label small" for="hiddenCheck">Hide this ad from public search</label>
        </div>
      }
    </div>
  </div>

  <!-- Media upload (new ads only shown after ad is created, or edit) -->
  @if (isEdit && ad) {
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h5 class="fw-semibold mb-3">Media Files ({{ existingMedia().length }} / {{ maxMedia }})</h5>
        <div class="d-flex gap-2 flex-wrap mb-3">
          @for (m of existingMedia(); track m.media_id) {
            <div class="position-relative" style="width:100px;height:100px;">
              @if (m.file_type === 'img') {
                <img [src]="m.file_name | uploadUrl:'images'" class="w-100 h-100 rounded" style="object-fit:cover;" alt="">
              } @else {
                <div class="w-100 h-100 rounded bg-dark d-flex align-items-center justify-content-center text-white small">▶ Video</div>
              }
              <button class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0"
                      style="width:22px;height:22px;line-height:1;border-radius:50%;"
                      (click)="deleteMedia(m.media_id)">×</button>
            </div>
          }
        </div>
        <input type="file" class="form-control form-control-sm" accept="image/*,video/*"
               multiple (change)="onFileChange($event)" [disabled]="uploadingMedia()">
        @if (uploadingMedia()) {
          <div class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1"></span> Uploading…</div>
        }
      </div>
    </div>
  }

  <div class="d-flex gap-3">
    <button class="btn fw-semibold px-4" style="background:var(--accent);color:#fff;"
            (click)="submit()" [disabled]="saving()">
      @if (saving()) { <span class="spinner-border spinner-border-sm me-2"></span> }
      {{ isEdit ? 'Save Changes' : 'Post Ad' }}
    </button>
    <button class="btn btn-outline-secondary" routerLink="/ads">Cancel</button>
  </div>
</div>
  `,
})
export class AdFormComponent implements OnInit {
  isEdit = false;
  adId   = 0;
  ad: AdDetail | null = null;

  title = ''; description = ''; price = 0; regionName = ''; hidden = false;
  selectedCategory: any = ''; selectedSubcat: any = ''; selectedCountry: any = ''; selectedToken: any = '';
  maxMedia = 5;

  categories   = signal<Category[]>([]);
  subcategories = signal<Subcategory[]>([]);
  countries    = signal<Country[]>([]);
  tokens       = signal<StoreToken[]>([]);
  existingMedia = signal<any[]>([]);

  loadingTokens = signal(false);
  saving        = signal(false);
  uploadingMedia = signal(false);
  error   = signal('');
  success = signal('');

  constructor(
    private route: ActivatedRoute,
    public router: Router,
    private adService: AdService,
    private locService: LocationService,
    private storeService: StoreService,
    public auth: AuthService,
  ) {}

  getPrice(t: any): string {
    const base = parseFloat(t.price_per_unit || '0');
    const disc = parseFloat(t.discount || '0');
    return '$' + (base * (1 - disc / 100)).toFixed(2);
  }

  ngOnInit(): void {
    this.adId  = +(this.route.snapshot.paramMap.get('id') || 0);
    this.isEdit = !!this.adId;

    this.locService.categories().subscribe(r => this.categories.set(r.categories));
    this.locService.countries().subscribe(r => this.countries.set(r.countries));

    if (!this.isEdit) {
      this.loadingTokens.set(true);
      this.storeService.tokens().subscribe({ next: r => { this.tokens.set(r.tokens); this.loadingTokens.set(false); }, error: () => this.loadingTokens.set(false) });
    } else {
      this.adService.get(this.adId).subscribe(r => {
        this.ad = r.ad;
        this.title       = r.ad.title;
        this.description = r.ad.description ?? '';
        this.price       = parseFloat(r.ad.price);
        this.regionName  = r.ad.region_name ?? '';
        this.hidden      = r.ad.hidden_by_advertiser === 1;
        this.selectedCountry = r.ad.country.country_id;
        this.existingMedia.set(r.ad.media);
        this.selectedCategory = r.ad.category.category_id;
        this.locService.subcategories(r.ad.category.category_id).subscribe(s => {
          this.subcategories.set(s.subcategories);
          this.selectedSubcat = r.ad.subcategory.subcategory_id;
        });
      });
    }
  }

  onCategoryChange(): void {
    this.selectedSubcat = '';
    this.subcategories.set([]);
    if (this.selectedCategory) {
      this.locService.subcategories(+this.selectedCategory).subscribe(r => this.subcategories.set(r.subcategories));
    }
  }

  submit(): void {
    this.error.set(''); this.success.set('');

    if (!this.title.trim()) { this.error.set('Title is required.'); return; }
    if (!this.isEdit && !this.selectedToken) { this.error.set('Please choose an ad package.'); return; }
    if (!this.selectedSubcat) { this.error.set('Please select a subcategory.'); return; }
    if (!this.selectedCountry) { this.error.set('Please select a country.'); return; }

    this.saving.set(true);

    if (this.isEdit) {
      this.adService.update(this.adId, {
        title: this.title, description: this.description,
        price: this.price, region_name: this.regionName,
        hidden: this.hidden, subcategory_id: this.selectedSubcat,
        country_id: this.selectedCountry,
      }).subscribe({
        next: () => { this.success.set('Ad updated successfully!'); this.saving.set(false); },
        error: err => { this.error.set(err.error?.error || 'Update failed.'); this.saving.set(false); },
      });
    } else {
      this.adService.create({
        title: this.title, description: this.description,
        price: this.price, region_name: this.regionName,
        subcategory_id: this.selectedSubcat, country_id: this.selectedCountry,
        store_token_id: this.selectedToken,
      }).subscribe({
        next: res => {
          this.saving.set(false);
          this.router.navigate(['/ads', res.ad_id, 'edit'], { queryParams: { new: 1 } });
        },
        error: err => { this.error.set(err.error?.error || 'Failed to post ad.'); this.saving.set(false); },
      });
    }
  }

  onFileChange(event: Event): void {
    const files = (event.target as HTMLInputElement).files;
    if (!files || !this.adId) return;
    this.uploadingMedia.set(true);
    const uploads = Array.from(files);
    let done = 0;
    uploads.forEach(file => {
      this.adService.uploadMedia(this.adId, file).subscribe({
        next: res => {
          this.existingMedia.update(m => [...m, { media_id: res.media_id, file_name: res.file_name, file_type: res.file_type, position: m.length + 1 }]);
          if (++done === uploads.length) this.uploadingMedia.set(false);
        },
        error: err => { this.error.set(err.error?.error || 'Upload failed.'); if (++done === uploads.length) this.uploadingMedia.set(false); },
      });
    });
  }

  deleteMedia(mediaId: number): void {
    if (!confirm('Remove this media file?')) return;
    this.adService.deleteMedia(this.adId, mediaId).subscribe({
      next: () => this.existingMedia.update(m => m.filter(x => x.media_id !== mediaId)),
    });
  }
}
