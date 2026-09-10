<?php
/**
 * 萌图云 MoePic - 账号设置
 */
require_once __DIR__ . '/../config/core.php';
$user = require_login();
$msg = ''; $type = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $old   = $_POST['old_password'] ?? '';
    $new   = $_POST['new_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $msg = '邮箱格式不正确'; $type = 'err'; }
    elseif ($new && !password_verify($old, $user['password'])) { $msg = '原密码错误'; $type = 'err'; }
    elseif ($new && strlen($new) < 6) { $msg = '新密码至少6位'; $type = 'err'; }
    else {
        $sql = 'UPDATE users SET email=?'; $p = [$email];
        if ($new) { $sql .= ',password=?'; $p[] = password_hash($new, PASSWORD_DEFAULT); }
        $sql .= ' WHERE id=?'; $p[] = $user['id'];
        db()->prepare($sql)->execute($p);
        $msg = '保存成功 🎉';
        $user['email'] = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>账号设置 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/user/">🖼️ 我的图片</a>
  <a href="/user/profile.php" class="active">⚙️ 账号设置</a>
  <a href="/user/api.php">🔑 API 密钥</a>
  <?php if(is_admin($user)):?><a href="/admin/">🛡️ 管理后台</a><?php endif;?>
  <a href="/logout.php" style="color:#ff6b81">🚪 退出</a>
</aside>
<section>
  <div class="card" style="max-width:560px">
    <h2>⚙️ 账号设置</h2>
    <?php if($msg):?><div class="alert alert-<?= $type==='ok'?'ok':'err' ?>"><?= $msg ?></div><?php endif;?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="field"><label>用户名</label><input value="<?= e($user['username']) ?>" disabled></div>
      <div class="field"><label>邮箱</label><input type="email" name="email" value="<?= e($user['email']) ?>" required></div>
      <hr class="mb mt" style="border:none;border-top:1px dashed var(--pink);">
      <p class="text-muted" style="font-size:.88rem">修改密码(不改请留空)</p>
      <div class="field"><label>原密码</label><input type="password" name="old_password"></div>
      <div class="field"><label>新密码</label><input type="password" name="new_password" minlength="6"></div>
      <button class="btn btn-primary">保存修改 💾</button>
    </form>
  </div>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
</body></html>
