<?php
/**
 * 萌图云 MoePic - 管理后台 · 图片管理
 */
require_once __DIR__ . '/../config/core.php';
$admin = require_admin();

if (isset($_GET['del'])) {
    csrf_check($_GET['csrf'] ?? null);
    $id = (int)$_GET['del'];
    $st = db()->prepare('SELECT * FROM images WHERE id=?'); $st->execute([$id]); $img = $st->fetch();
    if ($img) {
        foreach ([UPLOAD_DIR.'/'.$img['stored_name'], __DIR__.'/../'.$img['thumbpath']] as $f) {
            if ($f && file_exists($f)) @unlink($f);
        }
        db()->prepare('DELETE FROM images WHERE id=?')->execute([$id]);
        log_action('admin_delete', $id);
        header('Location: /admin/images.php?done='.urlencode('已删除图片'));
        exit;
    }
}
$q = trim($_GET['q'] ?? ''); $p = max(1,(int)(_GET('p'))); $per = 30;
$where = ''; $params = [];
if ($q) { $where = 'WHERE i.filename LIKE ? OR u.username LIKE ?'; $params = ["%{$q}%","%{$q}%"]; }
$total = db()->prepare("SELECT COUNT(*) c FROM images i LEFT JOIN users u ON u.id=i.user_id {$where}");
$total->execute($params); $total = $total->fetch()['c'];
$params[] = ($p-1)*$per; $params[] = $per;
$st = db()->prepare("SELECT i.*,u.username FROM images i LEFT JOIN users u ON u.id=i.user_id {$where} ORDER BY i.id DESC LIMIT ?,?");
$st->execute($params); $images = $st->fetchAll();
$pages = max(1, ceil($total/$per));
function _GET($k){return $_GET[$k]??1;}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>图片管理 - 管理后台</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/admin/">📊 仪表盘</a>
  <a href="/admin/images.php" class="active">🖼️ 图片管理</a>
  <a href="/admin/users.php">👥 用户管理</a>
  <a href="/admin/settings.php">⚙️ 站点设置</a>
  <a href="/admin/logs.php">📜 访问日志</a>
  <a href="/user/">🏠 返回前台</a>
</aside>
<section>
  <div class="user-head"><div><h2>🖼️ 全部图片</h2><p class="text-muted">共 <?= $total ?> 张,可删除违规图片</p></div></div>
  <?php if(isset($_GET['done'])):?><div class="alert alert-ok"><?= e($_GET['done']) ?></div><?php endif;?>
  <form class="toolbar" method="get">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="🔍 按文件名 / 用户名搜索...">
    <button class="btn btn-primary">搜索</button>
  </form>

  <div class="card" style="padding:0;overflow:hidden">
  <table>
    <thead><tr><th>预览</th><th>文件名</th><th>用户</th><th>大小</th><th>上传时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach($images as $img):?>
    <tr>
      <td><img class="img-thumb lazy" data-src="<?= e($img['thumbpath']?rtrim(SITE_URL,'/').'/'.$img['thumbpath']:$img['url']) ?>" src="/assets/images/placeholder.png" alt=""></td>
      <td style="word-break:break-all"><?= e(mb_strimwidth($img['filename'],0,40,'…')) ?></td>
      <td>@<?= e($img['username']?:'游客') ?></td>
      <td><?= filesize_human($img['size']) ?></td>
      <td><?= time_ago($img['created_at']) ?></td>
      <td>
        <a class="btn btn-sm btn-ghost" href="<?= e($img['url']) ?>" target="_blank">查看</a>
        <a class="btn btn-sm btn-danger" href="?del=<?= $img['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('确定删除这张图片?')">删除</a>
      </td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  </div>
  <?php if ($pages>1):?><div class="toolbar" style="justify-content:center;margin-top:16px">
    <?php for($i=1;$i<=$pages;$i++):?><a class="btn <?= $i==$p?'btn-primary':'btn-ghost' ?>" href="?p=<?= $i ?>&q=<?= e($q) ?>" style="min-width:44px"><?= $i ?></a><?php endfor;?>
  </div><?php endif;?>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
<script src="/assets/js/app.js"></script>
</body></html>
