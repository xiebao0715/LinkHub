<?php

function run_install_schema(PDO $pdo): void
{
    $schemaFile = __DIR__ . '/schema.sql';
    if (!is_readable($schemaFile)) {
        throw new RuntimeException('找不到数据库结构文件 schema.sql');
    }

    $lines = file($schemaFile, FILE_IGNORE_NEW_LINES);
    $sql = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '--')) {
            continue;
        }
        $sql .= $line . ' ';
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function ensure_install_schema(PDO $pdo): void
{
    if (!db_table_exists($pdo, 'settings')) {
        run_install_schema($pdo);
    }
    ensure_database_migrations($pdo);
}

function ensure_database_migrations(PDO $pdo): void
{
    if (!db_table_exists($pdo, 'links')) {
        return;
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM links LIKE 'clicks'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE links ADD COLUMN clicks INT NOT NULL DEFAULT 0 AFTER description');
    }

    $newSettings = [
        'recommended_keywords' => '',
        'hot_keywords' => '',
        'announcement_enabled' => '0',
        'announcement_title' => '',
        'announcement_content' => '',
    ];
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_key = setting_key'
    );
    foreach ($newSettings as $key => $value) {
        $check = $pdo->prepare('SELECT id FROM settings WHERE setting_key = ?');
        $check->execute([$key]);
        if (!$check->fetch()) {
            $stmt->execute([$key, $value]);
        }
    }
}
