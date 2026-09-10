# Changelog

本文件遵循 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/) 格式，版本号遵循 [Semantic Versioning](https://semver.org/lang/zh-CN/)。

## [Unreleased]

## [1.0.0] - 2026-09-10

### Added
- 初始发布：二次元可爱风 PHP 图床系统
- 支持拖拽 / 粘贴 / 选择文件上传
- 用户注册、登录、个人中心、API 密钥管理
- 管理员后台：仪表盘、图片管理、用户管理、站点设置、访问日志
- 自动生成缩略图、防盗链、访问统计
- 开放上传 / 删除 API，支持 PicGo 等工具
- Apache `.htaccess` 与 Nginx `nginx.conf` 参考配置

### Security
- PDO 预处理、XSS 转义、CSRF 校验、上传文件三重校验
- 上传目录禁止执行 PHP
- Session Cookie 加固
