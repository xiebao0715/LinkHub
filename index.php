<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/icons.php';

$db = Database::getInstance();

// 记录访问
Stats::recordVisit();

$siteName = Settings::getSiteName();
$siteLogo = Settings::getSiteLogo();
$icp = Settings::get('icp_number', '');
$copyright = Settings::get('copyright', '');
$defaultTheme = Settings::getDefaultTheme();

// 轮播图
$carousels = $db->query(
    'SELECT * FROM carousels WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();

// 平台列表（搜索范围）
$platforms = $db->query(
    'SELECT id, name, logo FROM platforms ORDER BY sort_order ASC, id ASC'
)->fetchAll();

// 热门资源（分页，每页 10 条）
$hotPerPage = 10;
$hotPage = max(1, (int) ($_GET['page'] ?? 1));
$hotTotal = (int) $db->query('SELECT COUNT(*) FROM links')->fetchColumn();
$hotTotalPages = max(1, (int) ceil($hotTotal / $hotPerPage));
$hotPage = min($hotPage, $hotTotalPages);
$hotOffset = ($hotPage - 1) * $hotPerPage;

$hotStmt = $db->prepare(
    'SELECT l.id, l.title, l.url, l.description, p.name AS platform_name, p.logo AS platform_logo
     FROM links l
     JOIN platforms p ON l.platform_id = p.id
     ORDER BY l.created_at DESC
     LIMIT ? OFFSET ?'
);
$hotStmt->bindValue(1, $hotPerPage, PDO::PARAM_INT);
$hotStmt->bindValue(2, $hotOffset, PDO::PARAM_INT);
$hotStmt->execute();
$hotLinks = $hotStmt->fetchAll();

// 推荐搜索词
$recommendedRaw = Settings::get('recommended_keywords', '');
$recommendedKeywords = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $recommendedRaw))));

// 热门搜索词
$hotKeywordsRaw = Settings::get('hot_keywords', '');
$hotKeywords = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $hotKeywordsRaw))));

// 公告弹窗
$announcementEnabled = Settings::get('announcement_enabled', '0') === '1';
$announcementTitle = Settings::get('announcement_title', '');
$announcementContent = Settings::get('announcement_content', '');

// 底部链接
$footerSections = $db->query('SELECT * FROM footer_sections ORDER BY id ASC')->fetchAll();
$footerLinks = [];
foreach ($footerSections as $section) {
    $stmt = $db->prepare(
        'SELECT * FROM footer_links WHERE section_id = ? ORDER BY sort_order ASC, id ASC LIMIT 6'
    );
    $stmt->execute([$section['id']]);
    $footerLinks[$section['type']] = [
        'name' => $section['name'],
        'links' => $stmt->fetchAll(),
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="<?= e($defaultTheme) ?>" data-theme-rev="<?= e(theme_revision($defaultTheme)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?></title>
    <?= site_favicon_tag($siteLogo) ?>
    <?php echo icons_stylesheet(); ?>
    <link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
</head>
<body>
    <!-- 顶部导航 -->
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="logo">
                <?php if ($siteLogo): ?>
                    <img src="<?= e(upload_url($siteLogo)) ?>" alt="<?= e($siteName) ?>" class="logo-img">
                <?php else: ?>
                    <span class="logo-icon"><?= icon('link') ?></span>
                <?php endif; ?>
                <span class="logo-text"><?= e($siteName) ?></span>
            </a>
            <button class="theme-toggle" id="themeToggle" title="切换主题">
                <span class="icon-sun"><?= icon('sun') ?></span>
                <span class="icon-moon"><?= icon('moon') ?></span>
            </button>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <!-- 轮播图 -->
            <?php if (!empty($carousels)): ?>
            <section class="carousel-section">
                <div class="carousel" id="carousel" data-count="<?= count($carousels) ?>">
                    <div class="carousel-viewport">
                        <div class="carousel-track" id="carouselTrack">
                            <?php foreach ($carousels as $i => $item): ?>
                            <div class="carousel-slide<?= $i === 0 ? ' active' : '' ?>" data-id="<?= $item['id'] ?>">
                                <?php if ($item['link_url']): ?>
                                <a href="<?= e($item['link_url']) ?>" target="_blank" rel="noopener" class="carousel-link" data-carousel-id="<?= $item['id'] ?>">
                                <?php endif; ?>
                                    <img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['description']) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                                    <?php if ($item['description']): ?>
                                    <div class="carousel-caption">
                                        <span><?= e($item['description']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php if ($item['link_url']): ?>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if (count($carousels) > 1): ?>
                    <button class="carousel-btn prev" id="carouselPrev" aria-label="上一张"><?= icon('chevron-left') ?></button>
                    <button class="carousel-btn next" id="carouselNext" aria-label="下一张"><?= icon('chevron-right') ?></button>
                    <div class="carousel-footer">
                        <div class="carousel-dots" id="carouselDots">
                            <?php foreach ($carousels as $i => $item): ?>
                            <button class="dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>" aria-label="第 <?= $i + 1 ?> 张"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 搜索区域 -->
            <section class="search-section">
                <div class="search-hero">
                    <h1 class="hero-title">发现优质资源</h1>
                    <p class="hero-desc">搜索平台资源，一键直达</p>
                </div>
                <div class="search-box">
                    <div class="search-scope">
                        <div class="scope-picker" id="scopePicker">
                            <input type="hidden" id="searchScope" value="all">
                            <button type="button" class="scope-picker-trigger" id="scopePickerTrigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="scope-picker-icon" id="scopePickerIcon"><i class="fa-solid fa-layer-group"></i></span>
                                <span class="scope-picker-label" id="scopePickerLabel">全部平台</span>
                                <i class="fa-solid fa-chevron-down scope-picker-arrow"></i>
                            </button>
                            <ul class="scope-picker-menu" id="scopePickerMenu" role="listbox">
                                <li class="scope-picker-option active" data-value="all" role="option">
                                    <span class="scope-picker-icon"><i class="fa-solid fa-layer-group"></i></span>
                                    <span class="scope-picker-text">全部平台</span>
                                </li>
                                <?php foreach ($platforms as $p): ?>
                                <li class="scope-picker-option" data-value="<?= $p['id'] ?>" role="option">
                                    <span class="scope-picker-icon">
                                        <?php if ($p['logo']): ?>
                                        <img src="<?= e(upload_url($p['logo'])) ?>" alt="">
                                        <?php else: ?>
                                        <?= e(mb_substr($p['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="scope-picker-text"><?= e($p['name']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="search-input-wrap">
                        <input type="text" id="searchInput" placeholder="搜索链接、平台..." autocomplete="off">
                        <button type="button" id="searchClear" class="search-clear" aria-label="清空搜索" hidden>
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <button type="button" id="searchBtn" class="search-btn">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </button>
                </div>

                <!-- 搜索词 -->
                <div class="search-tags">
                    <div class="tags-group" id="recentTags">
                        <span class="tags-label"><?= icon('clock', 'tag-icon') ?> 最近搜索</span>
                        <div class="tags-list"></div>
                    </div>
                    <?php if (!empty($recommendedKeywords)): ?>
                    <div class="tags-group">
                        <span class="tags-label"><?= icon('star', 'tag-icon') ?> 推荐搜索</span>
                        <div class="tags-list">
                            <?php foreach ($recommendedKeywords as $kw): ?>
                            <button class="tag reco-tag" data-keyword="<?= e($kw) ?>"><?= e($kw) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($hotKeywords)): ?>
                    <div class="tags-group">
                        <span class="tags-label"><?= icon('fire', 'tag-icon') ?> 热门搜索</span>
                        <div class="tags-list">
                            <?php foreach ($hotKeywords as $kw): ?>
                            <button class="tag hot-tag" data-keyword="<?= e($kw) ?>"><?= e($kw) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 搜索结果 -->
            <section class="results-section" id="resultsSection" style="display:none;">
                <div class="results-header">
                    <h2>搜索结果</h2>
                    <span class="results-count" id="resultsCount"></span>
                </div>
                <div class="results-grid" id="resultsGrid"></div>
            </section>

            <!-- 热门资源 -->
            <section class="hot-links-section" id="hotLinksSection">
                <h2 class="section-title">热门资源</h2>
                <?php if (empty($hotLinks)): ?>
                    <p class="empty-hint"><i class="fa-solid fa-link"></i> 暂无链接，请在后台添加</p>
                <?php else: ?>
                <div class="hot-links-grid">
                    <?php foreach ($hotLinks as $link): ?>
                    <a href="#" class="hot-link-card external-link" data-link-id="<?= $link['id'] ?>" data-link-url="<?= e($link['url']) ?>">
                        <h3 class="hot-link-title"><?= e($link['title']) ?></h3>
                        <p class="hot-link-desc"><?= e($link['description'] ?: '暂无描述') ?></p>
                        <div class="hot-link-platform">
                            <span class="hot-link-platform-icon">
                                <?php if ($link['platform_logo']): ?>
                                    <img src="<?= e(upload_url($link['platform_logo'])) ?>" alt="">
                                <?php else: ?>
                                    <?= e(mb_substr($link['platform_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </span>
                            <span class="hot-link-platform-name"><?= e($link['platform_name']) ?></span>
                            <span class="hot-link-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($hotTotalPages > 1): ?>
                <nav class="pagination" aria-label="热门资源分页">
                    <?php if ($hotPage > 1): ?>
                    <a href="/?page=<?= $hotPage - 1 ?>#hotLinksSection" class="pagination-btn">上一页</a>
                    <?php else: ?>
                    <span class="pagination-btn disabled">上一页</span>
                    <?php endif; ?>

                    <span class="pagination-info">第 <?= $hotPage ?> / <?= $hotTotalPages ?> 页</span>

                    <form class="pagination-jump" method="get" action="/#hotLinksSection">
                        <input type="number" name="page" min="1" max="<?= $hotTotalPages ?>" value="<?= $hotPage ?>" aria-label="页码">
                        <button type="submit">跳转</button>
                    </form>

                    <?php if ($hotPage < $hotTotalPages): ?>
                    <a href="/?page=<?= $hotPage + 1 ?>#hotLinksSection" class="pagination-btn">下一页</a>
                    <?php else: ?>
                    <span class="pagination-btn disabled">下一页</span>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- 底部 -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <?php foreach (['nav', 'friend', 'agreement'] as $type): ?>
                <?php if (!empty($footerLinks[$type]['links'])): ?>
                <div class="footer-col">
                    <h4><?= e($footerLinks[$type]['name']) ?></h4>
                    <ul>
                        <?php foreach ($footerLinks[$type]['links'] as $link): ?>
                        <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener"><?= e($link['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="footer-bottom">
                <?php if ($copyright): ?><p><?= e($copyright) ?></p><?php endif; ?>
                <?php if ($icp): ?><p><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener"><?= e($icp) ?></a></p><?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- 离开本站确认 -->
    <div class="leave-site-modal" id="leaveSiteModal" aria-hidden="true">
        <div class="leave-site-backdrop" id="leaveSiteBackdrop"></div>
        <div class="leave-site-dialog" role="alertdialog" aria-labelledby="leaveSiteTitle">
            <div class="leave-site-header">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h3 id="leaveSiteTitle">离开本站提醒</h3>
            </div>
            <div class="leave-site-body">
                <p>您即将离开本站，前往外部链接：</p>
                <p class="leave-site-url" id="leaveSiteUrl"></p>
                <p class="leave-site-hint">外部网站内容与本站无关，请注意账号与财产安全。</p>
            </div>
            <div class="leave-site-footer">
                <button type="button" class="leave-site-btn leave-site-btn-cancel" id="leaveSiteCancel">留在此页</button>
                <button type="button" class="leave-site-btn leave-site-btn-confirm" id="leaveSiteConfirm">继续访问</button>
            </div>
        </div>
    </div>

    <?php if ($announcementEnabled && $announcementContent !== ''): ?>
    <div class="announcement-modal" id="announcementModal" aria-hidden="true">
        <div class="announcement-backdrop" id="announcementBackdrop"></div>
        <div class="announcement-dialog" role="dialog" aria-labelledby="announcementTitle">
            <div class="announcement-header">
                <h3 id="announcementTitle"><?= e($announcementTitle ?: '公告') ?></h3>
                <button type="button" class="announcement-close" id="announcementClose" aria-label="关闭"><?= icon('xmark') ?></button>
            </div>
            <div class="announcement-body"><?= nl2br(e($announcementContent)) ?></div>
            <div class="announcement-footer">
                <button type="button" class="announcement-btn" id="announcementConfirm">我知道了</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
