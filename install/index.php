<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/schema.php';
if (is_installed()) {
    redirect('/');
}

$step = (int) ($_GET['step'] ?? 1);
$error = '';
$success = false;
$lockError = false;

// 已有数据库配置但缺表时，自动补建表结构
if (file_exists(config_path('database.php'))) {
    try {
        require_once __DIR__ . '/../includes/Database.php';
        Database::reset();
        ensure_install_schema(Database::getInstance());
    } catch (Throwable $e) {
        $error = '数据库表结构修复失败：' . $e->getMessage();
    }
}

// 数据库已就绪但 lock 文件缺失时，尝试自动完成安装
if (install_data_complete()) {
    if (finalize_installation()) {
        redirect('/');
    }
    $lockError = true;
    $step = 2;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int) ($_POST['step'] ?? 1);

    if ($step === 1) {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = (int) ($_POST['db_port'] ?? 3306);
        $database = trim($_POST['db_name'] ?? '');
        $username = trim($_POST['db_user'] ?? '');
        $password = $_POST['db_pass'] ?? '';

        if ($database === '' || $username === '') {
            $error = '请填写完整的数据库信息';
        } else {
            try {
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$database}`");

                run_install_schema($pdo);

                $configDir = config_path();
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }

                $configContent = "<?php\nreturn " . var_export([
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                ], true) . ";\n";
                file_put_contents(config_path('database.php'), $configContent);

                $_SESSION['install_db'] = true;
                $step = 2;
            } catch (PDOException $e) {
                $error = '数据库连接失败：' . $e->getMessage();
            }
        }
    } elseif ($step === 2) {
        if (empty($_SESSION['install_db']) && !file_exists(config_path('database.php'))) {
            redirect('/install/');
        }

        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPass2 = $_POST['admin_pass2'] ?? '';
        $siteName = trim($_POST['site_name'] ?? '链接管理平台');

        if ($adminUser === '' || strlen($adminPass) < 6) {
            $error = '管理员用户名不能为空，密码至少6位';
        } elseif ($adminPass !== $adminPass2) {
            $error = '两次密码输入不一致';
        } else {
            try {
                require_once __DIR__ . '/../includes/Database.php';
                Database::reset();
                $db = Database::getInstance();

                ensure_install_schema($db);

                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare('SELECT id FROM admins WHERE username = ?');
                $stmt->execute([$adminUser]);
                $existingAdmin = $stmt->fetch();
                if ($existingAdmin) {
                    $stmt = $db->prepare('UPDATE admins SET password = ? WHERE id = ?');
                    $stmt->execute([$hash, $existingAdmin['id']]);
                } else {
                    $stmt = $db->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
                    $stmt->execute([$adminUser, $hash]);
                }

                $defaults = [
                    'site_name' => $siteName,
                    'site_logo' => '',
                    'icp_number' => '',
                    'copyright' => '© ' . date('Y') . ' 链接管理平台',
                    'default_theme' => 'light',
                    'admin_theme' => 'light',
                    'total_visits' => '0',
                    'recommended_keywords' => '',
                    'announcement_enabled' => '0',
                    'announcement_title' => '',
                    'announcement_content' => '',
                ];
                $stmt = $db->prepare(
                    'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );
                foreach ($defaults as $k => $v) {
                    $stmt->execute([$k, $v]);
                }

                $sections = [
                    ['nav', '导航链接'],
                    ['friend', '友情链接'],
                    ['agreement', '服务协议'],
                ];
                $stmt = $db->prepare(
                    'INSERT INTO footer_sections (type, name) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE name = VALUES(name)'
                );
                foreach ($sections as $s) {
                    $stmt->execute($s);
                }

                if (!finalize_installation()) {
                    throw new RuntimeException(
                        '数据库已保存成功，但无法写入 config/installed.lock 文件。'
                        . '请在宝塔面板 → 文件 → 找到项目 config 目录 → 权限设为 755，'
                        . '所有者设为 www，然后刷新本页面即可自动完成安装。'
                    );
                }

                unset($_SESSION['install_db']);
                $success = true;
            } catch (Exception $e) {
                $error = '安装失败：' . $e->getMessage();
            }
        }
    }
}

// 数据库已配置但未完成安装时，自动进入第二步
if (!$success && !$lockError && file_exists(config_path('database.php')) && !file_exists(config_path('installed.lock'))) {
    $_SESSION['install_db'] = true;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $step = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 链接管理平台</title>
    <?php echo icons_stylesheet(); ?>
    <link rel="stylesheet" href="/assets/css/install.css"></head>
<body>
<div class="install-wrap">
    <div class="install-card">
        <div class="install-logo"><?= icon('link') ?></div>        <h1>链接管理平台</h1>
        <p class="subtitle">首次安装向导</p>

        <?php if ($lockError): ?>
            <div class="alert alert-error">
                <strong>数据库已配置完成，但缺少安装锁定文件。</strong><br>
                请在宝塔面板操作：<br>
                1. 文件 → 进入项目 <code>config</code> 目录<br>
                2. 权限设为 <strong>755</strong>，所有者设为 <strong>www</strong><br>
                3. 同样设置 <code>uploads</code> 目录权限<br>
                4. 刷新本页面，系统会自动完成安装
            </div>
            <div class="btn-group">
                <a href="/install/" class="btn btn-primary">刷新页面</a>
                <a href="/admin/login.php" class="btn btn-secondary">尝试进入后台</a>
            </div>
        <?php elseif ($success): ?>
            <div class="success-box">
                <div class="success-icon"><?= icon('circle-check') ?></div>                <h2>安装成功！</h2>
                <p>系统已配置完成，现在可以开始使用了</p>
                <div class="btn-group">
                    <a href="/" class="btn btn-primary">访问首页</a>
                    <a href="/admin/login.php" class="btn btn-secondary">进入后台</a>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <div class="steps">
                <span class="step done">1</span>
                <span class="step-line done"></span>
                <span class="step active">2</span>
            </div>
            <h2>创建管理员账号</h2>
            <form method="post">
                <input type="hidden" name="step" value="2">
                <div class="form-group">
                    <label>站点名称</label>
                    <input type="text" name="site_name" value="链接管理平台" required>
                </div>
                <div class="form-group">
                    <label>管理员用户名</label>
                    <input type="text" name="admin_user" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>管理员密码</label>
                    <input type="password" name="admin_pass" required minlength="6">
                </div>
                <div class="form-group">
                    <label>确认密码</label>
                    <input type="password" name="admin_pass2" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">完成安装</button>
            </form>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <div class="steps">
                <span class="step active">1</span>
                <span class="step-line"></span>
                <span class="step">2</span>
            </div>
            <h2>配置数据库</h2>
            <form method="post">
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="number" name="db_port" value="3306" required>
                </div>
                <div class="form-group">
                    <label>数据库名</label>
                    <input type="text" name="db_name" value="link_manager" required>
                </div>
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="db_pass">
                </div>
                <button type="submit" class="btn btn-primary btn-block">下一步</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
