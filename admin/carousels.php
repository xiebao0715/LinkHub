<?php
$pageTitle = '轮播图设置';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/modal.php';

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败，请重试';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                $count = (int) $db->query('SELECT COUNT(*) FROM carousels')->fetchColumn();
                if ($count >= 10) {
                    throw new RuntimeException('轮播图最多10个');
                }
                $desc = trim($_POST['description'] ?? '');
                $linkUrl = trim($_POST['link_url'] ?? '');
                $image = Upload::image($_FILES['image'] ?? [], 'carousels');
                if (!$image) {
                    throw new RuntimeException('请上传图片');
                }
                $stmt = $db->prepare(
                    'INSERT INTO carousels (description, image, link_url, sort_order) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$desc, $image, $linkUrl, $count]);
                $message = '轮播图添加成功';
            } elseif ($action === 'edit') {
                $id = (int) ($_POST['id'] ?? 0);
                $desc = trim($_POST['description'] ?? '');
                $linkUrl = trim($_POST['link_url'] ?? '');
                $stmt = $db->prepare('SELECT image FROM carousels WHERE id = ?');
                $stmt->execute([$id]);
                $old = $stmt->fetch();
                if (!$old) throw new RuntimeException('轮播图不存在');

                $image = $old['image'];
                if (!empty($_FILES['image']['name'])) {
                    $newImage = Upload::image($_FILES['image'], 'carousels');
                    if ($newImage) {
                        delete_upload_file($image);
                        $image = $newImage;
                    }
                }
                $stmt = $db->prepare(
                    'UPDATE carousels SET description=?, image=?, link_url=? WHERE id=?'
                );
                $stmt->execute([$desc, $image, $linkUrl, $id]);
                $message = '轮播图更新成功';
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $db->prepare('SELECT image FROM carousels WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    delete_upload_file($row['image']);
                    $db->prepare('DELETE FROM carousels WHERE id = ?')->execute([$id]);
                    $message = '轮播图已删除';
                }
            } elseif ($action === 'toggle') {
                $id = (int) ($_POST['id'] ?? 0);
                $db->prepare('UPDATE carousels SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
                $message = '状态已更新';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$carousels = $db->query('SELECT * FROM carousels ORDER BY sort_order ASC, id ASC')->fetchAll();
$editItem = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId) {
    foreach ($carousels as $c) {
        if ($c['id'] == $editId) { $editItem = $c; break; }
    }
}
$canAdd = count($carousels) < 10;
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3>轮播图列表</h3>
        <div style="display:flex;align-items:center;gap:10px">
            <span class="badge"><?= count($carousels) ?>/10</span>
            <?php if ($canAdd): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnAddCarousel"
                data-modal-open="carouselModal"
                data-fill-form="carouselForm"
                data-action="add"
                data-id=""
                data-description=""
                data-link-url="">
                <?= icon('plus') ?> 添加轮播图
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="panel-body">
        <?php if (empty($carousels)): ?>
            <p class="empty-text">暂无轮播图，点击上方按钮添加</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>预览</th>
                        <th>描述</th>
                        <th>链接</th>
                        <th>点击量</th>
                        <th>状态</th>
                        <th class="col-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carousels as $c): ?>
                    <tr>
                        <td><img src="<?= e(upload_url($c['image'])) ?>" class="table-thumb" alt=""></td>
                        <td><?= e($c['description']) ?></td>
                        <td class="text-truncate" style="max-width:200px"><?= e($c['link_url']) ?></td>
                        <td><?= $c['clicks'] ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="badge <?= $c['is_active'] ? 'badge-success' : 'badge-muted' ?>">
                                    <?= $c['is_active'] ? '启用' : '禁用' ?>
                                </button>
                            </form>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-sm btn-secondary"
                                data-modal-open="carouselModal"
                                data-fill-form="carouselForm"
                                data-action="edit"
                                data-id="<?= $c['id'] ?>"
                                data-description="<?= e($c['description']) ?>"
                                data-link-url="<?= e($c['link_url']) ?>">编辑</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
<form method="post" enctype="multipart/form-data" class="form-grid" id="carouselForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="id" value="" data-field="id">
    <div class="form-group">
        <label>描述</label>
        <input type="text" name="description" data-field="description" placeholder="轮播图描述">
    </div>
    <div class="form-group">
        <label>跳转链接</label>
        <input type="url" name="link_url" data-field="link-url" placeholder="https://">
    </div>
    <div class="form-group">
        <label>图片 <span id="carouselImageHint">（必填）</span></label>
        <input type="file" name="image" accept="image/*" id="carouselImageInput">
        <div class="preview-img" id="carouselPreview" style="display:none"></div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary" id="carouselFormSubmit">保存</button>
        <button type="button" class="btn btn-secondary" data-modal-close>取消</button>
    </div>
</form>
<?php
modal_overlay('carouselModal', '添加轮播图', ob_get_clean(), 'lg');
?>

<script>
(function () {
    const form = document.getElementById('carouselForm');
    const imageInput = document.getElementById('carouselImageInput');
    const imageHint = document.getElementById('carouselImageHint');
    const preview = document.getElementById('carouselPreview');

    document.querySelectorAll('[data-fill-form="carouselForm"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const isEdit = btn.getAttribute('data-action') === 'edit';
            document.querySelector('#carouselModal .modal-header h3').textContent = isEdit ? '编辑轮播图' : '添加轮播图';
            document.getElementById('carouselFormSubmit').textContent = isEdit ? '保存修改' : '添加';
            imageInput.required = !isEdit;
            imageHint.textContent = isEdit ? '（留空则不更新）' : '（必填）';
            preview.style.display = 'none';
            preview.innerHTML = '';
        });
    });

    <?php if ($editItem): ?>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.querySelector('[data-id="<?= $editItem['id'] ?>"][data-fill-form="carouselForm"]');
        if (btn) btn.click();
    });
    <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
