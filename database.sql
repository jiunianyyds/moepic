-- ============================================================
-- 萌图云 MoePic - 二次元可爱风图床系统 数据库结构
-- 数据库名: moepic (导入前请先在 phpMyAdmin 创建该数据库)
-- 字符集: utf8mb4 / 排序: utf8mb4_general_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- users 用户表 ----------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(32)  NOT NULL COMMENT '登录用户名',
  `password`     VARCHAR(255) NOT NULL COMMENT 'password_hash 加密后密码',
  `email`        VARCHAR(120) DEFAULT '' COMMENT '邮箱',
  `avatar`       VARCHAR(255) DEFAULT '' COMMENT '头像地址',
  `role`         TINYINT NOT NULL DEFAULT 0 COMMENT '0=普通用户 1=管理员',
  `status`       TINYINT NOT NULL DEFAULT 1 COMMENT '1=正常 0=禁用',
  `api_key`      VARCHAR(64)  DEFAULT '' COMMENT 'API 上传密钥',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';

-- ---------- images 图片表 ----------
DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者,0=游客',
  `filename`     VARCHAR(255) NOT NULL COMMENT '原始文件名',
  `stored_name`  VARCHAR(255) NOT NULL COMMENT '存储随机文件名',
  `filepath`     VARCHAR(255) NOT NULL COMMENT '相对存储路径',
  `thumbpath`    VARCHAR(255) DEFAULT '' COMMENT '缩略图路径',
  `mime`         VARCHAR(64)  DEFAULT '' COMMENT 'MIME 类型',
  `size`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  `width`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '宽',
  `height`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '高',
  `url`          VARCHAR(500) NOT NULL COMMENT '访问URL',
  `is_public`    TINYINT NOT NULL DEFAULT 1 COMMENT '是否公开(用于首页展示)',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='图片表';

-- ---------- settings 站点设置表 ----------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `k` VARCHAR(64) NOT NULL,
  `v` TEXT,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='站点设置(KV)';

INSERT INTO `settings` (`k`,`v`) VALUES
('site_name','萌图云 MoePic'),
('site_subtitle','一个可爱到冒泡的二次元图床 (´∀`)'),
('site_description','上传、分享、管理你的每一张萌图，支持拖拽 / 粘贴 / API 上传。'),
('announcement','欢迎来到萌图云 (๑•́ ₃ •̀๑) 新用户注册即送无限上传额度，请遵守社区规范哦～'),
('allow_register','1'),
('allow_guest_upload','0'),
('max_upload_mb','10'),
('default_theme','auto');

-- ---------- upload_logs 上传/访问日志表 ----------
DROP TABLE IF EXISTS `upload_logs`;
CREATE TABLE `upload_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL DEFAULT 0,
  `ip`         VARCHAR(45) DEFAULT '',
  `ua`         VARCHAR(500) DEFAULT '',
  `referer`    VARCHAR(500) DEFAULT '',
  `image_id`   INT UNSIGNED DEFAULT 0,
  `action`     VARCHAR(20) DEFAULT 'upload' COMMENT 'upload/view',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='访问与上传日志';

SET FOREIGN_KEY_CHECKS = 1;
