<?php
/**
 * 萌图云 MoePic - 缩略图目录访问入口 (thumbs/index.php)
 * 作用: 防盗链校验 + 输出缩略图
 * 说明: 正常情况下缩略图通过 /uploads/thumb_xxx.jpg 由上层 uploads/index.php 统一处理;
 *       本文件作为 thumbs/ 目录被直接访问时的兜底防护,确保防盗链始终生效。
 */
require_once __DIR__ . '/../config/core.php';

$file = $_GET['f'] ?? '';
$file = str_replace(['../', '\\', '//'], '', $file);
if (!preg_match('/^thumb_[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
    http_response_code(400); exit('Invalid file');
}
$real = UPLOAD_DIR . '/thumbs/' . $file;
if (!file_exists($real)) { http_response_code(404); exit('Not found'); }

// 防盗链
hotlink_check();

$mime = mime_content_type($real) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
header('Cache-Control: public, max-age=2592000');
readfile($real);
exit;
