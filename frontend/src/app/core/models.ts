// ── Auth ──────────────────────────────────────────────────────────────────────
export interface User {
  user_id: number;
  username: string;
  email: string;
  profile_picture: string | null;
  biography: string | null;
  honor_points: number;
  ban_status: number;
  ban_time_left: number;
  ad_ban_status: number;
  ad_ban_time_left: number;
  mute_status: number;
  mute_time_left: number;
  is_staff: boolean;
  staff_division: string | null;
  staff_rank: string | null;
  reg_date: string | null;
  last_login_date: string | null;
}

export interface LoginResponse {
  success: boolean;
  token: string;
  expires_at: string;
  user: User;
}

export interface PublicUser {
  user_id: number;
  username: string;
  profile_picture: string | null;
  biography: string | null;
  honor_points: number;
  honor_rank: { name: string; color: string } | null;
  is_staff: boolean;
  staff_division: string | null;
  staff_rank: string | null;
  reg_date: string | null;
  followers: number;
  following: number;
  reviews: ReviewStats;
  is_following: boolean;
}

// ── Ads ───────────────────────────────────────────────────────────────────────
export interface AdSummary {
  ad_id: number;
  title: string;
  price: string;
  country: { country_id: number; name: string };
  region_name: string | null;
  subcategory: { subcategory_id: number; name: string };
  category: { category_id: number; name: string };
  user: { user_id: number; username: string };
  thumbnail: string | null;
  creation_date: string;
  // from userAds endpoint (profile page)
  active?: number;
  ban_status?: number;
  hidden_by_advertiser?: number;
  time_left?: number;
}

export interface AdMedia {
  media_id: number;
  file_name: string;
  file_type: 'img' | 'vid';
  position: number;
}

export interface AdDetail extends AdSummary {
  description: string | null;
  active: number;
  ban_status: number;
  hidden_by_advertiser: number;
  time_left: number;
  media: AdMedia[];
  user: { user_id: number; username: string; profile_picture: string | null };
}

export interface AdListResponse {
  ads: AdSummary[];
  total: number;
}

// ── Categories / Locations ────────────────────────────────────────────────────
export interface Category {
  category_id: number;
  name: string;
  logo: string | null;
}

export interface Subcategory {
  subcategory_id: number;
  name: string;
  category_id: number;
}

export interface Country {
  country_id: number;
  name: string;
  iso_code: string | null;
}

// ── Tokens ──────────────────────────────────────────────────────────────────────────────────
export interface StoreToken {
  id_store_ad_token: number;
  name: string;
  description: string | null;
  price_per_unit: string;
  discount: string;
  max_media: number;
  ad_duration: number;
  offer_expiration_date: string | null;
}

export interface UserToken {
  user_token_id: number;
  name: string;
  description: string | null;
  max_media: number;
  ad_duration: number;
  creation_date: string | null;
}

// ── Conversations ─────────────────────────────────────────────────────────────
export interface ConversationSummary {
  conversation_id: number;
  ad_id: number;
  ad_title: string;
  other_user: { user_id: number; username: string };
  unread: number;
  last_message_date: string | null;
  lock_status: number;
}

export interface ConversationDetail {
  conversation_id: number;
  ad_id: number;
  ad_title: string;
  ad_active: number;
  ad_ban_status: number;
  ad_hidden: number;
  lock_status: number;
  start_date: string | null;
  is_advertiser: boolean;
  other_user: {
    user_id: number;
    username: string;
    profile_picture: string | null;
    ban_status: number;
    mute_status: number;
  };
  my_mute_status: number;
  unread: number;
}

export interface Message {
  message_id: number;
  sender_id: number;
  sender_name: string;
  content: string;
  type: string;
  timestamp: string;
  read_status: number;
}

// ── Reviews ───────────────────────────────────────────────────────────────────
export interface ReviewStats {
  positive: number;
  negative: number;
  score: number;
}

export interface Review {
  ad_review_id: number;
  rate: 'positive' | 'negative';
  score: number;
  comment: string | null;
  date: string;
  anonymous: boolean;
  rater: { user_id: number; username: string } | null;
}

// ── Notifications ─────────────────────────────────────────────────────────────
export interface Notification {
  notification_id: number;
  category: string;
  reference_type: string;
  reference_id: number | null;
  content: string;
  read_status: number;
  date: string;
}

export interface NotificationGroups {
  advertisements?: Notification[];
  conversations?: Notification[];
  tokens?: Notification[];
  social?: Notification[];
  honor?: Notification[];
  system?: Notification[];
}

// ── Support ───────────────────────────────────────────────────────────────────
export interface SupportTicket {
  support_conv_id: number;
  subject: string;
  status: string;
  opened_date: string | null;
  last_reply_date: string | null;
}

export interface SupportMessage {
  support_msg_id: number;
  is_staff: number;
  content: string;
  sent_date: string;
}

// ── Admin ─────────────────────────────────────────────────────────────────────
export interface Report {
  report_id: number;
  type: string;
  reason: string | null;
  reporter: { user_id: number; username: string };
  reported_user: { user_id: number; username: string };
  date: string;
}

export interface AdminSupportTicket extends SupportTicket {
  user: { user_id: number; username: string };
}

// ── API generic ───────────────────────────────────────────────────────────────
export interface ApiSuccess {
  success: boolean;
  message?: string;
}

export interface ApiError {
  error: string;
}

export interface PaginationParams {
  limit?: number;
  offset?: number;
}
