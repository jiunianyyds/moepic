<?php
/**
 * 萌图云 MoePic - 删除图片接口
 * GET/POST ?id=xxx  (需登录,仅本人或管理员可删)
 */
require_once __DIR__ . '/../config/core.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') csrf_check($_GET['csrf'] ?? null);
else csrf_check();
$user = require_login();
$id = (int)($_REQUEST['id'] ?? 0);
if (!$id) json(['ok'=>false,'msg'=>'参数错误'],400);

$st = db()->prepare('SELECT * FROM images WHERE id=?');
$st->execute([$id]); $img = $st->fetch();
if (!$img) json(['ok'=>false,'msg'=>'图片不存在'],404);
if ($img['user_id'] != $user['id'] && !is_admin($user)) json(['ok'=>false,'msg'=>'无权删除'],403);

// 删文件
foreach ([UPLOAD_DIR.'/'.$img['stored_name'], __DIR__.'/../'.$img['thumbpath']] as $f) {
    if ($f && file_exists($f)) @unlink($f);
}
db()->prepare('DELETE FROM images WHERE id=?')->execute([$id]);
log_action('delete', $id);
json(['ok'=>true,'msg'=>'已删除']);
