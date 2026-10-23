<?php

class Stats
{
    public static function recordVisit(): void
    {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $stmt = $db->prepare(
            'INSERT INTO visit_stats (visit_date, visit_count) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE visit_count = visit_count + 1'
        );
        $stmt->execute([$today]);
        Settings::set('total_visits', (int) Settings::get('total_visits', 0) + 1);
    }

    public static function recordSearch(string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO search_logs (keyword) VALUES (?)');
        $stmt->execute([$keyword]);
    }

    public static function recordCarouselClick(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE carousels SET clicks = clicks + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function recordLinkClick(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('UPDATE links SET clicks = clicks + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function getTotalVisits(): int
    {
        return (int) Settings::get('total_visits', 0);
    }

    public static function getHotSearches(int $limit = 20): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT keyword, COUNT(*) as count FROM search_logs
             GROUP BY keyword ORDER BY count DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTotalCarouselClicks(): int
    {
        $db = Database::getInstance();
        return (int) $db->query('SELECT COALESCE(SUM(clicks), 0) FROM carousels')->fetchColumn();
    }

    public static function getTotalLinkClicks(): int
    {
        $db = Database::getInstance();
        if (!db_table_exists($db, 'links')) {
            return 0;
        }
        return (int) $db->query('SELECT COALESCE(SUM(clicks), 0) FROM links')->fetchColumn();
    }
}
