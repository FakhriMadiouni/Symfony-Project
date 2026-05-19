<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadService
{
    private const ALLOWED_IMG = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VID = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

    public function __construct(
        private readonly string $avatarDir,
        private readonly string $mediaImgDir,
        private readonly string $mediaVidDir,
        private readonly int    $avatarMaxSize,
        private readonly int    $mediaImgMaxSize,
        private readonly int    $mediaVidMaxSize
    ) {}

    public function uploadAvatar(UploadedFile $file, ?string $oldFileName = null): array
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_IMG, true)) {
            return ['error' => 'Avatar must be a JPEG, PNG, GIF or WebP image.'];
        }
        if ($file->getSize() > $this->avatarMaxSize) {
            return ['error' => 'Avatar must be under ' . ($this->avatarMaxSize / 1048576) . ' MB.'];
        }

        $this->ensureDir($this->avatarDir);

        if ($oldFileName) {
            $this->deleteFile($this->avatarDir . '/' . $oldFileName);
        }

        $fileName = $this->uniqueName($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($this->avatarDir, $fileName);

        return ['success' => true, 'file_name' => $fileName];
    }

    public function uploadAdMedia(UploadedFile $file): array
    {
        $mime = $file->getMimeType();
        $isImg = in_array($mime, self::ALLOWED_IMG, true);
        $isVid = in_array($mime, self::ALLOWED_VID, true);

        if (!$isImg && !$isVid) {
            return ['error' => 'Unsupported file type.'];
        }

        if ($isImg && $file->getSize() > $this->mediaImgMaxSize) {
            return ['error' => 'Image must be under ' . ($this->mediaImgMaxSize / 1048576) . ' MB.'];
        }
        if ($isVid && $file->getSize() > $this->mediaVidMaxSize) {
            return ['error' => 'Video must be under ' . ($this->mediaVidMaxSize / 1048576) . ' MB.'];
        }

        $type = $isImg ? 'img' : 'vid';
        $dir  = $isImg ? $this->mediaImgDir : $this->mediaVidDir;
        $this->ensureDir($dir);

        $ext      = $file->getClientOriginalExtension() ?: ($isImg ? 'jpg' : 'mp4');
        $fileName = $this->uniqueName($ext);
        $file->move($dir, $fileName);

        return ['success' => true, 'file_name' => $fileName, 'file_type' => $type];
    }

    public function deleteAdMedia(string $fileName, string $fileType): void
    {
        $dir = $fileType === 'img' ? $this->mediaImgDir : $this->mediaVidDir;
        $this->deleteFile($dir . '/' . $fileName);
    }

    public function deleteAvatar(string $fileName): void
    {
        $this->deleteFile($this->avatarDir . '/' . $fileName);
    }

    private function uniqueName(string $ext): string
    {
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . ltrim($ext, '.');
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function deleteFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
