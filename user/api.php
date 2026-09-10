<?php
/**
 * 萌图云 MoePic - API 密钥管理
 */
require_once __DIR__ . '/../config/core.php';
$user = require_login();
$msg = '';

if (isset($_GET['reset'])) {
    csrf_check($_GET['csrf'] ?? null);
    $new = bin2hex(random_bytes(24));
    db()->prepare('UPDATE users SET api_key=? WHERE id=?')->execute([$new, $user['id']]);
    $user['api_key'] = $new;
    $msg = 'API Key 已重新生成 🔄';
}
if (empty($user['api_key'])) {
    $user['api_key'] = bin2hex(random_bytes(24));
    db()->prepare('UPDATE users SET api_key=? WHERE id=?')->execute([$user['api_key'], $user['id']]);
}
$base = rtrim(SITE_URL, '/');
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>API 密钥 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/user/">🖼️ 我的图片</a>
  <a href="/user/profile.php">⚙️ 账号设置</a>
  <a href="/user/api.php" class="active">🔑 API 密钥</a>
  <?php if(is_admin($user)):?><a href="/admin/">🛡️ 管理后台</a><?php endif;?>
  <a href="/logout.php" style="color:#ff6b81">🚪 退出</a>
</aside>
<section>
  <div class="card">
    <h2>🔑 API 上传密钥</h2>
    <p class="text-muted">使用 API Key 可在脚本 / 图床工具中免登录上传(如 PicGo、ShareX)。</p>
    <?php if($msg):?><div class="alert alert-ok"><?= $msg ?></div><?php endif;?>
    <div class="link-row">
      <input type="text" value="<?= e($user['api_key']) ?>" readonly>
      <button class="btn btn-sm btn-primary copy-btn" onclick="copyText('<?= e($user['api_key']) ?>')">复制</button>
      <a class="btn btn-sm btn-danger" href="?reset=1&csrf=<?= csrf_token() ?>" onclick="return confirm('重置后旧 Key 立即失效,确定?')">重置</a>
    </div>

    <h3 style="margin:20px 0 8px">📤 上传示例</h3>
    <pre class="card" style="background:var(--pink-light);white-space:pre-wrap;font-size:.85rem;overflow:auto">curl -X POST <?= $base ?>/api/upload.php \
  -H "X-API-Key: <?= e(substr($user['api_key'],0,12)) ?>..." \
  -F "file=@/path/to/image.png"</pre>

    <h3 style="margin:20px 0 8px">📥 返回格式</h3>
    <pre class="card" style="background:var(--pink-light);white-space:pre-wrap;font-size:.85rem;overflow:auto">{
  "ok": true,
  "msg": "上传成功",
  "data": {
    "url": "<?= $base ?>/uploads/xxxx.jpg",
    "markdown": "![](<?= $base ?>/uploads/xxxx.jpg)",
    "html": "&lt;img src=\"...\"&gt;",
    "bbcode": "[img]...[/img]"
  }
}</pre>
    <p class="text-muted" style="font-size:.85rem;margin-top:12px">💡 防盗链:可通过 config/functions.php 的 hotlink_check() 开启严格 referer 校验。</p>
  </div>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
<script src="/assets/js/app.js"></script>
</body></html>
