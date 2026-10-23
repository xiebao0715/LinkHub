<?php

class Upload
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE = 5 * 1024 * 1024;

    public static function image(array $file, string $subdir): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            throw new RuntimeException('文件上传失败');
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('文件大小不能超过 5MB');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('非法上传文件');
        }

        $mime = self::detectImageMime($file['tmp_name']);
        if ($mime === null || !in_array($mime, self::ALLOWED_TYPES, true)) {
            throw new RuntimeException('仅支持 JPG、PNG、GIF、WEBP 格式');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $dir = base_path('uploads/' . trim($subdir, '/'));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('', true) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('文件保存失败');
        }

        return trim($subdir, '/') . '/' . $filename;
    }

    private static function detectImageMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        $info = @getimagesize($path);
        if ($info !== false && !empty($info['mime'])) {
            return $info['mime'];
        }

        return null;
    }
}
