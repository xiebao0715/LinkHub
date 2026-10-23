<?php

class Settings
{
    private static array $cache = [];

    public static function get(string $key, $default = null)
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $value = $row ? $row['setting_value'] : $default;
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
        self::$cache[$key] = $value;
    }

    public static function getAll(): array
    {
        $db = Database::getInstance();
        $rows = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    public static function getSiteName(): string
    {
        return self::get('site_name', '链接管理平台');
    }

    public static function getSiteLogo(): string
    {
        return self::get('site_logo', '');
    }

    public static function getDefaultTheme(): string
    {
        return self::get('default_theme', 'light');
    }

    public static function getAdminTheme(): string
    {
        return self::get('admin_theme', 'light');
    }
}
