# 🌸 萌图云 MoePic

> 一款二次元可爱风的轻量级图床系统，开箱即用、安全可控。

PHP · MySQL · 响应式 · 多功能图床

## 📖 项目简介

**萌图云 MoePic** 是一款专为个人站长设计的动漫风格图床系统，采用经典的 **PHP + MySQL** 技术栈，界面以樱花粉、浅紫、天空蓝为主色调，清新可爱。支持拖拽/粘贴/选择上传、自动缩略图、用户系统、管理员后台、API 接口与可选的图片防盗链等完整功能，适合搭建个人图片托管服务。

- **项目作者**：玖念
- **项目背景**：本项目由 AI 辅助创建，采用开源项目标准规范组织代码与文档，便于二次开发与维护。
- **部署方式**：支持 Apache（`.htaccess`）与 Nginx（`nginx.conf`）两种主流环境，上传即可用。

## ✨ 核心功能

- 🎀 二次元可爱风 UI，樱花粉 / 浅紫 / 天空蓝配色，支持夜间模式
- 📱 完全响应式，PC / 手机自适应
- 🖼️ 三种上传方式：拖拽上传、`Ctrl+V` 粘贴截图、选择文件上传
- 🔐 完整用户系统：注册 / 登录 / 个人中心 / 账号设置，支持游客上传开关
- 🛡️ 管理员后台：仪表盘统计、图片管理、用户管理、站点设置、访问日志
- 📊 自动生成缩略图、上传进度条、图片搜索与分页
- 🔌 开放上传/删除 API，支持 PicGo 等图床工具免登录上传
- 🛡️ 全方位安全防护：SQL 注入、XSS、CSRF、文件上传、Session 加固
- 🚧 可选：图片防盗链（Referer 校验，需调整服务器配置开启）与敏感目录保护

## 🛠️ 技术栈

| 类别 | 技术 |
| ---- | ---- |
| 后端 | PHP 8.0+ |
| 数据库 | MySQL 5.7+ / MariaDB（UTF-8mb4） |
| 前端 | 原生 HTML / CSS / JavaScript（无框架，零依赖） |
| 图片处理 | GD 扩展（缩略图生成、压缩） |
| 服务器 | Apache / Nginx |

## 📂 目录结构

```
moepic/
├── index.php                 # 首页（上传 + 最新图片 + 公告）
├── install.php               # 一键安装向导（★ 安装完成后务必删除）
├── login.php                 # 登录
├── register.php              # 注册
├── logout.php                # 退出登录
├── image.php                 # 图片访问入口（可选防盗链 / 访问统计 / 缩略图按需生成）
├── api/
│   ├── upload.php            # 图片上传接口（POST）
│   └── delete.php            # 图片删除接口
├── config/
│   ├── core.php              # 入口引导（所有页面统一 require）
│   ├── database.php          # 数据库 / 站点基础配置（由 database.example.php 复制生成）
│   ├── database.example.php  # 数据库配置模板（部署前复制为 database.php）
│   └── functions.php         # 公共函数库（Session/CSRF/鉴权/验证）
├── partials/
│   ├── nav.php               # 公共导航栏
│   └── footer.php            # 公共底部
├── user/
│   ├── index.php             # 我的图片（管理 / 搜索 / 删除）
│   ├── profile.php           # 账号设置
│   └── api.php               # API 密钥管理
├── admin/
│   ├── index.php             # 管理后台 · 仪表盘 / 统计
│   ├── images.php            # 管理后台 · 图片管理
│   ├── users.php             # 管理后台 · 用户管理
│   ├── settings.php          # 管理后台 · 站点设置
│   └── logs.php              # 管理后台 · 访问日志
├── assets/
│   ├── css/style.css         # 全站样式
│   ├── js/app.js             # 前端交互（上传 / 反馈）
│   └── images/avatar-default.php
├── uploads/                  # 原图存储目录（部署时需可写）
├── uploads/thumbs/           # 缩略图存储目录（部署时需可写）
├── database.sql              # 数据库结构（UTF-8）
├── .htaccess                 # Apache 伪静态 / 安全规则
└── nginx.conf                # Nginx 重写与安全配置参考
```

## ⚙️ 环境要求

- **PHP** ≥ 8.0，需开启 PDO MySQL 与 GD 扩展
- **数据库** MySQL 5.7+ / MariaDB，需支持 UTF-8mb4
- **Web 服务器** Apache（支持 `.htaccess`）或 Nginx
- 现代浏览器（Chrome / Firefox / Edge / Safari）

## 🚀 安装步骤

### 方式一：一键安装向导（推荐）

1. 将整个 `moepic/` 目录上传至网站根目录（或子目录）。
2. 复制配置模板并填写你的 MySQL 连接信息：

   ```bash
   cp config/database.example.php config/database.php
   ```

   然后编辑 `config/database.php`：

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'moepic');     // 数据库名
   define('DB_USER', 'your_user');  // 数据库用户名
   define('DB_PASS', 'your_pass');  // 数据库密码
   ```

3. 确保以下目录**可写**（权限 755 或 777，视主机而定）：
   - `uploads/`
   - `uploads/thumbs/`

4. 浏览器访问 `https://你的域名/install.php`，按向导完成：
   - 自动建库、建表并写入初始数据
   - 创建管理员账号
5. 安装完成后 **务必删除 `install.php`**。

> 💡 项目内置「首次访问自动建表」机制：即使跳过向导，首次打开任意页面也会在表不存在时自动建表。

### 方式二：手动安装

1. 在 phpMyAdmin 中创建数据库（如 `moepic`，字符集 `utf8mb4`）。
2. 导入 `database.sql`。
3. 复制 `config/database.example.php` 为 `config/database.php`，并修改其中的数据库配置。
4. 设置目录权限，访问首页即可使用。

### Nginx 部署提示

项目已提供 [nginx.conf](nginx.conf) 参考配置，包含图片入口 Rewrite、禁止上传目录执行 PHP、静态资源缓存等规则，按注释将 `root` 与 `fastcgi_pass` 替换为你的实际路径即可。

## � 可选：启用图片防盗链与访问统计

默认配置采用**静态文件优先**策略：图片一旦上传到 `uploads/`，Web 服务器会直接返回文件，以获得最佳性能。因此 `image.php` 中的防盗链校验与访问统计默认**不会**对已有原图生效。

如果你需要启用这些功能，需要调整服务器配置，让所有 `/uploads/` 下图片请求都先经过 `image.php`。

### Apache

编辑 [`.htaccess`](.htaccess)，将图片 Rewrite 规则：

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^uploads/(.+\.(jpg|jpeg|png|gif|webp))$ image.php?f=$1 [L,QSA]
```

改为：

```apache
RewriteRule ^uploads/(.+\.(jpg|jpeg|png|gif|webp))$ image.php?f=$1 [L,QSA]
```

即移除 `RewriteCond %{REQUEST_FILENAME} !-f` 这一行。

### Nginx

编辑 [`nginx.conf`](nginx.conf)，将图片 location：

```nginx
location ~ ^/uploads/(.+\.(jpg|jpeg|png|gif|webp))$ {
    try_files /uploads/$1 /image.php?f=$1;
}
```

改为：

```nginx
location ~ ^/uploads/(.+\.(jpg|jpeg|png|gif|webp))$ {
    rewrite ^/uploads/(.+)$ /image.php?f=$1 last;
}
```

### 注意事项

- 开启后每张图片请求都会进入 PHP，**性能会有所下降**，建议在高并发场景谨慎开启。
- 防盗链的严格程度可在 `config/functions.php` 的 `hotlink_check()` 函数中调整（默认仅对 referer 做宽松校验）。

## � 使用指南

### 前台首页

- **上传图片**：拖拽 / 点击 / `Ctrl+V` 粘贴，支持 JPG、PNG、GIF、WEBP，单文件默认 ≤ 10MB（可在后台设置）。
- 上传成功后自动返回图片链接：原图 URL、Markdown、HTML、BBCode 等多种格式，一键复制。
- 首页展示最新公开图片，支持搜索与查看单图。

### 用户中心（`/user/`）

- **我的图片**：查看、搜索、删除自己上传的图片。
- **账号设置**：修改个人信息与密码。
- **API 密钥**：查看 / 重新生成个人 API Key，用于程序或图床工具上传。

### 管理后台（`/admin/`）

登录管理员账号后即可进入：

- **仪表盘**：站点数据与基础统计。
- **图片管理**：查看 / 删除全站图片。
- **用户管理**：启用 / 禁用用户。
- **站点设置**：站点名称、副标题、SEO 描述、公告，以及是否开放注册、是否允许游客上传、上传大小上限。
- **访问日志**：查看访问与操作记录。

## 🔌 API 文档

### 上传图片

```
POST /api/upload.php
Content-Type: multipart/form-data
```

| 参数 | 位置 | 说明 |
| ---- | ---- | ---- |
| `file` | form-data | 图片文件（必填，JPG/PNG/GIF/WEBP） |

**鉴权方式**（任选其一）：

- 请求头携带 API Key：`X-API-Key: 你的密钥`（推荐，可结合 PicGo 等工具）
- 登录态会话 + CSRF Token（网页端）

**请求示例（curl）**：

```bash
curl -X POST \
  -F "file=@test.png" \
  -H "X-API-Key: 你的API密钥" \
  https://你的域名/api/upload.php
```

**返回示例**：

```json
{
  "ok": true,
  "msg": "success",
  "data": {
    "id": 1,
    "filename": "test.png",
    "url": "https://你的域名/uploads/xxx.png",
    "markdown": "![test](https://你的域名/uploads/xxx.png)",
    "html": "<img src=\"https://你的域名/uploads/xxx.png\">",
    "bbcode": "[img]https://你的域名/uploads/xxx.png[/img]",
    "thumb": "https://你的域名/uploads/thumbs/thumb_xxx.png"
  }
}
```

### 删除图片

```
GET /api/delete.php?id=图片ID&csrf=令牌
POST /api/delete.php?id=图片ID
```

需登录，且仅本人或管理员可删除。成功返回 `{"ok": true, "msg": "已删除"}`。

## 🛡️ 安全设计

- **XSS 防护**：所有输出经 `htmlspecialchars` 转义。
- **SQL 注入防护**：PDO 预处理语句 + 关闭模拟预处理。
- **CSRF 防护**：表单 Token 校验，接口 `csrf_check()` 统一处理。
- **文件上传安全**：MIME + 扩展名双重校验，`getimagesize` 验证图片内容真实性，唯一文件名防猜测。
- **Session 加固**：`HttpOnly` Cookie、`SameSite`、HTTPS 自动启用 `Secure`，登录限速。
- **上传目录禁执行 PHP**：Apache / Nginx 双重保险，杜绝上传 Webshell。
- **可选防盗链**：`image.php` 支持校验 Referer 白名单，但默认静态文件优先策略下不会生效，需手动调整服务器配置开启。
- **敏感文件保护**：`database.sql`、`config/`、日志等均禁止直接访问。

## ❓ 常见问题（FAQ）

**Q1：页面显示 500 错误？**
检查 PHP 版本（≥7.1）以及 GD / PDO MySQL 扩展是否开启，同时确认 `uploads/` 目录可写。

**Q2：上传提示「文件过大」，如何调整上限？**
进入管理后台「站点设置」修改上传大小上限；也可通过环境变量 `MOEPIC_MAX_MB` 覆盖默认值。

**Q3：Nginx 环境下图片 404 或防盗链失效？**
默认配置下，已存在的原图会由 Nginx 直接返回，因此防盗链不会生效。如需启用防盗链，请参考上文「可选：启用图片防盗链与访问统计」修改 `nginx.conf`。

**Q4：如何对接 PicGo 等图床工具？**
在「用户中心 → API 密钥」生成 Key，PicGo 自定义接口地址填 `https://你的域名/api/upload.php` 并使用 `X-API-Key` 请求头即可。

## 📝 许可证

本项目采用 [MIT 许可证](LICENSE) 开源。

在 MIT 许可范围内可自由使用、修改与二次开发，请保留作者署名。

## 💬 关于作者

- **作者**：玖念
- 本项目由 AI 辅助创建，代码与文档遵循开源规范持续维护。
- 如有问题或建议，欢迎提交 Issue 或 Pull Request，共同让 萌图云 MoePic 变得更好~

---

  Made with 💖 &amp; 🌸 for anime lovers.
