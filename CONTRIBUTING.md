# 🤝 贡献指南 CONTRIBUTING

首先，感谢你有兴趣为 LinkHub 做出贡献！本项目欢迎一切形式的帮助：Bug 报告、功能建议、代码 PR、文档勘误、截图美化。

下面的规则帮助我们保持项目质量和协作效率。请花 5 分钟读完。

---

## 🧾 目录

- [行为准则](#行为准则)
- [我能做什么](#我能做什么)
- [环境准备](#环境准备)
- [开发规范](#开发规范)
- [提交流程](#提交流程)
- [Commit 信息规范](#commit-信息规范)
- [Pull Request 模板](#pull-request-模板)
- [Issue 模板](#issue-模板)
- [发布流程](#发布流程)
- [致谢](#致谢)

---

## 行为准则

> 简版：尊重、友善、建设性。

- 尊重不同观点和背景的贡献者
- 攻击、歧视、辱骂零容忍
- 就事论事，不要进行人身攻击
- 接受批评、积极改进
- 不发布违法、侵权、隐私相关内容

违反者会被 maintainer 直接关闭 Issue / PR 并拉黑。

---

## 我能做什么

| 类型 | 示例 | 需要技能 |
|------|------|---------|
| 🐛 Bug 报告 | 某操作 500 / 样式错乱 | 复现步骤 + 截图 |
| 💡 功能建议 | 添加友情链接导出 | 产品思维 |
| 🔧 代码 PR | 新增接口、优化 SQL | PHP / JS |
| 📖 文档改进 | 安装步骤不清楚 | 中文表达清晰 |
| 🎨 UI / UX | 首页搜索框交互优化 | CSS / 设计审美 |
| 🌐 国际化 | 英文翻译 | 中英双语 |
| 🧪 测试用例 | 安全场景回归 | 细心 + 复现 |

---

## 环境准备

### 本地快速搭建

```bash
# 1. 克隆仓库
git clone https://github.com/your-username/linkhub.git
cd linkhub

# 2. 复制数据库模板
cp config/database.example.php config/database.php
# 手动创建数据库 link_manager

# 3. 内置 PHP 服务器
php -S localhost:8080 -t .

# 4. 浏览器访问
# 首次访问 http://localhost:8080/install/ 完成安装
# 然后打开 http://localhost:8080/admin/login.php 进入后台
```

### 代码编辑器推荐

VS Code + 以下插件：
- **PHP Intelephense** — PHP 智能提示
- **PHP CS Fixer** — 代码格式化
- **ESLint** — JS 检查
- **CSS Peek** — CSS 跳转

---

## 开发规范

### PHP（核心规范）

基于 **PSR-12**，额外约束：

- **缩进 4 空格**，不使用 Tab
- 控制结构花括号**同行**（`function foo() {` 而非换行）
- 纯函数文件顶部不加 `namespace`（本项目不是 Composer 自动加载）
- 类文件每个文件一个类，类名与文件名一致（`Database.php` → `Database`）
- 字符串统一用 **双引号** `"hello $name"`
- 所有输出到 HTML 的内容**必须**用 `e()` 转义
- 所有 SQL 一律预编译 + 绑定参数，严禁拼接
- 数组声明用 `[]` 短语法

✅ 好的示例：
```php
$stmt = $db->prepare('SELECT * FROM links WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo e($row['title']);
```

❌ 坏的示例（禁止）：
```php
$sql = "SELECT * FROM links WHERE id = $id";   // 拼接！
$row = $db->query($sql)->fetch();
echo $row['title'];                              // 未转义！
```

### CSS

- 使用 **CSS 变量** 实现主题切换：`--variable-name`
- 类名用 **kebab-case**：`.hot-link-card`
- 避免 `!important`，实在必要写注释说明原因
- 媒体查询放在对应组件下方，不要集中在文件底部

### JavaScript

- 原生 ES6+，**禁止**引入 jQuery / Vue / React
- 变量名用 **camelCase**，常量用 **UPPER_SNAKE_CASE**
- 异步操作用 `async / await`
- 全局变量挂在 `window.LinkHub` 命名空间下
- DOM 操作前先 `document.readyState === 'loading' ? addEventListener('DOMContentLoaded', ...) : fn()`

### SQL

- 建表语句 `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
- 时间戳统一用 `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- 外键命名不显式写（保持 schema.sql 风格一致）
- 索引按查询字段命名：`idx_keyword`、`idx_created`

### Git

- **分支命名**：`feat/xxx`、`fix/xxx`、`docs/xxx`、`refactor/xxx`
- 不要把 `config/database.php`、`uploads/` 等敏感文件加入 git
- 提交前跑 `git diff --cached` 确认改动范围

---

## 提交流程

### 1. Fork & Clone

1. 点击 GitHub 右上角 **Fork**
2. 克隆到本地并添加 upstream：

```bash
git clone https://github.com/你的用户名/linkhub.git
cd linkhub
git remote add upstream https://github.com/LinkHub/linkhub.git
git remote -v
```

### 2. 创建分支

```bash
git fetch upstream
git checkout -b feat/my-new-feature upstream/main
```

### 3. 开发 + 本地验证

```bash
# 启动内置服务器
php -S localhost:8080 -t .

# 跑一遍你改动的场景
# 前台：首页、搜索、主题切换
# 后台：登录、你修改的模块
```

### 4. 提交

按 [Commit 信息规范](#commit-信息规范) 写 message：

```bash
git add <你修改的文件>
git commit -m "feat(admin): add bulk export button for links"
```

### 5. 保持最新

```bash
git fetch upstream
git rebase upstream/main
# 如有冲突：解决 → git add . → git rebase --continue
```

### 6. 推送 & 发 PR

```bash
git push origin feat/my-new-feature
```

在 GitHub 打开仓库，会看到 **"Compare & pull request"** 按钮，点击填写 PR 模板后提交。

---

## Commit 信息规范

采用 **Conventional Commits** 风格：

```
<type>(<scope>): <subject>

<body>（可选）

<footer>（可选，如 Fixes #123）
```

### Type 列表

| Type | 说明 |
|------|------|
| `feat` | 新功能 |
| `fix` | Bug 修复 |
| `docs` | 文档 |
| `style` | 样式微调 / 空格 |
| `refactor` | 重构（不改行为） |
| `perf` | 性能优化 |
| `test` | 测试 |
| `build` | 构建 / 依赖 |
| `ci` | CI 配置 |
| `chore` | 杂项（格式化、脚本等） |

### Scope 示例

`admin`、`api`、`frontend`、`install`、`db`、`settings`、`auth`

### 好的提交示例

```
feat(api): add stats-overview endpoint

- returns total links, platforms, visits
- caches query result for 5 min
- document in API.md

Closes #88
```

### 坏的提交示例

```
update
fix bug
修改样式
```

---

## Pull Request 模板

> 你在 GitHub 发 PR 时建议包含以下信息（可手动复制）：

```markdown
## 改动类型
- [ ] 新功能 (feat)
- [ ] Bug 修复 (fix)
- [ ] 文档更新 (docs)
- [ ] 重构 (refactor)
- [ ] 样式 / UI (style)
- [ ] 性能优化 (perf)

## 描述
<!-- 简要说明改动了什么、为什么 -->

## 关联 Issue
<!-- 如有：Fixes #123, Refs #456 -->

## 测试验证
<!-- 描述你如何本地验证改动 -->
- [ ] 前台首页正常
- [ ] 后台管理页正常
- [ ] 搜索功能正常
- [ ] 无 PHP Warning / Notice

## 截图（如涉及 UI）
<!-- 前后对比截图 -->

## 自检清单
- [ ] 所有 SQL 均使用预编译
- [ ] 所有 HTML 输出已用 e() 转义
- [ ] 未引入 config/database.php 等敏感文件
- [ ] 已更新对应文档（如有）
```

---

## Issue 模板

### Bug 报告

```markdown
## 环境
- OS: Windows / Linux / macOS
- PHP 版本:
- MySQL 版本:
- LinkHub 版本:

## 复现步骤
1. 打开 xxx
2. 点击 yyy
3. 发生 zzz

## 期望行为
应该...

## 实际行为
却...

## 截图 / 日志
<!-- 贴出截图或 PHP error log -->
```

### 功能建议

```markdown
## 需求描述
清晰描述你想要的功能

## 使用场景
这个功能用来解决什么问题？

## 实现思路（可选）
你对实现方式有什么想法？

## 参考（可选）
类似功能的链接 / 截图
```

---

## 发布流程

> Maintainer 专用，贡献者可跳过。

1. 更新 `README.md` 的版本 badge 和更新日志
2. 本地跑一遍基础验证
3. 打 tag：
   ```bash
   git checkout main
   git pull origin main
   git tag -a v1.0.0 -m "Release v1.0.0"
   git push origin main
   git push origin v1.0.0
   ```
4. 在 GitHub 上创建 Release，填写 Changelog
5. 同步更新 `DEPLOYMENT.md` 升级章节的版本引用

### 版本号语义

遵循 **SemVer**：`MAJOR.MINOR.PATCH`

- `MAJOR` — 不兼容的 API / 结构变更
- `MINOR` — 新增功能，向后兼容
- `PATCH` — Bug 修复、优化，向后兼容

---

## 致谢

每一位贡献者都会出现在 `CONTRIBUTORS.md` 和 Release Note 里。

> "如果没有社区，开源就是一个孤独的工程。" —— 不知名开发者

**再次感谢你！** 🎉

---

<p align="center">Happy Hacking ✨</p>
