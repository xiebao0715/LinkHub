<?php
$pageTitle = '系统设置';
require_once __DIR__ . '/includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败';
    } else {
        try {
            Settings::set('site_name', trim($_POST['site_name'] ?? ''));
            Settings::set('icp_number', trim($_POST['icp_number'] ?? ''));
            Settings::set('copyright', trim($_POST['copyright'] ?? ''));
            Settings::set('default_theme', $_POST['default_theme'] ?? 'light');
            Settings::set('admin_theme', $_POST['admin_theme'] ?? 'light');
            Settings::set('recommended_keywords', trim($_POST['recommended_keywords'] ?? ''));
            Settings::set('hot_keywords', trim($_POST['hot_keywords'] ?? ''));
            Settings::set('announcement_enabled', isset($_POST['announcement_enabled']) ? '1' : '0');
            Settings::set('announcement_title', trim($_POST['announcement_title'] ?? ''));
            Settings::set('announcement_content', trim($_POST['announcement_content'] ?? ''));

            if (!empty($_FILES['site_logo']['name'])) {
                $oldLogo = Settings::getSiteLogo();
                $newLogo = Upload::image($_FILES['site_logo'], 'site');
                if ($newLogo) {
                    delete_upload_file($oldLogo);
                    Settings::set('site_logo', $newLogo);
                }
            }

            // 修改密码
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';
            if ($newPass !== '') {
                if (strlen($newPass) < 6) throw new RuntimeException('密码至少6位');
                if ($newPass !== $confirmPass) throw new RuntimeException('两次密码不一致');
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $db = Database::getInstance();
                $db->prepare('UPDATE admins SET password = ? WHERE id = ?')
                   ->execute([$hash, Auth::user()['id']]);
            }

            $message = '设置已保存';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$settings = Settings::getAll();
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-header"><h3>站点信息</h3></div>
    <div class="panel-body">
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>站点名称</label>
                <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>站点 LOGO（全局，含窗口标题图标）</label>
                <input type="file" name="site_logo" accept="image/*">
                <?php if (!empty($settings['site_logo'])): ?>
                <div class="preview-img"><img src="<?= e(upload_url($settings['site_logo'])) ?>" alt=""></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>备案号</label>
                <input type="text" name="icp_number" value="<?= e($settings['icp_number'] ?? '') ?>" placeholder="京ICP备xxxxxxxx号">
            </div>

            <div class="form-group">
                <label>版权信息</label>
                <input type="text" name="copyright" value="<?= e($settings['copyright'] ?? '') ?>">
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:8px 0">

            <h4 style="margin-bottom:12px;font-size:0.95rem">搜索词配置</h4>
            <div class="form-group">
                <label>推荐搜索词（每行一个，显示在前台搜索区）</label>
                <textarea name="recommended_keywords" rows="4" placeholder="例如：&#10;GitHub&#10;开源项目&#10;教程"><?= e($settings['recommended_keywords'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>热门搜索词（每行一个，显示在前台搜索区）</label>
                <textarea name="hot_keywords" rows="4" placeholder="例如：&#10;网盘&#10;软件下载&#10;影视资源"><?= e($settings['hot_keywords'] ?? '') ?></textarea>
            </div>

            <h4 style="margin-bottom:12px;font-size:0.95rem">公告弹窗</h4>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="announcement_enabled" value="1"
                        <?= ($settings['announcement_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    启用前台公告弹窗
                </label>
            </div>
            <div class="form-group">
                <label>公告标题</label>
                <input type="text" name="announcement_title" value="<?= e($settings['announcement_title'] ?? '') ?>" placeholder="站点公告">
            </div>
            <div class="form-group">
                <label>公告内容</label>
                <textarea name="announcement_content" rows="5" placeholder="支持纯文本，换行将保留"><?= e($settings['announcement_content'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>前台默认主题</label>
                    <select name="default_theme">
                        <option value="light" <?= ($settings['default_theme'] ?? '') === 'light' ? 'selected' : '' ?>>白天模式</option>
                        <option value="dark" <?= ($settings['default_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>夜间模式</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>后台默认主题</label>
                    <select name="admin_theme">
                        <option value="light" <?= ($settings['admin_theme'] ?? '') === 'light' ? 'selected' : '' ?>>白天模式</option>
                        <option value="dark" <?= ($settings['admin_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>夜间模式</option>
                    </select>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:8px 0">

            <h4 style="margin-bottom:12px;font-size:0.95rem">修改管理员密码（留空则不修改）</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>新密码</label>
                    <input type="password" name="new_password" minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>确认密码</label>
                    <input type="password" name="confirm_password" minlength="6" autocomplete="new-password">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存设置</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
