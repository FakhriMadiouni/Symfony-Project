import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { StoreService } from '../../core/services/api.services';
import { StoreToken } from '../../core/models';

@Component({
  selector: 'app-store',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
<div class="container">
  <div style="margin-bottom:2rem;">
    <h1>Token Store</h1>
    <p style="color:var(--muted);">Purchase tokens to publish your advertisements on the platform.</p>
  </div>

  @if (error()) { <div class="alert alert-error">{{ error() }}</div> }
  @if (success()) { <div class="alert alert-success">{{ success() }}</div> }

  @if (loading()) {
    <div style="text-align:center;padding:4rem;">
      <div class="spinner"></div>
    </div>
  } @else if (tokens().length === 0) {
    <div class="panel" style="text-align:center;padding:3rem;">
      <p style="color:var(--muted);">No token offers available at the moment. Check back soon!</p>
    </div>
  } @else {

    <!-- LIMITED-TIME OFFERS -->
    @if (limited().length > 0) {
      <div style="margin-bottom:2rem;">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
          <span style="font-size:1.05rem;font-weight:700;">⏳ Limited-Time Offers</span>
          <span style="font-size:.78rem;background:rgba(255,160,0,.15);color:#ffa000;border:1px solid rgba(255,160,0,.3);border-radius:20px;padding:.2rem .65rem;">Expires soon</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;">
          @for (offer of limited(); track offer.id_store_ad_token) {
            <div class="panel" style="display:flex;flex-direction:column;gap:.9rem;border-color:rgba(255,160,0,.25);">
              <div>
                <h2 style="font-size:1.05rem;">{{ offer.name }}</h2>
                @if (offer.description) {
                  <p style="color:var(--muted);font-size:.83rem;margin-top:.2rem;">{{ offer.description }}</p>
                }
              </div>
              <ul style="list-style:none;font-size:.87rem;display:flex;flex-direction:column;gap:.35rem;">
                <li>⏱ <strong>{{ offer.ad_duration }}</strong> day(s) active</li>
                <li>🖼 Up to <strong>{{ offer.max_media }}</strong> media files</li>
                @if (+offer.discount > 0) {
                  <li style="color:var(--success);">🏷 {{ offer.discount }}% discount applied</li>
                }
                <li style="color:#ffa000;font-size:.8rem;">🗓 Expires {{ offer.offer_expiration_date | date:'d MMM y' }}</li>
              </ul>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
                <div>
                  <span style="font-size:1.25rem;font-weight:700;color:var(--accent);">
                    {{ finalPrice(offer) | number:'1.2-2' }} USD
                  </span>
                  @if (+offer.discount > 0) {
                    <span style="font-size:.78rem;color:var(--muted);text-decoration:line-through;margin-left:.4rem;">
                      {{ offer.price_per_unit | number:'1.2-2' }} USD
                    </span>
                  }
                  <span style="font-size:.75rem;color:var(--muted);"> / token</span>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <label style="font-size:.82rem;color:var(--muted);flex-shrink:0;">Qty:</label>
                <input type="number"
                       [value]="getQty(offer.id_store_ad_token)"
                       (input)="setQty(offer.id_store_ad_token, +$any($event.target).value)"
                       min="0" max="99">
              </div>
            </div>
          }
        </div>
      </div>
    }

    <!-- STANDARD OFFERS -->
    @if (permanent().length > 0) {
      <div style="margin-bottom:2rem;">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
          <span style="font-size:1.05rem;font-weight:700;">🪙 Standard Offers</span>
          <span style="font-size:.78rem;background:rgba(128,128,128,.1);color:var(--muted);border:1px solid var(--border);border-radius:20px;padding:.2rem .65rem;">Always available</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;">
          @for (offer of permanent(); track offer.id_store_ad_token) {
            <div class="panel" style="display:flex;flex-direction:column;gap:.9rem;">
              <div>
                <h2 style="font-size:1.05rem;">{{ offer.name }}</h2>
                @if (offer.description) {
                  <p style="color:var(--muted);font-size:.83rem;margin-top:.2rem;">{{ offer.description }}</p>
                }
              </div>
              <ul style="list-style:none;font-size:.87rem;display:flex;flex-direction:column;gap:.35rem;">
                <li>⏱ <strong>{{ offer.ad_duration }}</strong> day(s) active</li>
                <li>🖼 Up to <strong>{{ offer.max_media }}</strong> media files</li>
                @if (+offer.discount > 0) {
                  <li style="color:var(--success);">🏷 {{ offer.discount }}% discount applied</li>
                }
              </ul>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
                <div>
                  <span style="font-size:1.25rem;font-weight:700;color:var(--accent);">
                    {{ finalPrice(offer) | number:'1.2-2' }} USD
                  </span>
                  @if (+offer.discount > 0) {
                    <span style="font-size:.78rem;color:var(--muted);text-decoration:line-through;margin-left:.4rem;">
                      {{ offer.price_per_unit | number:'1.2-2' }} USD
                    </span>
                  }
                  <span style="font-size:.75rem;color:var(--muted);"> / token</span>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <label style="font-size:.82rem;color:var(--muted);flex-shrink:0;">Qty:</label>
                <input type="number"
                       [value]="getQty(offer.id_store_ad_token)"
                       (input)="setQty(offer.id_store_ad_token, +$any($event.target).value)"
                       min="0" max="99">
              </div>
            </div>
          }
        </div>
      </div>
    }

    <!-- Confirm purchase -->
    <div style="display:flex;justify-content:flex-end;margin-top:.5rem;">
      <button class="btn btn-primary" style="padding:.75rem 2rem;font-size:1rem;"
              (click)="purchase()" [disabled]="purchasing()">
        @if (purchasing()) { <span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> }
        ✅ Confirm Purchase
      </button>
    </div>
  }
</div>
  `,
})
export class StoreComponent implements OnInit {
  tokens    = signal<StoreToken[]>([]);
  limited   = signal<StoreToken[]>([]);
  permanent = signal<StoreToken[]>([]);
  loading   = signal(true);
  purchasing = signal(false);
  error     = signal('');
  success   = signal('');

  private quantities: Map<number, number> = new Map();

  constructor(private storeService: StoreService) {}

  ngOnInit(): void {
    this.storeService.tokens().subscribe({
      next: res => {
        this.tokens.set(res.tokens);
        this.limited.set(res.tokens.filter(t => !!t.offer_expiration_date));
        this.permanent.set(res.tokens.filter(t => !t.offer_expiration_date));
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  finalPrice(offer: StoreToken): number {
    return +offer.price_per_unit * (1 - +offer.discount / 100);
  }

  getQty(id: number): number { return this.quantities.get(id) ?? 0; }
  setQty(id: number, qty: number): void { this.quantities.set(id, Math.max(0, qty)); }

  purchase(): void {
    const items: { offer_id: number; qty: number }[] = [];
    this.quantities.forEach((qty, id) => { if (qty > 0) items.push({ offer_id: id, qty }); });
    if (!items.length) { this.error.set('Please enter a quantity of at least 1 for one or more offers.'); return; }

    this.error.set(''); this.success.set(''); this.purchasing.set(true);
    this.storeService.purchaseBulk(items).subscribe({
      next: res => {
        this.success.set(res.message ?? 'Purchase successful!');
        this.quantities.clear();
        this.purchasing.set(false);
      },
      error: err => { this.error.set(err.error?.error || 'Purchase failed.'); this.purchasing.set(false); },
    });
  }
}
