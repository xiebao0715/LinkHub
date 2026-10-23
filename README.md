<div align="center">

# 🔗 链接管理平台 LinkHub

> 轻量级开源导航 / 链接聚合系统，零依赖单文件部署，内置搜索、统计与后台管理

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![Installer](https://img.shields.io/badge/Installer-Web%20Wizard-success.svg)](#-安装部署)

[功能特性](#-功能特性) · [技术栈](#-技术栈) · [快速安装](#-快速安装) · [目录结构](#-目录结构) · [API 接口](#-api-接口) · [截图预览](#-截图预览) · [贡献指南](#-贡献指南) · [开源协议](#-开源协议)

</div>

---

## ✨ 功能特性

### 前台展示
- 🏠 **极简首页** — 响应式布局，支持深色 / 浅色主题一键切换
- 🎠 **轮播图 Banner** — 后台可管理，支持点击跳转
- 🔍 **全站搜索** — 按链接标题、描述、URL、平台名模糊检索
- 🎯 **平台过滤** — 下拉选择平台，缩小搜索范围
- 🔥 **热门资源** — 自动按创建时间排序，支持分页
- 📋 **推荐 / 热搜词** — 后台自定义关键词引导搜索
- 📢 **公告弹窗** — 支持启用 / 禁用、自定义标题与正文
- 🚪 **外跳提醒** — 离开本站前弹窗二次确认，保护用户安全
- 🔗 **友情 / 导航 / 协议链接** — 底部三大板块可独立配置
- 📊 **访问 / 搜索统计** — 后台 Dashboard 可视化展示

### 后台管理
- 🔐 **登录鉴权** — Session + `password_hash` 加密存储
- 👥 **管理员账号** — 安装时初始化，可在数据库修改密码
- 🏷️ **平台管理** — 增删改 + 排序 + Logo 上传
- 🔗 **链接管理** — 批量导入（Tab / 逗号分隔）、单条编辑、按平台筛选
- 🖼️ **轮播图管理** — 排序、启停、图片上传、跳转链接
- ⚙️ **站点设置** — 名称、Logo、ICP、版权、主题、公告开关等
- 📈 **数据看板** — 总访问、今日访问、搜索日志、链接总量
- 📤 **上传管理** — 支持图片上传到 `uploads/` 目录，自动按类型分文件夹

### 技术亮点
- 🧩 **零框架依赖** — 原生 PHP + PDO，无需 Composer
- 🔒 **SQL 注入防护** — 全部使用预编译语句 (`prepare` / `bindValue`)
- 🌐 **全 UTF-8** — 数据库 `utf8mb4`，支持 Emoji
- 🔄 **安装向导** — 浏览器访问 `/install/` 自动引导两步完成
- 🛡️ **CSRF 保护** — 内置 `csrf_token()` / `verify_csrf()` 辅助函数
- 📦 **单入口自动迁移** — `bootstrap.php` 自动补齐缺失表结构

---

## 🧱 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端语言 | **PHP 8.0+** | 原生开发，无框架 |
| 数据库 | **MySQL 5.7+ / MariaDB 10.3+** | PDO 连接，utf8mb4 字符集 |
| 前端样式 | **原生 CSS3** | CSS Variables 实现主题切换 |
| 前端交互 | **原生 JavaScript (ES6+)** | 无 jQuery / Vue 依赖 |
| 图标库 | **Font Awesome** | CDN 引入 |
| Web 服务器 | **Nginx / Apache / 宝塔** | 支持伪静态规则 |

---

## 📋 环境要求

- PHP **8.0 或更高**（推荐 8.1+）
- PHP 扩展：`pdo_mysql`、`mbstring`、`openssl`、`fileinfo`
- MySQL **5.7+** 或 MariaDB **10.3+**
- Web 服务器：Nginx / Apache / OpenLiteSpeed
- 目录写权限：`config/`、`uploads/`

---

## 🚀 快速安装

### 方式一：Web 安装向导（推荐）

1. **下载并上传源码**到网站根目录

   ```bash
   git clone https://github.com/your-username/linkhub.git
   # 或下载 ZIP 解压到网站目录
   ```

2. **配置 Web 服务器伪静态**

   **Nginx：**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

   **Apache：** 根目录自带 `.htaccess`，开箱即用

3. **设置目录权限**
   ```bash
   chmod -R 755 config uploads
   chown -R www:www config uploads   # 宝塔 / 面板环境
   ```

4. **浏览器访问** `http://你的域名/install/`

5. 按向导填写：
   - **Step 1**：数据库主机、端口、库名、用户名、密码（库不存在会自动创建）
   - **Step 2**：站点名称、管理员账号、管理员密码

6. 安装完成后自动跳转首页 🎉

> ⚠️ 安装完成后请保留 `config/installed.lock`，不要删除！如需重装，删除该文件即可重新进入向导。

### 方式二：手动部署

```bash
# 1. 创建数据库
mysql -u root -p -e "CREATE DATABASE link_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. 导入表结构
mysql -u root -p link_manager < install/schema.sql

# 3. 复制配置文件
cp config/database.example.php config/database.php
# 编辑 database.php 填入真实数据库连接信息

# 4. 创建管理员账号（替换 NEW_PASSWORD 为你的密码）
ADMIN_PASS=$(php -r "echo password_hash('NEW_PASSWORD', PASSWORD_DEFAULT);")
mysql -u root -p link_manager -e "INSERT INTO admins (username, password) VALUES ('admin', '$ADMIN_PASS');"

# 5. 写入安装锁
date '+%Y-%m-%d %H:%M:%S' > config/installed.lock

# 6. 完成！访问 http://你的域名/
```

### 本地开发（快速体验）

```bash
php -S localhost:8080 -t .
# 然后访问 http://localhost:8080/install/
```

---

## 📁 目录结构

```
linkhub/
├── admin/                      # 后台管理模块
│   ├── includes/               # 后台公共组件（header / footer / modal）
│   ├── dashboard.php           # 仪表盘首页
│   ├── links.php               # 链接管理
│   ├── platforms.php           # 平台管理
│   ├── carousels.php           # 轮播图管理
│   ├── settings.php            # 站点设置
│   ├── login.php               # 登录页
│   └── logout.php              # 登出处理
├── api/                        # 前端 API 接口（JSON）
│   ├── search.php              # 搜索接口
│   ├── track.php               # 点击统计接口
│   └── frame-check.php         # X-Frame-Options 检查
├── assets/                     # 静态资源
│   ├── css/
│   │   ├── app.css             # 前台样式
│   │   ├── admin.css           # 后台样式
│   │   └── install.css         # 安装向导样式
│   └── js/
│       ├── app.js              # 前台交互
│       └── admin.js            # 后台交互
├── config/                     # 运行时配置（敏感，已 gitignore）
│   ├── database.example.php    # 数据库配置模板 ⭐ 保留
│   ├── .gitkeep                # git 占位
│   ├── database.php            # 实际数据库配置（不入库）
│   └── installed.lock          # 安装完成标记（不入库）
├── includes/                   # 核心类与全局函数
│   ├── bootstrap.php           # 自动加载入口
│   ├── helpers.php             # 全局辅助函数（e / redirect / csrf / upload_url ...）
│   ├── icons.php               # 图标辅助
│   ├── Database.php            # PDO 单例
│   ├── Auth.php                # 鉴权（登录 / 登出 / 检查）
│   ├── Settings.php            # 站点设置读写
│   ├── Upload.php              # 文件上传
│   └── Stats.php               # 访问 / 搜索统计
├── install/                    # 安装向导
│   ├── index.php               # Web 向导入口
│   ├── schema.sql              # 数据库建表 SQL
│   └── schema.php              # 表结构迁移 / 自动补齐
├── uploads/                    # 用户上传文件（已 gitignore，保留 .gitkeep）
│   ├── .htaccess               # 禁止执行 PHP
│   └── (site / platforms / carousels/)
├── .htaccess                   # Apache 伪静态
├── .gitignore                  # Git 忽略规则
├── index.php                   # 前台首页入口
└── README.md
```

### 数据库表结构一览

| 表名 | 用途 |
|------|------|
| `settings` | 站点全局配置（键值对） |
| `admins` | 后台管理员账号 |
| `platforms` | 资源平台分类 |
| `links` | 具体链接条目 |
| `carousels` | 首页轮播图 |
| `footer_sections` | 底部三大板块（nav / friend / agreement） |
| `footer_links` | 底部板块内的链接 |
| `visit_stats` | 每日访问计数 |
| `search_logs` | 搜索关键词日志 |

完整建表 SQL 见 [install/schema.sql](./install/schema.sql)。

---

## 🔌 API 接口

所有 API 返回 `application/json` 格式，无需鉴权（仅内部前端调用）。

### 1. 搜索接口

```
GET /api/search.php?q=关键词&scope=all|{platform_id}
```

**请求示例**
```
GET /api/search.php?q=抖音&scope=5
```

**响应示例**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "platform_id": 5,
      "title": "抖音网页版",
      "url": "https://www.douyin.com",
      "description": "短视频平台",
      "clicks": 1234,
      "created_at": "2026-10-01 12:00:00",
      "platform_name": "短视频",
      "platform_logo": "/uploads/platforms/douyin.png"
    }
  ],
  "total": 1
}
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `q` | string | ✅ | 搜索关键词，空则返回空数组 |
| `scope` | string | ❌ | `all` 或平台 ID；默认 `all` |

### 2. 点击统计接口

```
POST /api/track.php
```

前端点击外链时调用，自动累加链接 `clicks` 计数。

### 3. Frame 检查接口

```
GET /api/frame-check.php?url={url}
```

判断目标页面是否允许被 iframe 嵌入（辅助外部链接安全提示）。

> 详细 API 文档见 [API.md](./API.md)。

---

## 💾 数据安全

### 提交到 Git 之前请务必确认：

- [x] `config/database.php` 未被提交（真实数据库密码）
- [x] `config/installed.lock` 未被提交
- [x] `uploads/` 下的用户上传文件未被提交
- [x] `.gitignore` 已就绪

本仓库的 `.gitignore` 已默认覆盖以上场景。

### 管理员密码加密

系统使用 PHP 内置 `password_hash($pass, PASSWORD_DEFAULT)` 生成哈希，验证通过 `password_verify()` 完成。如需重置管理员密码，可直接在 MySQL 中：

```sql
-- 先在命令行生成新密码哈希
-- php -r "echo password_hash('NEW_PASS', PASSWORD_DEFAULT);"

UPDATE admins SET password = '这里粘贴哈希值' WHERE username = 'admin';
```

### CSRF 防护

后台表单默认使用 `csrf_field()` 生成隐藏 Token，提交时调用 `verify_csrf()` 校验。如果你在二次开发时新增了 POST 表单，请务必加上这两步。

---

## 🖼️ 截图预览

| 前台首页 | 搜索结果 | 后台仪表盘 |
|---------|---------|-----------|
| 极简导航页，支持主题切换 | 实时模糊匹配 | 访问 / 链接 / 搜索总览 |

> 建议在 README 中补入实际截图路径（如 `screenshots/home.png`），发布前自行替换。

---

## ❓ 常见问题 FAQ

### Q1: 访问首页自动跳转 `/install/` 怎么办？
说明系统尚未安装。按 [快速安装](#-快速安装) 流程执行即可。已安装后仍跳转，检查 `config/installed.lock` 是否存在。

### Q2: 安装时报错"无法写入 config/installed.lock"？
**宝塔 / 面板用户常见问题**：`config/` 目录权限不足。
- 目录权限改为 **755**，所有者改为 **www**
- `uploads/` 目录同样处理
- 刷新安装页会自动完成

### Q3: 打开后台提示 404？
检查伪静态规则是否生效。Nginx 用户确认 `try_files` 配置，Apache 用户确认 `.htaccess` 未被禁用。

### Q4: 图片上传失败？
检查 `uploads/` 目录写权限。系统会自动创建 `uploads/site/`、`uploads/platforms/`、`uploads/carousels/` 三个子目录。

### Q5: 如何重置管理员密码？
见上方 [数据安全 - 管理员密码加密](#-数据安全) 章节。

### Q6: 支持 PHP 7.x 吗？
不支持。最低要求 PHP 8.0（使用了 `str_contains`、命名参数等新语法）。建议升级到 8.1+。

### Q7: 如何重新安装？
删除 `config/installed.lock` 和 `config/database.php`，浏览器重新访问 `/install/` 即可。数据库表结构如残留可手动清空。

---

## 📚 扩展开发

### 新增一个 API 端点

在 `api/` 下创建新 PHP 文件，开头引入 bootstrap：

```php
<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

// 业务逻辑 ...

json_response(['success' => true, 'data' => $result]);
```

### 新增一个后台管理页面

复制 `admin/links.php` 的结构：引入 bootstrap + `Auth::requireLogin()` + `Settings::getAdminTheme()` + `admin_header()` / `admin_footer()`。

### 主题样式开发

前台主题切换基于 HTML `data-theme` 属性：

```css
:root[data-theme="light"] { --bg: #fff; --text: #222; }
:root[data-theme="dark"]  { --bg: #1a1a1a; --text: #eee; }
```

后台同理，在 `assets/css/admin.css` 中扩展即可。

---

## 🤝 贡献指南

欢迎 Issue / PR！详见 [CONTRIBUTING.md](./CONTRIBUTING.md)。

开发流程简述：

1. Fork 本仓库
2. 创建功能分支 `git checkout -b feature/amazing-feature`
3. 本地测试（`php -S localhost:8080 -t .`）
4. 提交 `git commit -m 'feat: add xxx'`
5. 推送 `git push origin feature/amazing-feature`
6. 发起 Pull Request

### 代码规范

- **PHP**：遵循 PSR-12 基础规范，缩进 4 空格
- **SQL**：一律使用预编译参数绑定，严禁拼接
- **前端**：原生 JS，避免引入大型框架；注释清晰
- **安全**：任何输出都经过 `e()` 转义

---

## 📄 开源协议

本项目基于 **MIT License** 开源 — 详见 [LICENSE](./LICENSE) 文件。

你可以自由使用、修改、分发、商用，但需保留原始版权声明。

---

## ❤️ 致谢

- 原生 PHP 社区 —— 证明了"不依赖框架也能写出好看的产品"
- Font Awesome —— 开箱即用的图标
- 所有提交 Issue / PR 的开源贡献者

---

<p align="center">Made with ❤️ in China</p>
