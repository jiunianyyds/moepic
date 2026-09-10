<?php
/**
 * 萌图云 MoePic - 图片上传接口 (API)
 * POST /api/upload.php  (form-data: file)
 * 鉴权: 已登录用户 / 或游客(按站点设置) / 或 Header X-API-Key
 * 返回: JSON {ok, msg, data:{url, markdown, html, bbcode, ...}}
 */
require_once __DIR__ . '/../config/core.php';

// 尽力提高内存上限,降低超大图片触发 GD 内存耗尽(Fatal Error→500)的概率
@ini_set('memory_limit', '256M');

// 捕获致命错误:即使 PHP 崩溃也返回可读 JSON,便于排查而非裸 500
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode(['ok' => false, 'msg' => '服务器处理出错: ' . $err['message'] . ' (' . $err['file'] . ':' . $err['line'] . ')'], JSON_UNESCAPED_SLASHES);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json(['ok' => false, 'msg' => '请使用 POST 上传'], 405);

// CSRF(网页端带 cookie 时校验); API Key 走 Authorization 则不依赖 CSRF
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_POST['api_key'] ?? '');
$user = null;
if ($apiKey) {
    // API Key 鉴权(用于程序/图床工具)
    $st = db()->prepare('SELECT * FROM users WHERE api_key=? AND status=1');
    $st->execute([$apiKey]);
    $user = $st->fetch() ?: null;
    if (!$user) json(['ok' => false, 'msg' => 'API Key 无效'], 401);
} else {
    // 网页端:CSRF + Session
    try { csrf_check(); } catch (Exception $e) { json(['ok' => false, 'msg' => 'CSRF 校验失败'], 403); }
    $user = current_user();
    // 游客上传受"站点设置-游客上传"开关控制;已登录用户始终可上传
    if (!$user && setting('allow_guest_upload', '0') !== '1') {
        json(['ok' => false, 'msg' => '游客暂未开放上传功能,请先登录后再试'], 401);
    }
}

// ---------- 接收文件 ----------
$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [UPLOAD_ERR_INI_SIZE=>'文件超过服务器限制', UPLOAD_ERR_FORM_SIZE=>'文件过大', UPLOAD_ERR_NO_FILE=>'未选择文件'];
    json(['ok' => false, 'msg' => $errMap[$file['error']] ?? '上传错误(code ' . ($file['error'] ?? '?') . ')'], 400);
}

// ---------- 严格校验(类型/尺寸/真实性) ----------
$v = validate_image($file['tmp_name'], $file['name']);
if (!$v['ok']) json(['ok' => false, 'msg' => $v['msg']], 400);

// ---------- 准备目录 ----------
if (!is_dir(UPLOAD_DIR))   mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(THUMB_DIR))    mkdir(THUMB_DIR, 0755, true);

// ---------- 生成唯一文件名(防重复/防猜测) ----------
$stored = gen_stored_name($v['ext']);
$dest   = UPLOAD_DIR . '/' . $stored;

// 移动上传文件(原子性 + 只允许来自 PHP 临时目录的文件)
if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $dest)) {
    json(['ok' => false, 'msg' => '文件保存失败,请检查 uploads 目录权限'], 500);
}
@chmod($dest, 0644);

// ---------- 生成缩略图 ----------
$thumbName = 'thumb_' . $stored;
make_thumb($dest, THUMB_DIR . '/' . $thumbName, 320);

// ---------- 入库 ----------
$originName = basename($file['name']);
$url = rtrim(SITE_URL, '/') . '/uploads/' . $stored;
$thumbUrl = rtrim(SITE_URL, '/') . '/uploads/thumbs/' . $thumbName;
$st = db()->prepare('INSERT INTO images(user_id,filename,stored_name,filepath,thumbpath,mime,size,width,height,url,is_public,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())');
$st->execute([
    $user['id'] ?? 0, $originName, $stored,
    'uploads/' . $stored, 'uploads/thumbs/' . $thumbName,
    $v['mime'], $v['size'], $v['width'], $v['height'], $url, 1,
]);
$imageId = db()->lastInsertId();
log_action('upload', $imageId);

// ---------- 组装返回 ----------
$data = [
    'id'         => (int)$imageId,
    'filename'   => $originName,
    'url'        => $url,
    'thumb'      => $thumbUrl,
    'markdown'   => '![' . $originName . '](' . $url . ')',
    'html'       => '<img src="' . $url . '" alt="' . $originName . '">',
    'bbcode'     => '[img]' . $url . '[/img]',
    'size_human' => filesize_human($v['size']),
    'width'      => $v['width'],
    'height'     => $v['height'],
    'delete_url' => rtrim(SITE_URL, '/') . '/api/delete.php?id=' . $imageId . '&csrf=' . csrf_token(),
];
json(['ok' => true, 'msg' => '上传成功', 'data' => $data]);
