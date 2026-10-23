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
</div>
