import { Pipe, PipeTransform } from '@angular/core';
import { environment } from '../../../environments/environment';

@Pipe({ name: 'uploadUrl', standalone: true })
export class UploadUrlPipe implements PipeTransform {
  transform(fileName: string | null | undefined, type: 'avatars' | 'images' | 'videos' = 'images'): string {
    if (!fileName) return type === 'avatars' ? '/assets/default_avatar.svg' : '/assets/no_image.svg';
    return `${environment.uploadsUrl}/${type}/${fileName}`;
  }
}

@Pipe({ name: 'timeAgo', standalone: true })
export class TimeAgoPipe implements PipeTransform {
  transform(dateStr: string | null | undefined): string {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const s = Math.floor(diff / 1000);
    if (s < 60)   return `${s}s ago`;
    if (s < 3600) return `${Math.floor(s / 60)}m ago`;
    if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
    if (s < 2592000) return `${Math.floor(s / 86400)}d ago`;
    return new Date(dateStr).toLocaleDateString();
  }
}

@Pipe({ name: 'truncate', standalone: true })
export class TruncatePipe implements PipeTransform {
  transform(value: string | null | undefined, limit = 100): string {
    if (!value) return '';
    return value.length > limit ? value.slice(0, limit) + '…' : value;
  }
}
