# 贡献指南

感谢你对 萌图云 MoePic 的贡献兴趣！

## 如何贡献

1. **Fork** 本仓库
2. 从 `main` 分支创建你的功能分支：`git checkout -b feature/你的功能名`
3. 提交改动：`git commit -m "feat: 简短描述"`
4. 推送到你的 Fork：`git push origin feature/你的功能名`
5. 在 GitHub 发起 Pull Request

## 环境要求

- PHP >= 8.0
- MySQL 5.7+ / MariaDB
- PDO MySQL、GD 扩展
- Apache 或 Nginx

## 开发注意事项

- 请勿将 `config/database.php` 提交到仓库（已加入 `.gitignore`）
- 本地开发时复制模板：`cp config/database.example.php config/database.php`
- 保持代码风格一致（参见 `.editorconfig`）
- 新增功能请同步更新 README 与 CHANGELOG

## 报告问题

请使用 GitHub Issue，并尽量提供：
- 问题描述
- 复现步骤
- 环境信息（PHP 版本、MySQL 版本、Web 服务器）
- 相关日志或截图

## 安全漏洞

请勿公开提交安全 Issue，请参照 [SECURITY.md](SECURITY.md) 报告。
