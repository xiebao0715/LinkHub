<?php
$pageTitle = '平台管理';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/modal.php';

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                $name = trim($_POST['name'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                if ($name === '') throw new RuntimeException('平台名称不能为空');
                $logo = Upload::image($_FILES['logo'] ?? [], 'platforms');
                $count = (int) $db->query('SELECT COUNT(*) FROM platforms')->fetchColumn();
                $stmt = $db->prepare('INSERT INTO platforms (name, logo, description, sort_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $logo, $desc, $count]);
                $message = '平台添加成功';
            } elseif ($action === 'edit') {
                $id = (int) ($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                if ($name === '') throw new RuntimeException('平台名称不能为空');
                $stmt = $db->prepare('SELECT logo FROM platforms WHERE id = ?');
                $stmt->execute([$id]);
                $old = $stmt->fetch();
                if (!$old) throw new RuntimeException('平台不存在');
                $logo = $old['logo'];
                if (!empty($_FILES['logo']['name'])) {
                    $newLogo = Upload::image($_FILES['logo'], 'platforms');
                    if ($newLogo) {
                        delete_upload_file($logo);
                        $logo = $newLogo;
                    }
                }
                $stmt = $db->prepare('UPDATE platforms SET name=?, logo=?, description=? WHERE id=?');
                $stmt->execute([$name, $logo, $desc, $id]);
                $message = '平台更新成功';
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $db->prepare('SELECT logo FROM platforms WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    delete_upload_file($row['logo']);
                    $db->prepare('DELETE FROM platforms WHERE id = ?')->execute([$id]);
                    $message = '平台已删除（关联链接一并删除）';
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$platforms = $db->query(
    'SELECT p.*, (SELECT COUNT(*) FROM links l WHERE l.platform_id = p.id) as link_count
     FROM platforms p ORDER BY p.sort_order ASC, p.id ASC'
)->fetchAll();
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>平台列表</h3>
        <button type="button" class="btn btn-primary btn-sm"
            data-modal-open="platformModal"
            data-fill-form="platformForm"
            data-action="add"
            data-id=""
            data-name=""
            data-description="">
            <?= icon('plus') ?> 添加平台
        </button>
    </div>
    <div class="panel-body">
        <?php if (empty($platforms)): ?>
            <p class="empty-text">暂无平台，点击上方按钮添加</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>LOGO</th><th>名称</th><th>描述</th><th>链接数</th><th class="col-actions">操作</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($platforms as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['logo']): ?>
                                <img src="<?= e(upload_url($p['logo'])) ?>" class="table-thumb" alt="">
                            <?php else: ?>
                                <span class="avatar-placeholder"><?= e(mb_substr($p['name'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['description']) ?></td>
                        <td>
                            <a href="/admin/links.php?platform=<?= $p['id'] ?>" class="badge badge-info">
                                <?= $p['link_count'] ?> 条
                            </a>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm btn-secondary"
                                data-modal-open="platformModal"
                                data-fill-form="platformForm"
                                data-action="edit"
                                data-id="<?= $p['id'] ?>"
                                data-name="<?= e($p['name']) ?>"
                                data-description="<?= e($p['description']) ?>">编辑</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？关联链接将一并删除')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
ob_start();
?>
<form method="post" enctype="multipart/form-data" class="form-grid" id="platformForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="id" value="" data-field="id">
    <div class="form-group">
        <label>平台名称 *</label>
        <input type="text" name="name" required data-field="name">
    </div>
    <div class="form-group">
        <label>描述</label>
        <textarea name="description" rows="2" data-field="description"></textarea>
    </div>
    <div class="form-group">
        <label>LOGO（选填）<span id="platformLogoHint"></span></label>
        <input type="file" name="logo" accept="image/*">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary" id="platformFormSubmit">保存</button>
        <button type="button" class="btn btn-secondary" data-modal-close>取消</button>
    </div>
</form>
<?php
modal_overlay('platformModal', '添加平台', ob_get_clean());
?>

<script>
document.querySelectorAll('[data-fill-form="platformForm"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const isEdit = btn.getAttribute('data-action') === 'edit';
        document.querySelector('#platformModal .modal-header h3').textContent = isEdit ? '编辑平台' : '添加平台';
        document.getElementById('platformFormSubmit').textContent = isEdit ? '保存修改' : '添加';
        document.getElementById('platformLogoHint').textContent = isEdit ? '，留空则不更新' : '';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
