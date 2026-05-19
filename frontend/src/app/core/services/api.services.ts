import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  AdDetail, AdListResponse, AdSummary, AdminSupportTicket, ApiSuccess,
  Category, ConversationDetail, Country, Message, ConversationSummary,
  NotificationGroups, PublicUser, Report, Review, ReviewStats, StoreToken,
  Subcategory, SupportMessage, SupportTicket, UserToken
} from '../models';

const API = environment.apiUrl;

// ── Ads ───────────────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class AdService {
  constructor(private http: HttpClient) {}

  list(params: {
    subcategory?: number; country?: number; search?: string;
    sort?: string; limit?: number; offset?: number; followed_only?: boolean;
  } = {}): Observable<AdListResponse> {
    let p = new HttpParams();
    Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '' && v !== false) p = p.set(k, String(v)); });
    return this.http.get<AdListResponse>(`${API}/ads`, { params: p });
  }

  get(id: number): Observable<{ ad: AdDetail }> {
    return this.http.get<{ ad: AdDetail }>(`${API}/ads/${id}`);
  }

  create(data: object): Observable<{ ad_id: number }> {
    return this.http.post<{ ad_id: number }>(`${API}/ads`, data);
  }

  update(id: number, data: object): Observable<{ ad: AdDetail }> {
    return this.http.patch<{ ad: AdDetail }>(`${API}/ads/${id}`, data);
  }

  delete(id: number): Observable<ApiSuccess> {
    return this.http.delete<ApiSuccess>(`${API}/ads/${id}`);
  }

  uploadMedia(adId: number, file: File): Observable<any> {
    const fd = new FormData();
    fd.append('file', file);
    return this.http.post(`${API}/ads/${adId}/media`, fd);
  }

  deleteMedia(adId: number, mediaId: number): Observable<ApiSuccess> {
    return this.http.delete<ApiSuccess>(`${API}/ads/${adId}/media/${mediaId}`);
  }

  userAds(userId: number, limit = 20, offset = 0): Observable<{ ads: AdSummary[] }> {
    return this.http.get<{ ads: AdSummary[] }>(`${API}/users/${userId}/ads`, {
      params: new HttpParams().set('limit', limit).set('offset', offset)
    });
  }
}

// ── Locations / Categories ────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class LocationService {
  constructor(private http: HttpClient) {}
  countries(): Observable<{ countries: Country[] }> { return this.http.get<any>(`${API}/locations`); }
  categories(): Observable<{ categories: Category[] }> { return this.http.get<any>(`${API}/categories`); }
  subcategories(catId: number): Observable<{ subcategories: Subcategory[] }> {
    return this.http.get<any>(`${API}/categories`, { params: new HttpParams().set('category_id', catId) });
  }
}

// ── Store tokens ──────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class StoreService {
  constructor(private http: HttpClient) {}
  tokens(): Observable<{ tokens: StoreToken[] }> { return this.http.get<any>(`${API}/store/tokens`); }
  buy(storeTokenId: number, quantity = 1): Observable<any> {
    return this.http.post<any>(`${API}/store/buy`, { store_token_id: storeTokenId, quantity });
  }
  purchaseBulk(items: { offer_id: number; qty: number }[]): Observable<any> {
    return this.http.post<any>(`${API}/store/purchase`, { items });
  }
  myTokens(): Observable<{ tokens: UserToken[] }> { return this.http.get<any>(`${API}/store/my-tokens`); }
}

// ── User / Profile ────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class UserService {
  constructor(private http: HttpClient) {}

  getProfile(id: number): Observable<{ user: PublicUser }> {
    return this.http.get<{ user: PublicUser }>(`${API}/users/${id}`);
  }

  updateProfile(data: { username?: string; biography?: string }): Observable<any> {
    return this.http.patch(`${API}/users/me/update`, data);
  }

  uploadAvatar(file: File): Observable<any> {
    const fd = new FormData();
    fd.append('avatar', file);
    return this.http.post(`${API}/users/me/avatar`, fd);
  }

  deleteAvatar(): Observable<any> { return this.http.delete(`${API}/users/me/avatar`); }

  follow(userId: number): Observable<any> { return this.http.post(`${API}/users/${userId}/follow`, {}); }
  unfollow(userId: number): Observable<any> { return this.http.delete(`${API}/users/${userId}/follow`); }

  readvertise(adId: number, userTokenId: number): Observable<any> {
    return this.http.post(`${API}/users/${adId}/readvertise`, { user_token_id: userTokenId });
  }

  reviews(userId: number, limit = 20, offset = 0): Observable<{ reviews: Review[]; stats: ReviewStats }> {
    return this.http.get<any>(`${API}/users/${userId}/reviews`, {
      params: new HttpParams().set('limit', limit).set('offset', offset)
    });
  }
}

// ── Conversations ─────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class ConversationService {
  constructor(private http: HttpClient) {}

  list(): Observable<{ conversations: ConversationSummary[] }> {
    return this.http.get<any>(`${API}/conversations`);
  }

  get(id: number): Observable<{ conversation: ConversationDetail }> {
    return this.http.get<any>(`${API}/conversations/${id}`);
  }

  start(adId: number, message?: string): Observable<{ conversation_id: number }> {
    return this.http.post<any>(`${API}/conversations`, { ad_id: adId, message });
  }

  messages(convId: number, limit = 50, offset = 0): Observable<{ messages: Message[] }> {
    return this.http.get<any>(`${API}/conversations/${convId}/messages`, {
      params: new HttpParams().set('limit', limit).set('offset', offset)
    });
  }

  send(convId: number, content: string): Observable<{ message: Message }> {
    return this.http.post<any>(`${API}/conversations/${convId}/messages`, { content });
  }
}

// ── Reviews ───────────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class ReviewService {
  constructor(private http: HttpClient) {}

  create(data: { conversation_id: number; rate: string; comment?: string; anonymous?: boolean }): Observable<any> {
    return this.http.post(`${API}/reviews`, data);
  }

  check(convId: number): Observable<{ reviewed: boolean }> {
    return this.http.get<any>(`${API}/reviews/check`, { params: new HttpParams().set('conv_id', convId) });
  }
}

// ── Notifications ─────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class NotificationService {
  constructor(private http: HttpClient) {}

  getAll(): Observable<{ notifications: NotificationGroups }> {
    return this.http.get<any>(`${API}/notifications`);
  }

  unreadCount(): Observable<{ count: number }> {
    return this.http.get<any>(`${API}/notifications/unread-count`);
  }

  markRead(id: number): Observable<any> {
    return this.http.post(`${API}/notifications/${id}/read`, {});
  }

  markAllRead(): Observable<any> {
    return this.http.post(`${API}/notifications/read-all`, {});
  }
}

// ── Reports ───────────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class ReportService {
  constructor(private http: HttpClient) {}

  submit(data: { type: string; reported_user_id: number; reference_id?: number; reason?: string }): Observable<any> {
    return this.http.post(`${API}/reports`, data);
  }
}

// ── Support ───────────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class SupportService {
  constructor(private http: HttpClient) {}

  list(): Observable<{ conversations: SupportTicket[] }> { return this.http.get<any>(`${API}/support`); }
  open(subject: string, message: string): Observable<{ support_conv_id: number }> {
    return this.http.post<any>(`${API}/support`, { subject, message });
  }
  get(id: number): Observable<{ conversation: SupportTicket; messages: SupportMessage[] }> {
    return this.http.get<any>(`${API}/support/${id}`);
  }
  reply(id: number, content: string): Observable<any> {
    return this.http.post(`${API}/support/${id}/reply`, { content });
  }
}

// ── Admin ─────────────────────────────────────────────────────────────────────
@Injectable({ providedIn: 'root' })
export class AdminService {
  constructor(private http: HttpClient) {}

  // Users
  warnUser(id: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/warn`, { reason }); }
  unwarnUser(id: number): Observable<any> { return this.http.post(`${API}/admin/users/${id}/unwarn`, {}); }
  banUser(id: number, minutes: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/ban`, { minutes, reason }); }
  unbanUser(id: number): Observable<any> { return this.http.post(`${API}/admin/users/${id}/unban`, {}); }
  muteWarnUser(id: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/mute-warn`, { reason }); }
  muteUnwarnUser(id: number): Observable<any> { return this.http.post(`${API}/admin/users/${id}/mute-unwarn`, {}); }
  muteUser(id: number, minutes: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/mute`, { minutes, reason }); }
  unmuteUser(id: number): Observable<any> { return this.http.post(`${API}/admin/users/${id}/unmute`, {}); }
  adWarnUser(id: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/ad-warn`, { reason }); }
  adBanUser(id: number, minutes: number, reason: string): Observable<any> { return this.http.post(`${API}/admin/users/${id}/ad-ban`, { minutes, reason }); }
  adUnbanUser(id: number): Observable<any> { return this.http.post(`${API}/admin/users/${id}/ad-unban`, {}); }

  // Ads
  banAd(id: number): Observable<any> { return this.http.post(`${API}/admin/ads/${id}/ban`, {}); }
  unbanAd(id: number): Observable<any> { return this.http.post(`${API}/admin/ads/${id}/unban`, {}); }

  // Reports
  getReports(limit = 20, offset = 0): Observable<{ reports: Report[] }> {
    return this.http.get<any>(`${API}/admin/reports`, { params: new HttpParams().set('limit', limit).set('offset', offset) });
  }
  closeReport(id: number, action: 'resolve' | 'reject', details?: string): Observable<any> {
    return this.http.post(`${API}/admin/reports/${id}/close`, { action, details });
  }

  // Support
  getSupportTickets(limit = 20, offset = 0): Observable<{ conversations: AdminSupportTicket[] }> {
    return this.http.get<any>(`${API}/admin/support`, { params: new HttpParams().set('limit', limit).set('offset', offset) });
  }
  supportReply(id: number, content: string): Observable<any> { return this.http.post(`${API}/admin/support/${id}/reply`, { content }); }
  supportClose(id: number): Observable<any> { return this.http.post(`${API}/admin/support/${id}/close`, {}); }
}
