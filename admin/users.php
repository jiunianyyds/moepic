<?php
/**
 * 萌图云 MoePic - 管理后台 · 用户管理
 */
require_once __DIR__ . '/../config/core.php';
$admin = require_admin();

if (isset($_GET['action'], $_GET['id'])) {
    csrf_check($_GET['csrf'] ?? null);
    $id = (int)$_GET['id'];
    if ($id == $admin['id']) { header('Location: /admin/users.php?done='.urlencode('不能对自己操作')); exit; }
    switch ($_GET['action']) {
        case 'delete':
            // 删除用户及其所有图片
            $imgs = db()->prepare('SELECT stored_name,thumbpath FROM images WHERE user_id=?'); $imgs->execute([$id]);
            foreach ($imgs->fetchAll() as $im) {
                foreach ([UPLOAD_DIR.'/'.$im['stored_name'], __DIR__.'/../'.$im['thumbpath']] as $f) {
                    if ($f && file_exists($f)) @unlink($f);
                }
            }
            db()->prepare('DELETE FROM images WHERE user_id=?')->execute([$id]);
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            header('Location: /admin/users.php?done='.urlencode('已删除用户及其图片'));
            exit;
        case 'toggle':
            db()->prepare('UPDATE users SET status=1-status WHERE id=?')->execute([$id]);
            header('Location: /admin/users.php?done='.urlencode('状态已更新'));
            exit;
        case 'role':
            db()->prepare('UPDATE users SET role=1-role WHERE id=?')->execute([$id]);
            header('Location: /admin/users.php?done='.urlencode('权限已更新'));
            exit;
    }
}
$q = trim($_GET['q'] ?? ''); $where = ''; $params = [];
if ($q) { $where = 'WHERE username LIKE ? OR email LIKE ?'; $params = ["%{$q}%","%{$q}%"]; }
$users = db()->prepare("SELECT u.*,(SELECT COUNT(*) FROM images i WHERE i.user_id=u.id) pics FROM users u {$where} ORDER BY u.id DESC LIMIT 100");
$users->execute($params); $users = $users->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>用户管理 - 管理后台</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/admin/">📊 仪表盘</a>
  <a href="/admin/images.php">🖼️ 图片管理</a>
  <a href="/admin/users.php" class="active">👥 用户管理</a>
  <a href="/admin/settings.php">⚙️ 站点设置</a>
  <a href="/admin/logs.php">📜 访问日志</a>
  <a href="/user/">🏠 返回前台</a>
</aside>
<section>
  <div class="user-head"><div><h2>👥 用户管理</h2><p class="text-muted">共 <?= count($users) ?> 位(仅显示最近100条)</p></div></div>
  <?php if(isset($_GET['done'])):?><div class="alert alert-ok"><?= e($_GET['done']) ?></div><?php endif;?>
  <form class="toolbar" method="get"><input type="text" name="q" value="<?= e($q) ?>" placeholder="🔍 按用户名 / 邮箱搜索..."><button class="btn btn-primary">搜索</button></form>
  <div class="card" style="padding:0;overflow:hidden">
  <table>
    <thead><tr><th>ID</th><th>用户名</th><th>邮箱</th><th>图片</th><th>角色</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach($users as $u):?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td>@<?= e($u['username']) ?><?= $u['id']==$admin['id']?' <span class="badge">你</span>':'' ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= $u['pics'] ?></td>
      <td><?= $u['role']?'<span class="badge badge-admin">管理员</span>':'用户' ?></td>
      <td><?= $u['status']?'<span class="badge">正常</span>':'<span class="badge btn-danger">禁用</span>' ?></td>
      <td><?= date('Y-m-d',strtotime($u['created_at'])) ?></td>
      <td>
        <a class="btn btn-sm btn-ghost" href="?action=toggle&id=<?= $u['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('切换该用户状态?')">启/禁用</a>
        <a class="btn btn-sm btn-ghost" href="?action=role&id=<?= $u['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('切换管理员权限?')">权限</a>
        <a class="btn btn-sm btn-danger" href="?action=delete&id=<?= $u['id'] ?>&csrf=<?= csrf_token() ?>" onclick="return confirm('删除该用户及其全部图片?此操作不可恢复!')">删除</a>
      </td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  </div>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
</body></html>
