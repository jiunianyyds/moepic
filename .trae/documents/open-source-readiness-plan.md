# 萌图云 MoePic 开源仓库规范修复计划

## Context

项目准备上传至 GitHub，需要以开源审查员视角检查并补齐规范开源仓库所需的文档、配置与代码质量。当前已排除敏感配置并清理 git 历史，但仍缺少许可证、Composer 元数据、贡献指南、安全策略等关键文件，且 README、nginx.conf、.htaccess 存在不准确或无效的配置。

## Recommended Approach

按优先级分阶段补齐以下内容，所有新增/修改文件均在一次提交中完成。

### 高优先级（必须修复）

1. **新增 LICENSE（MIT）**
   - 路径：`d:\myCode\moepic\LICENSE`
   - 内容：标准 MIT 许可证，署名 `Copyright (c) 2026 玖念`
   - 理由：开源仓库必须明确许可条款。

2. **新增 composer.json**
   - 路径：`d:\myCode\moepic\composer.json`
   - 内容：声明项目元数据、MIT 许可证、PHP >= 8.0、必需的扩展（pdo、pdo_mysql、gd）
   - 理由：PHP 项目行业标准，提供依赖与平台要求声明。

3. **增强 .gitignore**
   - 路径：`d:\myCode\moepic\.gitignore`
   - 内容：在现有基础上增加 IDE、系统文件、日志、.env、Composer vendor/lock 等排除项
   - 理由：防止无关文件被误提交。

4. **修正 README.md**
   - 路径：`d:\myCode\moepic\README.md`
   - 修改点：
     - 目录结构图中 `config/database.php` → `config/database.example.php`
     - 安装步骤增加 `cp config/database.example.php config/database.php`
     - 环境要求 PHP 7.1+ → PHP 8.0+（代码已使用 match 等 PHP 8 语法）
     - 许可证段落明确为 MIT 并链接 LICENSE 文件
   - 理由：避免用户找不到配置入口，纠正版本误导，明确许可证。

5. **修复 nginx.conf**
   - 路径：`d:\myCode\moepic\nginx.conf`
   - 修改点：
     - `try_files /uploads/index.php?f=$1 =404;` 改为 `try_files /uploads/$1 /image.php?f=$1;`
     - 补充有效拒绝规则保护 config、logs 等敏感目录
   - 理由：当前 rewrite 指向不存在的文件，Nginx 部署下图片会 404。

6. **修复 .htaccess**
   - 路径：`d:\myCode\moepic\.htaccess`
   - 修改点：
     - 移除在 .htaccess 上下文中无效的 `<Directory>` 与 `<DirectoryMatch>`
     - 使用 `RewriteRule ^uploads/.*\.php$ - [F,L]` 禁止上传目录执行 PHP
     - 使用 `RewriteRule ^config/ - [F,L]` 与 `RewriteRule ^thumbs/uploads/ - [F,L]` 保护敏感目录
     - 保留 `<FilesMatch "\.(sql|env|log)$">` 等有效规则
   - 理由：无效指令会导致安全规则实际不生效。

### 中优先级（建议补齐）

7. **新增 SECURITY.md**
   - 路径：`d:\myCode\moepic\SECURITY.md`
   - 内容：支持版本、漏洞报告方式、响应时间、已实施安全措施

8. **新增 CHANGELOG.md**
   - 路径：`d:\myCode\moepic\CHANGELOG.md`
   - 内容：采用 Keep a Changelog 格式，记录 1.0.0 初始版本

9. **新增 CONTRIBUTING.md**
   - 路径：`d:\myCode\moepic\CONTRIBUTING.md`
   - 内容：Fork/分支/PR 流程、环境要求、禁止提交 config/database.php

10. **新增 .editorconfig**
    - 路径：`d:\myCode\moepic\.editorconfig`
    - 内容：PHP 4 空格、JS/CSS/HTML 2 空格、UTF-8、LF、末尾空行

11. **新增 .gitattributes**
    - 路径：`d:\myCode\moepic\.gitattributes`
    - 内容：文本文件自动换行、LF 规范化、database.sql 语言识别

## Critical Files

- `d:\myCode\moepic\README.md`
- `d:\myCode\moepic\LICENSE`
- `d:\myCode\moepic\composer.json`
- `d:\myCode\moepic\.gitignore`
- `d:\myCode\moepic\nginx.conf`
- `d:\myCode\moepic\.htaccess`
- `d:\myCode\moepic\SECURITY.md`
- `d:\myCode\moepic\CHANGELOG.md`
- `d:\myCode\moepic\CONTRIBUTING.md`
- `d:\myCode\moepic\.editorconfig`
- `d:\myCode\moepic\.gitattributes`

## Verification

1. `git ls-files` 应包含新增文件，且不包含 `config/database.php`、`uploads/`、`install.lock`
2. `git log --oneline` 应显示新增提交 `chore(repo): 完善开源仓库标准文档与配置`
3. README 中不应再出现 `config/database.php` 的直接编辑指引（除 `cp` 命令）
4. nginx.conf 的 `try_files` 应指向 `/image.php?f=$1`
5. .htaccess 中不再包含 `<Directory>` / `<DirectoryMatch>` 指令
