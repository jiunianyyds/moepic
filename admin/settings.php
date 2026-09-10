<?php
/**
 * 萌图云 MoePic - 管理后台 · 站点设置
 */
require_once __DIR__ . '/../config/core.php';
$admin = require_admin();
$msg = '';

function set_setting(string $k, string $v): void {
    $st = db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=?');
    $st->execute([$k, $v, $v]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_setting('site_name', trim($_POST['site_name'] ?? ''));
    set_setting('site_subtitle', trim($_POST['site_subtitle'] ?? ''));
    set_setting('site_description', trim($_POST['site_description'] ?? ''));
    set_setting('announcement', trim($_POST['announcement'] ?? ''));
    set_setting('allow_register', (int)($_POST['allow_register'] ?? 0) ? '1' : '0');
    set_setting('allow_guest_upload', (int)($_POST['allow_guest_upload'] ?? 0) ? '1' : '0');
    set_setting('max_upload_mb', (int)($_POST['max_upload_mb'] ?? 10));
    // 清除设置缓存
    $GLOBALS['_settings'] = null;
    $msg = '设置已保存 ✨';
}
// 重新读取最新值
function gs(string $k, string $d=''): string { return setting($k, $d); }
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>站点设置 - 管理后台</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/admin/">📊 仪表盘</a>
  <a href="/admin/images.php">🖼️ 图片管理</a>
  <a href="/admin/users.php">👥 用户管理</a>
  <a href="/admin/settings.php" class="active">⚙️ 站点设置</a>
  <a href="/admin/logs.php">📜 访问日志</a>
  <a href="/user/">🏠 返回前台</a>
</aside>
<section>
  <div class="card" style="max-width:720px">
    <h2>⚙️ 站点设置</h2>
    <?php if($msg):?><div class="alert alert-ok"><?= $msg ?></div><?php endif;?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="field"><label>站点名称</label><input name="site_name" value="<?= e(gs('site_name')) ?>" required></div>
      <div class="field"><label>站点副标题</label><input name="site_subtitle" value="<?= e(gs('site_subtitle')) ?>"></div>
      <div class="field"><label>站点描述(SEO)</label><textarea name="site_description" rows="2" style="width:100%;padding:10px;border-radius:12px;border:2px solid var(--pink-light);background:var(--cream)"><?= e(gs('site_description')) ?></textarea></div>
      <div class="field"><label>📌 首页公告</label><textarea name="announcement" rows="3" style="width:100%;padding:10px;border-radius:12px;border:2px solid var(--pink-light);background:var(--cream)"><?= e(gs('announcement')) ?></textarea></div>
      <div class="split">
        <div class="field"><label>允许注册</label>
          <select name="allow_register" style="width:100%;padding:11px;border-radius:12px;border:2px solid var(--pink-light);background:var(--cream)">
            <option value="1" <?= gs('allow_register','1')==='1'?'selected':'' ?>>开启</option>
            <option value="0" <?= gs('allow_register','1')==='0'?'selected':'' ?>>关闭</option>
          </select>
        </div>
        <div class="field"><label>允许游客上传</label>
          <select name="allow_guest_upload" style="width:100%;padding:11px;border-radius:12px;border:2px solid var(--pink-light);background:var(--cream)">
            <option value="0" <?= gs('allow_guest_upload','0')==='0'?'selected':'' ?>>关闭(需登录)</option>
            <option value="1" <?= gs('allow_guest_upload','0')==='1'?'selected':'' ?>>开启</option>
          </select>
        </div>
        <div class="field"><label>单文件大小上限 (MB)</label><input type="number" name="max_upload_mb" min="1" max="100" value="<?= e(gs('max_upload_mb','10')) ?>"></div>
      </div>
      <button class="btn btn-primary">保存设置 💾</button>
    </form>
  </div>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
</body></html>
