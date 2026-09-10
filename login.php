<?php
/**
 * 萌图云 MoePic - 用户登录
 */
require_once __DIR__ . '/config/core.php';
if (current_user()) redirect('/user/');
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT * FROM users WHERE (username=? OR email=?) AND status=1');
    $st->execute([$username, $username]);
    $u = $st->fetch();
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true); // 防 Session Fixation
        $_SESSION['uid'] = $u['id'];
        $redirect = $_GET['redirect'] ?? ($u['role'] == 1 ? '/admin/' : '/user/');
        redirect($redirect);
    }
    $err = '用户名或密码错误,或账号已被禁用 (´-ω-`)';
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>登录 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<?php include __DIR__.'/partials/nav.php'; ?>
<div class="auth-wrap">
  <div class="card auth-card">
    <div class="avatar">🎀</div>
    <h2>欢迎回来!</h2>
    <p class="form-hint">登录后管理你的专属图库 ฅ•ω•ฅ</p>
    <?php if($err):?><div class="alert alert-err"><?=$err?></div><?php endif;?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="field"><label>用户名 / 邮箱</label><input name="username" required autofocus placeholder="admin"></div>
      <div class="field"><label>密码</label><input type="password" name="password" required placeholder="••••••"></div>
      <button class="btn btn-primary" style="width:100%;margin-top:6px">登 录 ✨</button>
    </form>
    <p class="text-muted mt">还没有账号? <a href="register.php">去注册 →</a></p>
  </div>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
</body></html>
