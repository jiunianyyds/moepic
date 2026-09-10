<?php
/**
 * 萌图云 MoePic - 入口引导(所有页面 require 此文件)
 */
define('MOEPIC_ROOT', __DIR__);
define('SITE_PATH', dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '');

// 统一时区为北京时间(与 MySQL 会话时区保持一致,避免时间显示偏差)
date_default_timezone_set('Asia/Shanghai');

// ===== 安装锁检测:未安装则跳转到安装向导(安装页本身除外) =====
$moepicLockFile = dirname(__DIR__) . '/install.lock';
$moepicScript   = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!file_exists($moepicLockFile) && $moepicScript !== 'install.php') {
    header('Location: ' . rtrim(SITE_PATH, '/') . '/install.php');
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

session_start_safe();

// 首次运行自动建表(若表不存在),部署零门槛
try {
    db()->query('SELECT 1 FROM settings LIMIT 1');
} catch (Exception $e) {
    try { db_install_if_needed(); } catch (Exception $ex) { /* 交给安装页处理 */ }
}

// 统一错误级别:生产环境不暴露细节
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

// 全局 CSRF 令牌(供模板直接 echo)
$GLOBALS['_csrf'] = csrf_token();
