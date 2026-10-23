<?php
$pageTitle = '底部链接';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/modal.php';

$db = Database::getInstance();
$message = '';
$error = '';

$sectionTypes = ['nav' => '导航链接', 'friend' => '友情链接', 'agreement' => '服务协议'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'update_section') {
                $type = $_POST['section_type'] ?? '';
                $name = trim($_POST['section_name'] ?? '');
                if (!isset($sectionTypes[$type]) || $name === '') {
                    throw new RuntimeException('分类名称不能为空');
                }
                $stmt = $db->prepare('UPDATE footer_sections SET name = ? WHERE type = ?');
                $stmt->execute([$name, $type]);
                $message = '分类名称已更新';
            } elseif ($action === 'add_link') {
                $type = $_POST['section_type'] ?? '';
                $title = trim($_POST['title'] ?? '');
                $url = trim($_POST['url'] ?? '');
                if (!isset($sectionTypes[$type]) || $title === '' || $url === '') {
                    throw new RuntimeException('请填写完整信息');
                }
                $stmt = $db->prepare('SELECT id FROM footer_sections WHERE type = ?');
                $stmt->execute([$type]);
                $section = $stmt->fetch();
                if (!$section) throw new RuntimeException('分类不存在');

                $countStmt = $db->prepare('SELECT COUNT(*) FROM footer_links WHERE section_id = ?');
                $countStmt->execute([$section['id']]);
                $linkCount = (int) $countStmt->fetchColumn();
                if ($linkCount >= 6) {
                    throw new RuntimeException('每个分类最多6个链接');
                }

                $stmt = $db->prepare('INSERT INTO footer_links (section_id, title, url, sort_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$section['id'], $title, $url, $linkCount]);
                $message = '链接添加成功';
            } elseif ($action === 'edit_link') {
                $linkId = (int) ($_POST['link_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $url = trim($_POST['url'] ?? '');
                if ($title === '' || $url === '') throw new RuntimeException('请填写完整信息');
                $stmt = $db->prepare('UPDATE footer_links SET title=?, url=? WHERE id=?');
                $stmt->execute([$title, $url, $linkId]);
                $message = '链接更新成功';
            } elseif ($action === 'delete_link') {
                $linkId = (int) ($_POST['link_id'] ?? 0);
                $db->prepare('DELETE FROM footer_links WHERE id = ?')->execute([$linkId]);
                $message = '链接已删除';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$sections = [];
foreach ($sectionTypes as $type => $defaultName) {
    $stmt = $db->prepare('SELECT * FROM footer_sections WHERE type = ?');
    $stmt->execute([$type]);
    $section = $stmt->fetch();
    if ($section) {
        $linkStmt = $db->prepare(
            'SELECT * FROM footer_links WHERE section_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $linkStmt->execute([$section['id']]);
        $sections[$type] = [
            'section' => $section,
            'links' => $linkStmt->fetchAll(),
        ];
    }
}
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php foreach ($sectionTypes as $type => $label): ?>
<?php $sec = $sections[$type] ?? null; if (!$sec) continue; ?>
<div class="panel">
    <div class="panel-header">
        <h3><?= e($label) ?></h3>
        <div style="display:flex;align-items:center;gap:10px">
            <span class="badge"><?= count($sec['links']) ?>/6</span>
            <?php if (count($sec['links']) < 6): ?>
            <button type="button" class="btn btn-primary btn-sm"
                data-modal-open="footerModal"
                data-fill-form="footerForm"
                data-action="add_link"
                data-link-id=""
                data-section-type="<?= $type ?>"
                data-title=""
                data-url="">
                <?= icon('plus') ?> 添加链接
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="panel-body">
        <form method="post" style="margin-bottom:20px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="section_type" value="<?= $type ?>">
            <div class="form-group" style="margin-bottom:0">
                <label>分类名称</label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="section_name" value="<?= e($sec['section']['name']) ?>" required style="flex:1">
                    <button type="submit" class="btn btn-sm btn-secondary">保存名称</button>
                </div>
            </div>
        </form>

        <?php if (empty($sec['links'])): ?>
            <p class="empty-text">暂无链接</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>标题</th><th>链接</th><th class="col-actions">操作</th></tr></thead>
                <tbody>
                    <?php foreach ($sec['links'] as $link): ?>
                    <tr>
                        <td><?= e($link['title']) ?></td>
                        <td class="text-truncate" style="max-width:250px"><?= e($link['url']) ?></td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm btn-secondary"
                                data-modal-open="footerModal"
                                data-fill-form="footerForm"
                                data-action="edit_link"
                                data-link-id="<?= $link['id'] ?>"
                                data-section-type="<?= $type ?>"
                                data-title="<?= e($link['title']) ?>"
                                data-url="<?= e($link['url']) ?>">编辑</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_link">
                                <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
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
<?php endforeach; ?>

<?php
ob_start();
?>
<form method="post" class="form-grid" id="footerForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_link">
    <input type="hidden" name="link_id" value="" data-field="link-id">
    <input type="hidden" name="section_type" value="" data-field="section-type">
    <div class="form-group">
        <label>标题 *</label>
        <input type="text" name="title" required data-field="title">
    </div>
    <div class="form-group">
        <label>链接 *</label>
        <input type="url" name="url" required placeholder="https://" data-field="url">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary" id="footerFormSubmit">保存</button>
        <button type="button" class="btn btn-secondary" data-modal-close>取消</button>
    </div>
</form>
<?php
modal_overlay('footerModal', '添加链接', ob_get_clean());
?>

<script>
document.querySelectorAll('[data-fill-form="footerForm"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const isEdit = btn.getAttribute('data-action') === 'edit_link';
        document.querySelector('#footerModal .modal-header h3').textContent = isEdit ? '编辑链接' : '添加链接';
        document.getElementById('footerFormSubmit').textContent = isEdit ? '保存修改' : '添加';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
