# 📡 API 接口文档

LinkHub 对外暴露轻量级 JSON API，供前端页面调用。所有接口均**无需鉴权**（后台操作走 Session），返回统一结构。

---

## 🧾 目录

- [通用约定](#通用约定)
- [接口列表](#接口列表)
  - [1. 搜索 /api/search.php](#1-搜索-api-searchphp)
  - [2. 点击统计 /api/track.php](#2-点击统计-api-trackphp)
  - [3. Frame 检查 /api/frame-check.php](#3-frame-检查-apiframe-checkphp)
- [错误响应](#错误响应)
- [数据表字段参考](#数据表字段参考)

---

## 通用约定

### 基础路径

```
http(s)://你的域名/api/
```

### 返回格式

所有接口返回 `application/json; charset=utf-8`，统一顶层结构：

```json
{
  "success": true,
  "data":    {},
  "message": ""
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| `success` | boolean | 请求是否成功 |
| `data` | mixed | 业务数据（成功时） |
| `message` | string | 错误描述（失败时） |

### HTTP 状态码

| 码 | 含义 |
|----|------|
| 200 | 正常 |
| 400 | 参数不合法 |
| 405 | 请求方法不允许 |
| 410 | 接口已废弃 |

### 字符集

全链路 `utf8mb4`，Emoji 安全。

---

## 接口列表

### 1. 搜索 `/api/search.php`

前台搜索框的核心接口。按关键词模糊匹配链接的 **标题 / URL / 描述 / 平台名**，支持按平台过滤。

#### 请求

```
GET /api/search.php?q={keyword}[&scope=all|{platform_id}]
```

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| `q` | string | ✅ | — | 搜索关键词，空串返回空数组 |
| `scope` | string | ❌ | `all` | `all` 或平台 ID (整数) |

#### 请求示例

```bash
# 搜索"抖音"，限定在平台 ID=5 内
curl "http://localhost/api/search.php?q=%E6%8A%96%E9%9F%B3&scope=5"

# 搜索"AI"，跨全部平台
curl "http://localhost/api/search.php?q=AI"

# 空关键词（前端清空时会调用，返回空数组）
curl "http://localhost/api/search.php?q="
```

#### 响应示例

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "platform_id": 5,
      "title": "抖音网页版",
      "url": "https://www.douyin.com",
      "description": "短视频分享平台",
      "clicks": 1234,
      "created_at": "2026-10-01 12:00:00",
      "updated_at": "2026-10-10 08:30:00",
      "platform_name": "短视频",
      "platform_logo": "/uploads/platforms/douyin.png"
    }
  ],
  "total": 1
}
```

#### 实现要点

- 最大返回 **50 条**（SQL 硬编码 `LIMIT 50`）
- 排序：`created_at DESC`（最新在前）
- 搜索命中任何一个字段即返回
- 每次调用自动记录一条 `search_logs`（见 `Stats::recordSearch()`）

---

### 2. 点击统计 `/api/track.php`

用户点击外链时前端异步上报，用于累加 `links.clicks` 或 `carousels.clicks`。

#### 请求

```
POST /api/track.php
Content-Type: application/json   # 也支持 application/x-www-form-urlencoded
```

Body（JSON）：

```json
{
  "type": "link",
  "id": 1
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `type` | string | ✅ | `link`（链接）或 `carousel`（轮播图） |
| `id` | int | ✅ | 对应表的主键 ID，需大于 0 |

#### 请求示例

```bash
# 链接点击
curl -X POST http://localhost/api/track.php \
  -H "Content-Type: application/json" \
  -d '{"type":"link","id":42}'

# 轮播图点击
curl -X POST http://localhost/api/track.php \
  -H "Content-Type: application/json" \
  -d '{"type":"carousel","id":3}'
```

#### 成功响应

```json
{ "success": true }
```

#### 失败响应

```json
{ "success": false, "message": "Invalid request" }
```

#### 前端调用示例（原生 JS）

```javascript
async function trackClick(type, id) {
    try {
        await fetch('/api/track.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, id }),
            keepalive: true   // 页面跳转时也保证发送
        });
    } catch (e) { /* 静默失败，不影响跳转 */ }
}

// 使用
trackClick('link', 42);
window.location.href = 'https://example.com';
```

---

### 3. Frame 检查 `/api/frame-check.php`

> ⚠️ **该接口已于近期停用**，返回 HTTP 410。原设计用于检测目标站点是否允许被 iframe 嵌入，现在前台外链直接通过"离开本站提醒"弹窗保护用户安全。

**当前响应**

```json
{ "error": "此接口已停用" }
```

前端可忽略 410 状态码。如需重新启用，请在 `api/frame-check.php` 中实现业务逻辑。

---

## 错误响应

| HTTP | 场景 | 响应体 |
|------|------|--------|
| 200 | 正常 | `{ success: true, data: ... }` |
| 400 | 参数非法 | `{ success: false, message: "Invalid request" }` |
| 405 | GET 接口收到 POST（反之亦然） | `{ success: false, message: "Method not allowed" }` |
| 410 | 接口已废弃 | `{ error: "此接口已停用" }` |
| 500 | 服务器异常（数据库未就绪、内部错误） | Nginx / PHP 默认错误页 |

> 所有 SQL 查询均使用 PDO 预编译，不会因异常输入触发 SQL 注入。

---

## 数据表字段参考

### links（链接）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | INT PK | 主键 |
| `platform_id` | INT FK | 所属平台 |
| `title` | VARCHAR(200) | 标题 |
| `url` | VARCHAR(500) UNIQUE | 链接地址 |
| `description` | TEXT | 简介 |
| `clicks` | INT | 点击数 |
| `created_at` / `updated_at` | TIMESTAMP | 时间戳 |

### platforms（平台）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | INT PK | 主键 |
| `name` | VARCHAR(100) | 平台名 |
| `logo` | VARCHAR(255) | Logo 相对路径 |
| `description` | TEXT | 描述 |
| `sort_order` | INT | 排序值（越小越靠前） |

### carousels（轮播图）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | INT PK | 主键 |
| `image` | VARCHAR(255) | 图片路径 |
| `link_url` | VARCHAR(500) | 点击跳转地址 |
| `description` | VARCHAR(255) | 说明（显示在图片下方） |
| `is_active` | TINYINT(1) | 1=启用 |
| `sort_order` | INT | 排序值 |
| `clicks` | INT | 点击数 |

### visit_stats（访问统计）

| 字段 | 类型 | 说明 |
|------|------|------|
| `visit_date` | DATE PK | 日期 |
| `visit_count` | INT | 当日访问次数 |

### search_logs（搜索日志）

| 字段 | 类型 | 说明 |
|------|------|------|
| `keyword` | VARCHAR(100) | 搜索关键词 |
| `created_at` | TIMESTAMP | 搜索时间 |

---

## 二次开发：新增 API

**推荐在 `api/` 下新建独立文件**，例如 `api/stats-overview.php`：

```php
<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// 你的业务逻辑 ...
$totalLinks = (int) $db->query('SELECT COUNT(*) FROM links')->fetchColumn();

json_response([
    'success' => true,
    'data' => ['total_links' => $totalLinks],
]);
```

前端调用：

```javascript
fetch('/api/stats-overview.php')
    .then(r => r.json())
    .then(res => console.log(res.data.total_links));
```

### 开发注意事项

1. **始终引入 `bootstrap.php`** —— 确保 Session、Database、Settings 都已就绪
2. **使用 `json_response()`** —— 统一输出 + 自动 `exit`
3. **全部 SQL 用预编译** —— `$stmt->prepare()` + `bindValue()`
4. **敏感参数校验** —— `is_numeric()` 检查 ID，`filter_var` 检查 URL
5. **前端 keepalive** —— 页面跳转前上报的接口加 `keepalive: true`

---

<p align="center">API 简单够用，有更复杂的需求欢迎提 Issue 讨论 ✨</p>
