<?php

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            return true;
        }
        return false;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/admin/login.php');
        }
    }
}
