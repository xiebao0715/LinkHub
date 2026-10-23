<?php
$pageTitle = '仪表盘';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

$totalVisits = Stats::getTotalVisits();
$linkCount = (int) $db->query('SELECT COUNT(*) FROM links')->fetchColumn();
$platformCount = (int) $db->query('SELECT COUNT(*) FROM platforms')->fetchColumn();
$carouselClicks = Stats::getTotalCarouselClicks();
$linkClicks = Stats::getTotalLinkClicks();
$hotSearches = Stats::getHotSearches(20);

// 近7天访问趋势
$visitTrend = $db->query(
    "SELECT visit_date, visit_count FROM visit_stats
     WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     ORDER BY visit_date ASC"
)->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary"><?= icon('eye') ?></div>
        <div class="stat-info">
            <span class="stat-value"><?= format_number($totalVisits) ?></span>
            <span class="stat-label">站点访问量</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-accent"><?= icon('link') ?></div>
        <div class="stat-info">
            <span class="stat-value"><?= format_number($linkCount) ?></span>
            <span class="stat-label">链接数量</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-info"><?= icon('cubes') ?></div>
        <div class="stat-info">
            <span class="stat-value"><?= format_number($platformCount) ?></span>
            <span class="stat-label">平台数量</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-purple"><?= icon('hand-pointer') ?></div>
        <div class="stat-info">
            <span class="stat-value"><?= format_number($carouselClicks) ?></span>
            <span class="stat-label">轮播图点击量</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-success"><?= icon('mouse-pointer') ?></div>
        <div class="stat-info">
            <span class="stat-value"><?= format_number($linkClicks) ?></span>
            <span class="stat-label">链接总点击量</span>
        </div>
    </div>
</div>

<div class="panel-grid">
    <div class="panel">
        <div class="panel-header">
            <h3>近7天访问趋势</h3>
        </div>
        <div class="panel-body">
            <?php if (empty($visitTrend)): ?>
                <p class="empty-text">暂无访问数据</p>
            <?php else: ?>
                <div class="chart-bars">
                    <?php
                    $maxCount = max(array_column($visitTrend, 'visit_count')) ?: 1;
                    foreach ($visitTrend as $day):
                        $height = ($day['visit_count'] / $maxCount) * 100;
                    ?>
                    <div class="chart-bar-item">
                        <div class="chart-bar" style="height:<?= max($height, 4) ?>%"></div>
                        <span class="chart-label"><?= date('m/d', strtotime($day['visit_date'])) ?></span>
                        <span class="chart-value"><?= $day['visit_count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>最热门搜索词 TOP 20</h3>
        </div>
        <div class="panel-body">
            <?php if (empty($hotSearches)): ?>
                <p class="empty-text">暂无搜索数据</p>
            <?php else: ?>
                <div class="hot-list">
                    <?php foreach ($hotSearches as $i => $hs): ?>
                    <div class="hot-item">
                        <span class="hot-rank<?= $i < 3 ? ' top' : '' ?>"><?= $i + 1 ?></span>
                        <span class="hot-keyword"><?= e($hs['keyword']) ?></span>
                        <span class="hot-count"><?= $hs['count'] ?> 次</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
