<?php
/**
 * 萌图云 MoePic - 用户中心 / 我的图片
 */
require_once __DIR__ . '/../config/core.php';
$user = require_login();

// 删除操作(GET,带CSRF)
if (isset($_GET['del'])) {
    csrf_check($_GET['csrf'] ?? null);
    $id = (int)$_GET['del'];
    $st = db()->prepare('SELECT * FROM images WHERE id=? AND user_id=?');
    $st->execute([$id, $user['id']]);
    $img = $st->fetch();
    if ($img) {
        foreach ([UPLOAD_DIR.'/'.$img['stored_name'], __DIR__.'/../'.$img['thumbpath']] as $f) {
            if ($f && file_exists($f)) @unlink($f);
        }
        db()->prepare('DELETE FROM images WHERE id=?')->execute([$id]);
        log_action('delete', $id);
        toastRedirect('已删除该图片');
    }
}
function toastRedirect($msg){ header('Location: /user/?done='.urlencode($msg)); exit; }

// 搜索/分页
$keyword = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 24;
$where = 'WHERE user_id=?';
$params = [$user['id']];
if ($keyword) { $where .= ' AND filename LIKE ?'; $params[] = "%{$keyword}%"; }
$total = db()->prepare("SELECT COUNT(*) c FROM images {$where}");
$total->execute($params); $total = $total->fetch()['c'];
$params[] = ($page-1)*$perPage; $params[] = $perPage;
$st = db()->prepare("SELECT * FROM images {$where} ORDER BY id DESC LIMIT ?,?");
$st->execute($params);
$images = $st->fetchAll();
$used = db()->prepare('SELECT COALESCE(SUM(size),0) s FROM images WHERE user_id=?');
$used->execute([$user['id']]); $usedBytes = $used->fetch()['s'];
$pages = max(1, ceil($total / $perPage));
$done = $_GET['done'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>用户中心 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container">
<div class="dash">
  <!-- 侧栏 -->
  <aside class="sidebar card" style="height:fit-content">
    <div class="text-center" style="margin-bottom:8px">
      <img class="avatar-img" src="<?= e($user['avatar'] ?: '/assets/images/avatar-default.php?s=' . urlencode($user['username'])) ?>" alt="avatar" onerror="this.src='/assets/images/avatar-default.php'">
    </div>
    <div class="text-center" style="margin-bottom:14px">
      <b>@<?= e($user['username']) ?></b><br>
      <span class="badge <?= is_admin($user)?'badge-admin':'' ?>"><?= is_admin($user)?'🌟 管理员':'普通用户' ?></span>
    </div>
    <a href="/user/" class="active">🖼️ 我的图片</a>
    <a href="/user/profile.php">⚙️ 账号设置</a>
    <a href="/user/api.php">🔑 API 密钥</a>
    <?php if(is_admin($user)):?><a href="/admin/">🛡️ 管理后台</a><?php endif;?>
    <a href="/logout.php" style="color:#ff6b81">🚪 退出登录</a>
  </aside>

  <!-- 主区 -->
  <section>
    <?php if($done):?><div class="alert alert-ok"><?=e($done)?></div><?php endif;?>
    <div class="user-head">
      <div style="flex:1;min-width:200px">
        <h2>🐾 我的图库</h2>
        <p class="text-muted">在这里管理你上传的每一张萌图</p>
      </div>
      <a class="btn btn-primary" href="/#dropZone">＋ 上传新图片</a>
    </div>

    <div class="stat-row">
      <div class="stat"><div class="num"><?= $total ?></div><div>图片总数</div></div>
      <div class="stat"><div class="num"><?= filesize_human($usedBytes) ?></div><div>已用空间</div></div>
      <div class="stat"><div class="num"><?= MAX_UPLOAD_MB ?>MB</div><div>单文件上限</div></div>
    </div>

    <!-- 搜索栏 -->
    <form class="toolbar" method="get">
      <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="🔍 搜索图片文件名...">
      <button class="btn btn-primary">搜索</button>
      <?php if($keyword):?><a class="btn btn-ghost" href="/user/">清除</a><?php endif;?>
    </form>

    <?php if ($images): ?>
    <div class="grid">
      <?php foreach ($images as $img): ?>
      <div class="gallery-card" id="img-<?= $img['id'] ?>">
        <a href="<?= e($img['url']) ?>" target="_blank">
          <img class="lazy" data-src="<?= e($img['thumbpath']?rtrim(SITE_URL,'/').'/'.$img['thumbpath']:$img['url']) ?>" alt="<?= e($img['filename']) ?>" loading="lazy">
        </a>
        <div style="padding:10px">
          <div style="font-size:.85rem;word-break:break-all"><?= e(mb_strimwidth($img['filename'],0,30,'…')) ?></div>
          <div class="text-muted" style="font-size:.78rem;display:flex;justify-content:space-between;margin:4px 0 8px">
            <span><?= filesize_human($img['size']) ?></span><span><?= time_ago($img['created_at']) ?></span>
          </div>
          <div class="split">
            <button class="btn btn-sm btn-primary" type="button" data-copy="<?= e($img['url']) ?>">🔗 链接</button>
            <a class="btn btn-sm btn-ghost" href="<?= e($img['url']) ?>" target="_blank">查看</a>
            <a class="btn btn-sm btn-danger" href="?del=<?= $img['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('确定删除这张图吗?')">删除</a>
          </div>
          <?php
          // 多格式链接(与上传成功后一致)
          $md  = '![' . $img['filename'] . '](' . $img['url'] . ')';
          $htm = '<img src="' . $img['url'] . '">';
          $bb  = '[img]' . $img['url'] . '[/img]';
          ?>
          <div class="split" style="margin-top:6px">
            <button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e($md) ?>" title="复制 Markdown">MD</button>
            <button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e($htm) ?>" title="复制 HTML">HTML</button>
            <button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e($bb) ?>" title="复制 BBCode">BBCode</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- 分页 -->
    <?php if ($pages > 1): ?>
    <div class="toolbar" style="justify-content:center;margin-top:20px">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <a class="btn <?= $i==$page?'btn-primary':'btn-ghost' ?>" href="?p=<?= $i ?>&q=<?= e($keyword) ?>" style="min-width:44px"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty card">
      <div class="big">📭</div>
      <p><?= $keyword?'没有找到相关图片':'你还没有上传图片哦' ?></p>
      <a class="btn btn-primary mt" href="/">去上传第一张 →</a>
    </div>
    <?php endif; ?>
  </section>
</div>
</main>
<?php include __DIR__.'/../partials/footer.php'; ?>
<script src="/assets/js/app.js"></script>
</body></html>
