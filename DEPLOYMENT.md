# 🔧 部署指南 DEPLOYMENT

本页覆盖 LinkHub 在不同环境下的部署操作：**宝塔面板**、**Linux 手动**、**Docker**（可选）、**HTTPS 配置**、**性能调优**。

---

## 🧾 目录

- [一、环境检查](#一环境检查)
- [二、宝塔面板部署（最常用）](#二宝塔面板部署最常用)
- [三、Linux 服务器手动部署](#三linux-服务器手动部署)
- [四、Nginx 完整配置示例](#四nginx-完整配置示例)
- [五、Apache 配置说明](#五apache-配置说明)
- [六、HTTPS 启用](#六https-启用)
- [七、安全加固](#七安全加固)
- [八、性能调优](#八性能调优)
- [九、备份与迁移](#九备份与迁移)
- [十、升级流程](#十升级流程)

---

## 一、环境检查

部署前请在服务器执行：

```bash
php -v            # 需要 8.0+
php -m | grep -E 'pdo_mysql|mbstring|openssl|fileinfo'
mysql --version   # 或 mariadb --version
```

PHP 推荐 `8.1` 或 `8.2`（PHP 8.0 已于 2022 年底停止主动维护）。

### php.ini 关键项

```ini
upload_max_filesize = 20M
post_max_size       = 20M
max_execution_time  = 120
memory_limit        = 256M
date.timezone       = Asia/Shanghai
```

---

## 二、宝塔面板部署（最常用）

适合小白，全程图形化。

### 2.1 创建站点

1. **网站 → 添加站点**
2. 域名填入你的域名（如 `nav.example.com`）
3. 根目录选择 `/www/wwwroot/nav.example.com`
4. PHP 版本选 **8.1 / 8.2**
5. 数据库：MySQL 版本选 **5.7** 或 **8.0**，点击"创建"，记下库名、用户名、密码

### 2.2 上传源码

- **方式 A**：终端执行 `cd /www/wwwroot/nav.example.com && git clone https://github.com/your-username/linkhub.git .`
- **方式 B**：本地打包 ZIP → 宝塔 **文件** 面板上传 → 解压

> ⚠️ 如果用 Git clone，注意末尾有个 `.` 代表克隆到当前目录。

### 2.3 目录权限

宝塔 → **文件** → 右键目录 → **权限**：

| 目录 | 权限 | 所有者 |
|------|------|--------|
| `config/` | 755 | www |
| `uploads/` | 755 | www |
| `assets/` | 755 | root |
| 其余 | 755 | root |

### 2.4 Nginx 伪静态

网站 → 设置 → 伪静态，粘贴：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 2.5 打开安装向导

浏览器访问 `http://你的域名/install/`，按向导填写数据库信息（Step 1 的库主机通常是 `127.0.0.1`，端口 `3306`）。

---

## 三、Linux 服务器手动部署

### 3.1 安装 LEMP（以 Ubuntu 22.04 为例）

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-gd php8.1-intl git
```

### 3.2 创建数据库

```bash
sudo mysql -u root -p <<'SQL'
CREATE DATABASE link_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'linkhub'@'localhost' IDENTIFIED BY 'ChangeMeStrongP@ssword';
GRANT ALL ON link_manager.* TO 'linkhub'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 3.3 部署源码

```bash
sudo mkdir -p /var/www/linkhub
sudo chown $USER:$USER /var/www/linkhub
git clone https://github.com/your-username/linkhub.git /var/www/linkhub
```

### 3.4 配置目录权限

```bash
sudo chown -R www-data:www-data /var/www/linkhub/{config,uploads}
sudo chmod -R 755 /var/www/linkhub/{config,uploads}
```

### 3.5 配置 Nginx

创建 `/etc/nginx/sites-available/linkhub.conf`（见第四节完整示例），然后：

```bash
sudo ln -s /etc/nginx/sites-available/linkhub.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 3.6 完成

浏览器打开域名 → 走 `/install/` 向导即可。

---

## 四、Nginx 完整配置示例

保存为 `/etc/nginx/sites-available/linkhub.conf`：

```nginx
server {
    listen 80;
    server_name nav.example.com;
    root /var/www/linkhub;
    index index.php index.html;

    # 默认字符集
    charset utf-8;

    # 前台入口
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 禁止直接访问敏感目录
    location ~* ^/(config|includes)/ {
        deny all;
        return 404;
    }

    # 安装完成后禁止 install 目录
    location ~* ^/install/ {
        if (-f $document_root/config/installed.lock) {
            return 404;
        }
        try_files $uri $uri/ /install/index.php?$query_string;
    }

    # PHP-FPM 处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
        return 404;
    }
}
```

---

## 五、Apache 配置说明

项目根目录已自带 `.htaccess`，完成三件事：

1. 禁止直接访问 `config/`、`includes/` 目录
2. 安装完成后自动屏蔽 `install/` 目录
3. 其它请求走 `index.php`（兜底）

Apache 只需确认 `mod_rewrite` 已启用：

```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

虚拟主机配置 `AllowOverride All`：

```apache
<VirtualHost *:80>
    ServerName nav.example.com
    DocumentRoot /var/www/linkhub
    <Directory /var/www/linkhub>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/linkhub-error.log
    CustomLog ${APACHE_LOG_DIR}/linkhub-access.log combined
</VirtualHost>
```

---

## 六、HTTPS 启用

### 方式 A：宝塔一键申请

网站 → 设置 → **SSL** → Let's Encrypt → 勾选域名 → 申请 → 开启强制 HTTPS。

### 方式 B：Certbot（Ubuntu / Debian）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d nav.example.com
```

Certbot 会自动修改 Nginx 配置，添加 443 监听与自动续期。

### 方式 C：自签名证书（仅本地开发）

```bash
openssl req -x509 -newkey rsa:2048 -nodes -keyout key.pem -out cert.pem -days 365
# Nginx ssl_certificate /path/to/cert.pem;
# Nginx ssl_certificate_key /path/to/key.pem;
```

---

## 七、安全加固

### 7.1 生产环境必做

- [x] `config/installed.lock` 存在（禁止重装）
- [x] `install/` 目录被 Nginx / `.htaccess` 屏蔽
- [x] 数据库密码强复杂度（≥ 16 位，含大小写 / 数字 / 特殊字符）
- [x] 管理员密码 ≥ 12 位
- [x] HTTPS 已启用
- [x] 禁止 PHP 在 `uploads/` 目录执行（自带 `.htaccess` 已覆盖）

### 7.2 额外措施

#### 禁用 PHP 危险函数

`php.ini`：
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

#### 开启 HttpOnly Cookie

在 `includes/bootstrap.php` 的 `session_start()` 前加：
```php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
```

#### 数据库定期备份

```bash
# 每日凌晨 3 点自动备份
0 3 * * * mysqldump -u linkhub -p'密码' link_manager | gzip > /var/backup/linkhub-$(date +\%F).sql.gz
```

---

## 八、性能调优

### 8.1 MySQL 参数建议

编辑 `/etc/mysql/mysql.conf.d/mysqld.cnf`：

```ini
[mysqld]
innodb_buffer_pool_size = 256M     # 小机器用物理内存 25%-50%
innodb_log_file_size    = 64M
query_cache_type        = 0        # MySQL 8.0 已移除
max_connections         = 100
character-set-server    = utf8mb4
collation-server        = utf8mb4_unicode_ci
```

### 8.2 PHP-FPM 进程池

小服务器（2C2G）推荐：

```ini
pm = dynamic
pm.max_children      = 20
pm.start_servers     = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.process_idle_timeout = 10s
```

### 8.3 页面缓存（可选）

由于系统未内置缓存层，大流量场景可在 Nginx 层加：

```nginx
proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=linkhub:10m max_size=1G inactive=60m;

server {
    # ...
    location ~ \.php$ {
        fastcgi_pass ...;
        fastcgi_cache linkhub;
        fastcgi_cache_valid 200 302 10m;
        fastcgi_cache_key "$scheme$request_method$host$request_uri";
    }
}
```

> 注意：搜索接口 (`/api/*`) 和后台页面不应走缓存。

---

## 九、备份与迁移

### 9.1 备份清单

```bash
# 1. 数据库
mysqldump -u linkhub -p link_manager > db-backup.sql

# 2. 用户上传文件
tar -czf uploads-backup.tar.gz uploads/

# 3. 配置文件
cp config/database.php config/installed.lock config-backup/
```

### 9.2 迁移到新服务器

1. 按第二节 / 第三节完成新服务器环境搭建（**不访问 `/install/`**）
2. 上传源码 + 覆盖 `config/database.php`（改新数据库凭据）
3. 导入数据库：`mysql -u xxx -p link_manager < db-backup.sql`
4. 同步 `uploads/` 目录
5. 确认 `config/installed.lock` 存在
6. 访问首页 → 完成

---

## 十、升级流程

```bash
# 1. 备份
cp -r config config.bak.$(date +%Y%m%d)
mysqldump -u linkhub -p link_manager > db-pre-upgrade.sql

# 2. 拉取最新代码
git pull origin main

# 3. 如果有 schema 变更，bootstrap.php 会自动执行 ensure_database_migrations()
# 一般无需手动执行 SQL

# 4. 权限复查
sudo chown -R www-data:www-data config uploads

# 5. 刷新浏览器验证
```

> 跨大版本升级时请先阅读对应 Release Note。

---

## 十一、打赏支持

可以请喝杯咖啡吗

| 支付宝 | 微信 |
| --- | --- |
| ![支付宝](img/支付宝.png) | ![微信](img/微信.png) |

---

## ❓ 部署 FAQ

| 症状 | 可能原因 | 解决 |
|------|---------|------|
| 首页 502 | PHP-FPM 未启动 / socket 路径错 | `sudo systemctl status php8.1-fpm`，检查 `fastcgi_pass` |
| 安装向导 Step1 报"数据库连接失败" | 主机 / 端口 / 权限错 | 宝塔内通常主机用 `127.0.0.1` 而非 `localhost`；MySQL 用户需允许本机连接 |
| Step2 完后报"无法写入 config/installed.lock" | 目录权限 | 改 `config/` 为 755 + www 所有者 |
| 图片上传成功但前端不显示 | `uploads/` 目录路径不对 | 确认 `uploads/` 在网站根目录下 |
| 后台登录后被踢出 | Session 存储不可写 | 检查 `/tmp` 权限，或 php-fpm 的 `session.save_path` |
| Nginx 日志大量 "open() failed" | 静态资源缓存路径不存在 | 创建 `/var/cache/nginx` 或关闭 fastcgi_cache |

---

<p align="center">部署顺利后，恭喜你拥有了自己的导航站 🎉</p>
