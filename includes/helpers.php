<?php

function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
}

function config_path(string $path = ''): string
{
    return base_path('config' . ($path ? '/' . ltrim($path, '/') : ''));
}

function is_installed(): bool
{
    if (!file_exists(config_path('database.php'))) {
        return false;
    }
    if (file_exists(config_path('installed.lock'))) {
        return true;
    }
    // 数据库已有完整数据时，尝试自动补写 lock 文件（常见于宝塔权限问题）
    if (install_data_complete()) {
        finalize_installation();
        return file_exists(config_path('installed.lock'));
    }
    return false;
}

function install_data_complete(): bool
{
    if (!file_exists(config_path('database.php'))) {
        return false;
    }
    try {
        require_once __DIR__ . '/Database.php';
        Database::reset();
        $db = Database::getInstance();
        if (!db_table_exists($db, 'settings') || !db_table_exists($db, 'admins')) {
            return false;
        }
        $adminCount = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        $settingCount = (int) $db->query('SELECT COUNT(*) FROM settings')->fetchColumn();
        return $adminCount > 0 && $settingCount > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function db_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function finalize_installation(): bool
{
    $configDir = config_path();
    if (!is_dir($configDir)) {
        @mkdir($configDir, 0755, true);
    }
    foreach (['carousels', 'platforms', 'site'] as $dir) {
        $path = base_path('uploads/' . $dir);
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
    }
    if (file_exists(config_path('installed.lock'))) {
        return true;
    }
    $written = @file_put_contents(config_path('installed.lock'), date('Y-m-d H:i:s'));
    return $written !== false;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function asset_url(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function upload_url(string $path): string
{
    if (empty($path)) {
        return '';
    }
    return '/uploads/' . ltrim($path, '/');
}

function delete_upload_file(?string $relativePath): void
{
    if (empty($relativePath)) {
        return;
    }
    $fullPath = base_path('uploads/' . ltrim($relativePath, '/'));
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function format_number(int $num): string
{
    if ($num >= 10000) {
        return round($num / 10000, 1) . 'w';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return (string) $num;
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', strtotime($datetime));
}

function theme_revision(string $theme): string
{
    return substr(md5($theme), 0, 8);
}

function site_favicon_tag(?string $logo = null): string
{
    if ($logo === null && class_exists('Settings', false)) {
        $logo = Settings::getSiteLogo();
    }
    if (empty($logo)) {
        return '';
    }
    return '<link rel="icon" href="' . e(upload_url($logo)) . '">';
}

function is_valid_link_url(string $url): bool
{
    if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
        return false;
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * 解析批量导入单行：平台、名称、描述、URL（Tab 或逗号分隔，每行一条）
 */
function parse_batch_link_line(string $line): ?array
{
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        return null;
    }

    if (str_contains($line, "\t")) {
        $parts = explode("\t", $line);
    } elseif (preg_match('/,(https?:\/\/[^\s,]+)$/i', $line, $m)) {
        $url = $m[1];
        $rest = substr($line, 0, -strlen($m[0]));
        $parts = array_merge(explode(',', $rest, 2), [$url]);
    } else {
        $parts = explode(',', $line);
    }

    $parts = array_map('trim', $parts);
    $count = count($parts);

    if ($count === 3) {
        return [
            'platform' => $parts[0],
            'title' => $parts[1],
            'description' => '',
            'url' => $parts[2],
        ];
    }

    if ($count >= 4) {
        return [
            'platform' => $parts[0],
            'title' => $parts[1],
            'description' => $parts[2],
            'url' => $parts[3],
        ];
    }

    return ['error' => 'format', 'raw' => $line];
}

function site_logo_html(?string $logo = null, string $class = 'site-logo-img', string $fallbackIcon = 'link'): string
{
    if ($logo === null && class_exists('Settings', false)) {
        $logo = Settings::getSiteLogo();
    }
    if (!empty($logo)) {
        return '<img src="' . e(upload_url($logo)) . '" alt="" class="' . e($class) . '">';
    }
    require_once __DIR__ . '/icons.php';
    return icon($fallbackIcon);
}
