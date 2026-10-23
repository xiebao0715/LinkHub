<?php

session_start();

require_once __DIR__ . '/helpers.php';

if (!is_installed() && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
    redirect('/install/');
}

require_once __DIR__ . '/Database.php';

if (is_installed()) {
    require_once __DIR__ . '/Settings.php';
    require_once __DIR__ . '/Auth.php';
    require_once __DIR__ . '/Upload.php';
    require_once __DIR__ . '/Stats.php';
    require_once __DIR__ . '/../install/schema.php';
    ensure_database_migrations(Database::getInstance());
}
