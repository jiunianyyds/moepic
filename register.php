<?php
/**
 * 萌图云 MoePic - 用户注册
 */
require_once __DIR__ . '/config/core.php';
if (current_user()) redirect('/user/');
$err = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) $err = '用户名只能含字母/数字/下划线,3-32位';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))     $err = '邮箱格式不正确';
    elseif (strlen($password) < 6)                          $err = '密码至少6位';
    else {
        $db = db();
        $c = $db->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $c->execute([$username, $email]);
        if ($c->fetch()) $err = '用户名或邮箱已被使用 (´-ω-`)';
        else {
            $api = bin2hex(random_bytes(24));
            $st = $db->prepare('INSERT INTO users(username,password,email,role,api_key,created_at) VALUES(?,?,?,0,?,NOW())');
            $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, $api]);
            $ok = '注册成功 🎉 正在跳转登录...';
            header('Refresh: 1.5; url=/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>注册 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<?php include __DIR__.'/partials/nav.php'; ?>
<div class="auth-wrap">
  <div class="card auth-card">
    <div class="avatar">🐱</div>
    <h2>加入 <?= e(setting('site_name')) ?></h2>
    <p class="form-hint">注册后就能上传、管理你的萌图啦 (´∀`)</p>
    <?php if($err):?><div class="alert alert-err"><?=$err?></div><?php endif;?>
    <?php if($ok):?><div class="alert alert-ok"><?=$ok?></div><?php endif;?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="field"><label>用户名</label><input name="username" required minlength="3" placeholder="3-32位字母/数字/下划线"></div>
      <div class="field"><label>邮箱</label><input type="email" name="email" required placeholder="you@example.com"></div>
      <div class="field"><label>密码</label><input type="password" name="password" required minlength="6" placeholder="至少6位"></div>
      <button class="btn btn-primary" style="width:100%;margin-top:6px">立即注册 🎀</button>
    </form>
    <p class="text-muted mt">已有账号? <a href="login.php">去登录 →</a></p>
  </div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
</body></html>
