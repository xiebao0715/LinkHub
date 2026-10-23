<?php

$pageTitle = '链接管理';

require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/modal.php';



$db = Database::getInstance();

$message = '';

$error = '';

$batchResult = $_SESSION['batch_result'] ?? null;

unset($_SESSION['batch_result']);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {

        $error = '安全验证失败';

    } else {

        $action = $_POST['action'] ?? '';

        try {

            if ($action === 'add') {

                $platformId = (int) ($_POST['platform_id'] ?? 0);

                $title = trim($_POST['title'] ?? '');

                $url = trim($_POST['url'] ?? '');

                $desc = trim($_POST['description'] ?? '');



                if ($platformId <= 0) throw new RuntimeException('请选择平台');

                if ($title === '' || $url === '') throw new RuntimeException('标题和链接不能为空');

                if (!is_valid_link_url($url)) throw new RuntimeException('链接地址格式不正确');



                $stmt = $db->prepare('SELECT id FROM links WHERE url = ?');

                $stmt->execute([$url]);

                if ($stmt->fetch()) throw new RuntimeException('该链接已存在，请勿重复添加');



                $stmt = $db->prepare(

                    'INSERT INTO links (platform_id, title, url, description) VALUES (?, ?, ?, ?)'

                );

                $stmt->execute([$platformId, $title, $url, $desc]);

                $message = '链接添加成功';

            } elseif ($action === 'delete') {

                $id = (int) ($_POST['id'] ?? 0);

                $db->prepare('DELETE FROM links WHERE id = ?')->execute([$id]);

                $message = '链接已删除';

            } elseif ($action === 'edit') {

                $id = (int) ($_POST['id'] ?? 0);

                $platformId = (int) ($_POST['platform_id'] ?? 0);

                $title = trim($_POST['title'] ?? '');

                $url = trim($_POST['url'] ?? '');

                $desc = trim($_POST['description'] ?? '');



                if ($platformId <= 0) throw new RuntimeException('请选择平台');

                if ($title === '' || $url === '') throw new RuntimeException('标题和链接不能为空');

                if (!is_valid_link_url($url)) throw new RuntimeException('链接地址格式不正确');



                $stmt = $db->prepare('SELECT id FROM links WHERE url = ? AND id != ?');

                $stmt->execute([$url, $id]);

                if ($stmt->fetch()) throw new RuntimeException('该链接已存在');



                $stmt = $db->prepare(

                    'UPDATE links SET platform_id=?, title=?, url=?, description=? WHERE id=?'

                );

                $stmt->execute([$platformId, $title, $url, $desc, $id]);

                $message = '链接更新成功';

            } elseif ($action === 'batch_upload') {

                $raw = trim($_POST['batch_data'] ?? '');

                if ($raw === '') throw new RuntimeException('请输入或上传链接数据');



                $lines = preg_split('/\r\n|\r|\n/', $raw);

                $platformMap = [];

                foreach ($db->query('SELECT id, name FROM platforms')->fetchAll() as $p) {

                    $platformMap[$p['name']] = (int) $p['id'];

                }



                $existingUrls = [];

                foreach ($db->query('SELECT url FROM links')->fetchAll() as $row) {

                    $existingUrls[$row['url']] = true;

                }



                $result = [

                    'added' => [],

                    'new_platforms' => [],

                    'duplicates' => [],

                    'invalid' => [],

                    'format_errors' => [],

                ];

                $seenInBatch = [];

                $maxSort = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) FROM platforms')->fetchColumn();



                $insertLink = $db->prepare(

                    'INSERT INTO links (platform_id, title, url, description) VALUES (?, ?, ?, ?)'

                );

                $insertPlatform = $db->prepare(

                    'INSERT INTO platforms (name, sort_order) VALUES (?, ?)'

                );



                foreach ($lines as $lineNum => $line) {

                    $parsed = parse_batch_link_line($line);

                    if ($parsed === null) continue;



                    if (isset($parsed['error'])) {

                        $result['format_errors'][] = [

                            'line' => $lineNum + 1,

                            'raw' => $parsed['raw'] ?? $line,

                        ];

                        continue;

                    }



                    $platformName = $parsed['platform'];

                    $title = $parsed['title'];

                    $url = $parsed['url'];

                    $description = $parsed['description'];



                    if ($platformName === '' || $title === '') {

                        $result['invalid'][] = [

                            'line' => $lineNum + 1,

                            'platform' => $platformName,

                            'title' => $title,

                            'url' => $url,

                            'reason' => '平台或名称不能为空',

                        ];

                        continue;

                    }



                    if (!is_valid_link_url($url)) {

                        $result['invalid'][] = [

                            'line' => $lineNum + 1,

                            'platform' => $platformName,

                            'title' => $title,

                            'url' => $url,

                            'reason' => '链接格式无效',

                        ];

                        continue;

                    }



                    if (isset($existingUrls[$url]) || isset($seenInBatch[$url])) {

                        $result['duplicates'][] = [

                            'line' => $lineNum + 1,

                            'platform' => $platformName,

                            'title' => $title,

                            'url' => $url,

                        ];

                        continue;

                    }



                    if (!isset($platformMap[$platformName])) {

                        $maxSort++;

                        $insertPlatform->execute([$platformName, $maxSort]);

                        $platformId = (int) $db->lastInsertId();

                        $platformMap[$platformName] = $platformId;

                        $result['new_platforms'][] = $platformName;

                    }



                    $platformId = $platformMap[$platformName];

                    $insertLink->execute([$platformId, $title, $url, $description]);

                    $existingUrls[$url] = true;

                    $seenInBatch[$url] = true;



                    $result['added'][] = [

                        'platform' => $platformName,

                        'title' => $title,

                        'url' => $url,

                        'description' => $description,

                    ];

                }



                $_SESSION['batch_result'] = $result;

                redirect('/admin/links.php');

            }

        } catch (Exception $e) {

            $error = $e->getMessage();

        }

    }

}



$platforms = $db->query('SELECT id, name FROM platforms ORDER BY sort_order ASC, id ASC')->fetchAll();

$filterPlatform = $_GET['platform'] ?? 'all';

$searchQ = trim($_GET['q'] ?? '');



$where = [];

$params = [];



if ($filterPlatform !== 'all' && is_numeric($filterPlatform)) {

    $where[] = 'l.platform_id = ?';

    $params[] = (int) $filterPlatform;

}



if ($searchQ !== '') {

    $where[] = '(l.title LIKE ? OR l.url LIKE ? OR l.description LIKE ? OR p.name LIKE ?)';

    $like = '%' . $searchQ . '%';

    $params = array_merge($params, [$like, $like, $like, $like]);

}



$sql = 'SELECT l.*, p.name AS platform_name FROM links l JOIN platforms p ON l.platform_id = p.id';

if ($where) {

    $sql .= ' WHERE ' . implode(' AND ', $where);

}

$sql .= ' ORDER BY l.created_at DESC';



if ($params) {

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $links = $stmt->fetchAll();

} else {

    $links = $db->query($sql)->fetchAll();

}



$totalCount = (int) $db->query('SELECT COUNT(*) FROM links')->fetchColumn();



$queryParams = [];

if ($filterPlatform !== 'all') $queryParams['platform'] = $filterPlatform;

if ($searchQ !== '') $queryParams['q'] = $searchQ;

$queryString = $queryParams ? '?' . http_build_query($queryParams) : '';

?>



<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>



<?php if ($batchResult): ?>

<div class="alert alert-success batch-result-alert">

    <div class="batch-result-summary">

        批量导入完成：成功添加 <strong><?= count($batchResult['added']) ?></strong> 条链接

        <?php if (count($batchResult['new_platforms'])): ?>

        ，新增平台 <strong><?= count($batchResult['new_platforms']) ?></strong> 个

        <?php endif; ?>

        <?php if (count($batchResult['duplicates'])): ?>

        ，跳过重复 <strong><?= count($batchResult['duplicates']) ?></strong> 条

        <?php endif; ?>

        <?php if (count($batchResult['invalid']) + count($batchResult['format_errors'])): ?>

        ，无效数据 <strong><?= count($batchResult['invalid']) + count($batchResult['format_errors']) ?></strong> 条

        <?php endif; ?>

    </div>

    <div class="batch-result-links">

        <?php if (count($batchResult['added']) || count($batchResult['new_platforms'])): ?>

        <button type="button" class="batch-detail-link" data-modal-open="batchAddedModal">查看新增详情</button>

        <?php endif; ?>

        <?php if (count($batchResult['duplicates'])): ?>

        <button type="button" class="batch-detail-link" data-modal-open="batchDupModal">查看重复详情</button>

        <?php endif; ?>

        <?php if (count($batchResult['invalid']) || count($batchResult['format_errors'])): ?>

        <button type="button" class="batch-detail-link" data-modal-open="batchInvalidModal">查看链接错误详情</button>

        <?php endif; ?>

    </div>

</div>

<?php endif; ?>



<div class="panel">

    <div class="panel-header">

        <h3>链接列表</h3>

        <span class="badge">共 <?= $totalCount ?> 条<?php if ($searchQ): ?>，搜索到 <?= count($links) ?> 条<?php endif; ?></span>

    </div>

    <div class="panel-body">

        <div class="toolbar">

            <div class="toolbar-filters">

                <form method="get" class="search-form" id="linkSearchForm">

                    <?php if ($filterPlatform !== 'all'): ?>

                    <input type="hidden" name="platform" value="<?= e($filterPlatform) ?>">

                    <?php endif; ?>

                    <input type="text" name="q" value="<?= e($searchQ) ?>" placeholder="搜索标题、链接、描述、平台..." class="search-input">

                    <button type="submit" class="btn btn-secondary btn-sm"><?= icon('search') ?> 搜索</button>

                    <?php if ($searchQ): ?>

                    <a href="?<?= $filterPlatform !== 'all' ? 'platform=' . (int) $filterPlatform : '' ?>" class="btn btn-sm btn-secondary">清除</a>

                    <?php endif; ?>

                </form>

                <label for="platformFilter">筛选平台</label>

                <select id="platformFilter" onchange="location.href=buildFilterUrl(this.value)">

                    <option value="all" <?= $filterPlatform === 'all' ? 'selected' : '' ?>>全部平台</option>

                    <?php foreach ($platforms as $p): ?>

                    <option value="<?= $p['id'] ?>" <?= $filterPlatform == $p['id'] ? 'selected' : '' ?>>

                        <?= e($p['name']) ?>

                    </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="toolbar-actions">

                <button type="button" class="btn btn-secondary btn-sm" data-modal-open="batchModal">

                    <?= icon('upload') ?> 批量导入

                </button>

                <button type="button" class="btn btn-primary btn-sm" id="btnAddLink"

                    data-modal-open="linkModal"

                    data-fill-form="linkForm"

                    data-action="add"

                    data-id=""

                    data-platform-id="<?= $filterPlatform !== 'all' ? (int) $filterPlatform : '' ?>"

                    data-title=""

                    data-url=""

                    data-description="">

                    <?= icon('plus') ?> 添加链接

                </button>

            </div>

        </div>



        <?php if (empty($links)): ?>

            <p class="empty-text"><?= $searchQ ? '未找到匹配的链接' : '暂无链接' ?></p>

        <?php else: ?>

        <div class="table-wrap">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>标题</th>

                        <th>描述</th>

                        <th>平台</th>

                        <th>链接</th>

                        <th>点击量</th>

                        <th>添加时间</th>

                        <th class="col-actions">操作</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($links as $l): ?>

                    <tr>

                        <td><?= e($l['title']) ?></td>

                        <td class="text-truncate" style="max-width:160px"><?= e($l['description']) ?></td>

                        <td><span class="badge badge-info"><?= e($l['platform_name']) ?></span></td>

                        <td class="text-truncate" style="max-width:180px">

                            <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener"><?= e($l['url']) ?></a>

                        </td>

                        <td><?= (int) ($l['clicks'] ?? 0) ?></td>

                        <td><?= time_ago($l['created_at']) ?></td>

                        <td class="actions">

                            <button type="button" class="btn btn-sm btn-secondary"

                                data-modal-open="linkModal"

                                data-fill-form="linkForm"

                                data-action="edit"

                                data-id="<?= $l['id'] ?>"

                                data-platform-id="<?= $l['platform_id'] ?>"

                                data-title="<?= e($l['title']) ?>"

                                data-url="<?= e($l['url']) ?>"

                                data-description="<?= e($l['description']) ?>">编辑</button>

                            <form method="post" style="display:inline" onsubmit="return confirm('确定删除？')">

                                <?= csrf_field() ?>

                                <input type="hidden" name="action" value="delete">

                                <input type="hidden" name="id" value="<?= $l['id'] ?>">

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

<form method="post" class="form-grid" id="linkForm">

    <?= csrf_field() ?>

    <input type="hidden" name="action" value="add">

    <input type="hidden" name="id" value="" data-field="id">

    <div class="form-group">

        <label>选择平台 *</label>

        <select name="platform_id" required data-field="platform-id">

            <option value="">-- 请选择 --</option>

            <?php foreach ($platforms as $p): ?>

            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="form-group">

        <label>链接标题 *</label>

        <input type="text" name="title" required data-field="title">

    </div>

    <div class="form-group">

        <label>链接地址 *</label>

        <input type="url" name="url" required placeholder="https://" data-field="url">

    </div>

    <div class="form-group">

        <label>描述</label>

        <textarea name="description" rows="3" data-field="description"></textarea>

    </div>

    <div class="form-actions">

        <button type="submit" class="btn btn-primary" id="linkFormSubmit">保存</button>

        <button type="button" class="btn btn-secondary" data-modal-close>取消</button>

    </div>

</form>

<?php

modal_overlay('linkModal', '添加链接', ob_get_clean());

?>



<?php

ob_start();

?>

<form method="post" class="form-grid" id="batchForm">

    <?= csrf_field() ?>

    <input type="hidden" name="action" value="batch_upload">

    <div class="form-hint">

        每行一条，格式：<code>平台,名称,描述,URL</code>（也可用 Tab 分隔）。描述可留空：<code>平台,名称,URL</code>

    </div>

    <div class="form-group">

        <label>导入数据 *</label>

        <textarea name="batch_data" id="batchData" rows="12" placeholder="示例：&#10;GitHub,某项目,开源项目,https://github.com/example&#10;B站,某视频,,https://www.bilibili.com/video/xxx"></textarea>

    </div>

    <div class="form-group">

        <label>或上传 TXT 文件</label>

        <input type="file" id="batchFile" accept=".txt,text/plain">

    </div>

    <div class="form-actions">

        <button type="submit" class="btn btn-primary">开始导入</button>

        <button type="button" class="btn btn-secondary" data-modal-close>取消</button>

    </div>

</form>

<?php

modal_overlay('batchModal', '批量导入链接', ob_get_clean(), 'xl');

?>



<?php if ($batchResult): ?>

<?php

ob_start();

?>

<?php if (count($batchResult['new_platforms'])): ?>

<h4 class="detail-section-title">新增平台</h4>

<ul class="detail-list">

    <?php foreach ($batchResult['new_platforms'] as $name): ?>

    <li><?= e($name) ?></li>

    <?php endforeach; ?>

</ul>

<?php endif; ?>

<?php if (count($batchResult['added'])): ?>

<h4 class="detail-section-title">新增链接</h4>

<div class="table-wrap">

    <table class="data-table data-table-sm">

        <thead><tr><th>平台</th><th>名称</th><th>链接</th></tr></thead>

        <tbody>

        <?php foreach ($batchResult['added'] as $item): ?>

        <tr>

            <td><?= e($item['platform']) ?></td>

            <td><?= e($item['title']) ?></td>

            <td class="text-truncate" style="max-width:200px"><?= e($item['url']) ?></td>

        </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php else: ?>

<p class="empty-text">无新增链接</p>

<?php endif; ?>

<?php

modal_overlay('batchAddedModal', '新增详情', ob_get_clean(), 'xl');

?>



<?php

ob_start();

?>

<?php if (count($batchResult['duplicates'])): ?>

<div class="table-wrap">

    <table class="data-table data-table-sm">

        <thead><tr><th>行号</th><th>平台</th><th>名称</th><th>链接</th></tr></thead>

        <tbody>

        <?php foreach ($batchResult['duplicates'] as $item): ?>

        <tr>

            <td><?= (int) $item['line'] ?></td>

            <td><?= e($item['platform']) ?></td>

            <td><?= e($item['title']) ?></td>

            <td class="text-truncate" style="max-width:200px"><?= e($item['url']) ?></td>

        </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php else: ?>

<p class="empty-text">无重复链接</p>

<?php endif; ?>

<?php

modal_overlay('batchDupModal', '重复链接详情', ob_get_clean(), 'xl');

?>



<?php

ob_start();

?>

<?php if (count($batchResult['invalid']) || count($batchResult['format_errors'])): ?>

<div class="table-wrap">

    <table class="data-table data-table-sm">

        <thead><tr><th>行号</th><th>平台</th><th>名称</th><th>链接</th><th>原因</th></tr></thead>

        <tbody>

        <?php foreach ($batchResult['format_errors'] as $item): ?>

        <tr>

            <td><?= (int) $item['line'] ?></td>

            <td colspan="2" class="text-truncate" style="max-width:200px"><?= e($item['raw']) ?></td>

            <td>—</td>

            <td>格式错误</td>

        </tr>

        <?php endforeach; ?>

        <?php foreach ($batchResult['invalid'] as $item): ?>

        <tr>

            <td><?= (int) $item['line'] ?></td>

            <td><?= e($item['platform']) ?></td>

            <td><?= e($item['title']) ?></td>

            <td class="text-truncate" style="max-width:160px"><?= e($item['url']) ?></td>

            <td><?= e($item['reason']) ?></td>

        </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php else: ?>

<p class="empty-text">无错误数据</p>

<?php endif; ?>

<?php

modal_overlay('batchInvalidModal', '链接错误详情', ob_get_clean(), 'xl');

?>

<?php endif; ?>



<script>

function buildFilterUrl(platform) {

    const params = new URLSearchParams();

    if (platform !== 'all') params.set('platform', platform);

    const q = document.querySelector('.search-input')?.value?.trim();

    if (q) params.set('q', q);

    const qs = params.toString();

    return qs ? '?' + qs : '?';

}



document.querySelectorAll('[data-fill-form="linkForm"]').forEach(btn => {

    btn.addEventListener('click', () => {

        const isEdit = btn.getAttribute('data-action') === 'edit';

        document.querySelector('#linkModal .modal-header h3').textContent = isEdit ? '编辑链接' : '添加链接';

        document.getElementById('linkFormSubmit').textContent = isEdit ? '保存修改' : '添加链接';

    });

});



const batchFile = document.getElementById('batchFile');

const batchData = document.getElementById('batchData');

if (batchFile && batchData) {

    batchFile.addEventListener('change', () => {

        const file = batchFile.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = (e) => {

            batchData.value = e.target.result;

        };

        reader.readAsText(file, 'UTF-8');

    });

}

</script>



<?php require_once __DIR__ . '/includes/footer.php'; ?>


