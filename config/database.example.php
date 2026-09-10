<?php
/**
 * 萌图云 MoePic - 数据库配置（模板）
 * ★ 复制本文件为 database.php 并填写你自己的信息。
 *   注意：config/database.php 已被 .gitignore 排除，请勿将真实密码提交到仓库。
 */
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);          // ← 数据库端口,默认 3306
define('DB_USER', 'root');        // ← 改成你的数据库用户名
define('DB_PASS', '');            // ← 改成你的数据库密码
define('DB_NAME', 'moepic');      // ← 数据库名(需先创建)
define('DB_CHAR', 'utf8mb4');

/**
 * 站点基础配置
 * SITE_URL 建议留空,系统自动识别;有 CDN/反代时再手动填
 */
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('UPLOAD_DIR',   __DIR__ . '/../uploads');   // 原图目录(绝对路径)
define('THUMB_DIR',    __DIR__ . '/../uploads/thumbs'); // 缩略图目录
define('MAX_UPLOAD_MB', (int)getenv('MOEPIC_MAX_MB') ?: 10);
define('SESSION_NAME', 'moepic_sid');

/* ---------------- 数据库单例连接 ---------------- */
function db(): PDO
{
    static $pdo;
    if ($pdo) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR;
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // 原生预处理,防 SQL 注入
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
    // 会话时区与 PHP 保持一致(北京时间),保证 NOW()/CURRENT_TIMESTAMP 写入正确
    try { $pdo->exec("SET time_zone = '+08:00'"); } catch (Exception $e) { /* 部分受限主机不允许,忽略 */ }
    return $pdo;
}

/* 首次访问自动建表(仅当表不存在时,安全可重复执行) */
function db_install_if_needed(): void
{
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    db()->exec($sql);
}