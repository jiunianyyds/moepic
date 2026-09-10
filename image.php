<?php
/**
 * 萌图云 MoePic - 图片访问入口 (on-demand 缩略图 / 防盗链 / 404)
 *
 * .htaccess / router.php 将磁盘上不存在的 /uploads/xxx.jpg 请求路由到此处 ?f=xxx.jpg
 * 职责:
 *   1. 防盗链校验 (hotlink_check)
 *   2. 按需生成缩略图 (请求 thumb_xxx.png 但磁盘无此文件时, 从原图生成)
 *   3. 真正不存在的文件返回 404
 *
 * 注意: 此文件必须位于 uploads/ 目录之外 (uploads/ 通过 .htaccess 禁止 PHP 执行)。
 */
require_once __DIR__ . '/config/core.php';

$f = $_GET['f'] ?? '';
// 安全:禁止目录穿越 / 空字节
$f = str_replace(['../', '..\\', "\0"], '', $f);
$f = ltrim($f, '/.\\');
if ($f === '' || !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 Not Found');
}

// 防盗链 (默认宽松: 允许空 referer / 本站)
hotlink_check();

$base     = basename($f);
$isThumb  = str_starts_with($base, 'thumb_');
$thumbDir = THUMB_DIR;                       // .../uploads/thumbs
$upDir    = UPLOAD_DIR;                       // .../uploads

if ($isThumb) {
    // 缩略图请求: thumb_xxx.png → 原图 xxx.png
    $thumbPath = $thumbDir . '/' . $base;
    // 已存在则直接输出
    if (is_file($thumbPath)) {
        serve_image($thumbPath);
    }
    // 按需生成: 从原图创建缩略图
    $origName = substr($base, 6);            // 去掉 'thumb_' 前缀
    $origPath = $upDir . '/' . $origName;
    if (is_file($origPath)) {
        // 确保缩略图目录存在
        if (!is_dir($thumbDir)) @mkdir($thumbDir, 0755, true);
        make_thumb($origPath, $thumbPath, 320);
        if (is_file($thumbPath)) {
            serve_image($thumbPath);
        }
    }
    // 原图也不存在 → 404
    not_found();
}

// 原图请求 (文件不存在才路由到此处, 理论上极少触发)
$origPath = $upDir . '/' . $f;
if (is_file($origPath)) {
    serve_image($origPath);
}
not_found();

/* ---------- 输出函数 ---------- */
function serve_image(string $path): void
{
    $info = @getimagesize($path);
    $mime = $info ? $info['mime'] : (@mime_content_type($path) ?: 'application/octet-stream');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=2592000'); // 30 天
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
    readfile($path);
    exit;
}
function not_found(): void
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 Not Found');
}
