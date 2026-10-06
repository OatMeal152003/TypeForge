<?php
// TypeForge — device avatar uploads (My Account, users + admins).
// Validates real image bytes, 2 MB cap, random filename. Never trusts client name/type.

class V1_AvatarUpload {
    private const MAX_BYTES = 2097152; // 2 MB
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public static function dir(): string {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
    }

    // Returns relative web path (e.g. uploads/avatars/ab12....png). Throws on invalid upload.
    public static function store(array $file, ?string $old = null): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('No image selected.', 422);
        }
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed. Try a smaller image.', 422);
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image too large (max 2 MB).', 422);
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload.', 400);
        }
        $info = @getimagesize($tmp);
        if ($info === false || !isset(self::ALLOWED[$info['mime']])) {
            throw new InvalidArgumentException('Only JPG, PNG, WEBP or GIF images allowed.', 422);
        }
        $dir = self::dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Avatar storage unavailable.', 500);
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$info['mime']];
        if (!move_uploaded_file($tmp, $dir . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException('Could not save image.', 500);
        }
        self::removeOld($old);
        return 'uploads/avatars/' . $name;
    }

    public static function removeOld(?string $old): void {
        if ($old === null || $old === '' || str_starts_with($old, 'http')) return;
        if (strpos($old, '..') !== false) return;
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $old);
        if (is_file($path) && str_starts_with(realpath($path) ?: '', realpath(self::dir()) ?: '')) {
            @unlink($path);
        }
    }
}
