<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../includes/icons.php';



if (Auth::check()) {

    redirect('/admin/dashboard.php');

}



$error = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');

    $password = $_POST['password'] ?? '';



    if (Auth::attempt($username, $password)) {

        redirect('/admin/dashboard.php');

    } else {

        $error = '用户名或密码错误';

    }

}



$adminTheme = is_installed() ? Settings::getAdminTheme() : 'light';

$siteName = is_installed() ? Settings::getSiteName() : '链接管理平台';

$siteLogo = is_installed() ? Settings::getSiteLogo() : '';

$adminThemeRev = theme_revision($adminTheme);

?>

<!DOCTYPE html>

<html lang="zh-CN" data-theme="<?= e($adminTheme) ?>" data-theme-rev="<?= e($adminThemeRev) ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>后台登录 - <?= e($siteName) ?></title>

    <?= site_favicon_tag($siteLogo) ?>

    <?php echo icons_stylesheet(); ?>

    <link rel="stylesheet" href="/assets/css/admin.css">

</head>

<body class="login-page">

<div class="login-wrap">

    <div class="login-card">

        <div class="login-logo"><?= site_logo_html($siteLogo, 'login-logo-img', 'gear') ?></div>

        <h1>后台管理</h1>

        <p class="subtitle"><?= e($siteName) ?></p>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <form method="post">

            <div class="form-group">

                <label>用户名</label>

                <input type="text" name="username" required autofocus autocomplete="username">

            </div>

            <div class="form-group">

                <label>密码</label>

                <input type="password" name="password" required autocomplete="current-password">

            </div>

            <button type="submit" class="btn btn-primary btn-block">登录</button>

        </form>

        <a href="/" class="back-link"><?= icon('arrow-left', 'back-icon') ?> 返回首页</a>

    </div>

</div>

<script src="/assets/js/admin.js"></script>

</body>

</html>


