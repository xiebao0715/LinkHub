<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_once __DIR__ . '/../../includes/icons.php';

Auth::requireLogin();



$pageTitle = $pageTitle ?? '后台管理';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$adminTheme = Settings::getAdminTheme();

$siteName = Settings::getSiteName();

$siteLogo = Settings::getSiteLogo();

$adminThemeRev = theme_revision($adminTheme);

?>

<!DOCTYPE html>

<html lang="zh-CN" data-theme="<?= e($adminTheme) ?>" data-theme-rev="<?= e($adminThemeRev) ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> - <?= e($siteName) ?></title>

    <?= site_favicon_tag($siteLogo) ?>

    <?php echo icons_stylesheet(); ?>

    <link rel="stylesheet" href="/assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">

            <span class="sidebar-logo"><?= site_logo_html($siteLogo, 'sidebar-logo-img') ?></span>

            <span class="sidebar-title"><?= e($siteName) ?></span>

        </div>

        <nav class="sidebar-nav">

            <a href="/admin/dashboard.php" class="nav-item<?= $currentPage === 'dashboard' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('chart-line') ?></span> 仪表盘

            </a>

            <a href="/admin/carousels.php" class="nav-item<?= $currentPage === 'carousels' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('images') ?></span> 轮播图设置

            </a>

            <a href="/admin/platforms.php" class="nav-item<?= $currentPage === 'platforms' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('cubes') ?></span> 平台管理

            </a>

            <a href="/admin/links.php" class="nav-item<?= $currentPage === 'links' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('link') ?></span> 链接管理

            </a>

            <a href="/admin/footer.php" class="nav-item<?= $currentPage === 'footer' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('list') ?></span> 底部链接

            </a>

            <a href="/admin/settings.php" class="nav-item<?= $currentPage === 'settings' ? ' active' : '' ?>">

                <span class="nav-icon"><?= icon('gear') ?></span> 系统设置

            </a>

        </nav>

        <div class="sidebar-footer">

            <a href="/" target="_blank" class="nav-item"><span class="nav-icon"><?= icon('globe') ?></span> 访问前台</a>

            <a href="/admin/logout.php" class="nav-item"><span class="nav-icon"><?= icon('right-from-bracket') ?></span> 退出登录</a>

        </div>

    </aside>



    <div class="main-area">

        <header class="topbar">

            <button class="menu-toggle" id="menuToggle"><?= icon('bars') ?></button>

            <div class="topbar-brand">

                <?php if ($siteLogo): ?>

                <img src="<?= e(upload_url($siteLogo)) ?>" alt="<?= e($siteName) ?>" class="topbar-logo">

                <?php endif; ?>

                <h1 class="page-title"><?= e($pageTitle) ?></h1>

            </div>

            <button class="theme-toggle" id="adminThemeToggle" title="切换主题">

                <span class="icon-sun"><?= icon('sun') ?></span>

                <span class="icon-moon"><?= icon('moon') ?></span>

            </button>

        </header>

        <main class="content">


