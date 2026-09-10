<?php
/**
 * 萌图云 MoePic - 首页
 */
require_once __DIR__ . '/config/core.php';

// 最新公开图片(游客也能看)
$latest = [];
try {
    $st = db()->query('SELECT i.*, u.username FROM images i LEFT JOIN users u ON u.id=i.user_id WHERE i.is_public=1 ORDER BY i.id DESC LIMIT 18');
    $latest = $st->fetchAll();
} catch (Exception $e) {}
$me = current_user();
$announcement = setting('announcement');
$guestUpload = empty($me) && setting('allow_guest_upload', '0') !== '1'; // 游客且未开放游客上传
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= e(setting('site_description')) ?>">
<title><?= e(setting('site_name')) ?> - <?= e(setting('site_subtitle')) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__.'/partials/nav.php'; ?>

<main class="container">
  <?php if ($announcement): ?>
  <div class="announcement">
    <span class="pin">📌</span>
    <span><?= e($announcement) ?></span>
  </div>
  <?php endif; ?>

  <!-- Hero + 上传区 -->
  <section class="hero">
    <h1>
      <span class="deco">🐰</span>
      欢迎来到 <span class="hl"><?= e(setting('site_name')) ?></span>
      <span class="deco">🌸</span>
    </h1>
    <p class="sub"><?= e(setting('site_description')) ?></p>

    <div class="card" style="max-width:680px;margin:0 auto;text-align:left">
      <?php if ($guestUpload): ?>
      <div class="drop disabled" id="dropZone">
        <div class="big">🔒</div>
        <p><b>游客暂未开放上传</b>，请先登录</p>
        <p class="hint">登录后可上传图片,并管理你的每一张萌图</p>
        <p class="hint"><a href="login.php">去登录 →</a> &nbsp;·&nbsp; <a href="register.php">注册账号</a></p>
      </div>
      <?php else: ?>
      <div class="drop" id="dropZone">
        <div class="big">🖼️✨</div>
        <p><b>拖拽图片到这里</b>，或点击选择文件</p>
        <p class="hint">支持 JPG / PNG / GIF / WEBP · 单文件 ≤ <?= MAX_UPLOAD_MB ?>MB</p>
        <p class="hint">💡 小贴士:在任何页面都可以直接 <b>Ctrl+V</b> 粘贴截图上传哦 (´▽`)ﾉ</p>
        <input type="file" id="fileInput" accept="image/*" style="display:none" multiple>
      </div>
      <?php endif; ?>
      <div class="progress" id="progress"><span></span></div>
      <div id="result" class="result"></div>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    </div>
  </section>

  <!-- 最新图片 -->
  <h2 class="section-title">🆕 最新上传 <span class="text-muted" style="font-size:.9rem;font-weight:400">共 <?= count($latest) ?> 张</span></h2>
  <?php if ($latest): ?>
  <div class="grid">
    <?php foreach ($latest as $img): ?>
    <div class="gallery-card">
      <a href="<?= e($img['url']) ?>" target="_blank">
        <img class="lazy" data-src="<?= e($img['url']) ?>" alt="<?= e($img['filename']) ?>" loading="lazy">
      </a>
      <button class="btn btn-sm btn-primary card-copy" type="button" data-copy="<?= e($img['url']) ?>" title="复制图片链接">🔗 复制</button>
      <div class="meta">
        <span>@<?= e($img['username'] ?: '游客') ?></span>
        <span><?= time_ago($img['created_at']) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty card">还没有图片,成为第一个上传的人吧 ฅ•ω•ฅ</div>
  <?php endif; ?>

  <!-- 特色功能介绍 -->
  <h2 class="section-title">🌟 特色功能</h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
    <?php foreach([
      ['🖱️','拖拽上传','把图片拖进区域即可秒传'],
      ['📋','粘贴上传','Ctrl+V 直接传截图'],
      ['🌙','夜间模式','护眼萌系深色主题'],
      ['🔗','多格式链接','Markdown / HTML / BBCode'],
      ['🛡️','安全可靠','防注入 · 防XSS · 防盗链'],
      ['📊','API 接入','程序化上传 & 管理'],
    ] as $f): ?>
    <div class="card" style="text-align:center">
      <div style="font-size:2.2rem"><?= $f[0] ?></div>
      <b><?= $f[1] ?></b>
      <p class="text-muted" style="font-size:.88rem"><?= $f[2] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__.'/partials/footer.php'; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
