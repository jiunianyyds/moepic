<?php
/**
 * 萌图云 MoePic - 一键安装向导
 * 访问: https://你的域名/install.php
 * 填写数据库信息与管理员账号,自动完成:
 *   1. 改写 config/database.php
 *   2. 建库 / 执行 database.sql 建表
 *   3. 创建管理员账号
 *   4. 写入 install.lock 锁定,禁止重复安装
 */

// ===== 已安装则禁止再次访问 =====
$moepicLockFile = __DIR__ . '/install.lock';
if (file_exists($moepicLockFile)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>萌图云 MoePic · 已安装</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#ffe0ec 0%,#e0e7ff 100%);font-family:-apple-system,"PingFang SC","Microsoft YaHei",sans-serif;color:#444}
.box{background:#fff;border-radius:20px;padding:40px 36px;max-width:480px;width:90%;text-align:center;box-shadow:0 10px 40px #0001}
.logo{font-size:2rem;margin-bottom:6px}.logo b{color:#ff6b81}.sub{color:#888;margin-bottom:20px;font-size:.95rem}
.code{background:#fff5f7;border:1px dashed #ffb3c1;border-radius:10px;padding:12px;font-family:Consolas,monospace;font-size:.85rem;color:#d6336c;margin:14px 0;word-break:break-all}
.btn{display:inline-block;margin-top:8px;padding:10px 24px;background:linear-gradient(135deg,#ff6b81,#a78bfa);color:#fff;text-decoration:none;border-radius:999px;font-weight:600}
</style></head><body>
<div class="box">
  <div class="logo">🌸 萌图云 <b>MoePic</b></div>
  <p class="sub">系统已完成安装,安装向导已锁定 🔒</p>
  <p>为防止被他人恶意重装,本页面已被禁止访问。</p>
  <p>如需重新安装,请先删除项目根目录下的锁文件:</p>
  <div class="code">install.lock</div>
  <p class="sub" style="font-size:.85rem">删除后刷新本页即可重新进入安装向导。</p>
  <a class="btn" href="index.php">返回首页 →</a>
</div></body></html>';
    exit;
}

// 仅载入配置与公共函数(不加载 core.php,避免被锁检测重定向)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
session_start_safe();

$step    = $_POST['step'] ?? 'form';
$msg     = '';
$ok      = true;
$form    = [
    'db_host' => $_POST['db_host'] ?? 'localhost',
    'db_port' => $_POST['db_port'] ?? '3306',
    'db_user' => $_POST['db_user'] ?? '',
    'db_pass' => $_POST['db_pass'] ?? '',
    'db_name' => $_POST['db_name'] ?? 'moepic',
    'admin_user' => $_POST['admin_user'] ?? '',
    'admin_pass' => $_POST['admin_pass'] ?? '',
    'admin_email'=> $_POST['admin_email'] ?? '',
];

/* ---------- 执行安装 ---------- */
if ($step === 'install') {
    csrf_check();

    // 1) 基础校验
    if ($form['db_host'] === '' || $form['db_user'] === '' || $form['db_name'] === '') {
        $msg = '请填写完整的数据库连接信息'; $ok = false;
    } elseif (strlen($form['admin_user']) < 3) {
        $msg = '管理员用户名至少 3 位'; $ok = false;
    } elseif (strlen($form['admin_pass']) < 6) {
        $msg = '管理员密码至少 6 位'; $ok = false;
    }

    // 2) 测试数据库连接并建库
    $pdo = null;
    if ($ok) {
        try {
            $pdo = new PDO(
                'mysql:host=' . $form['db_host'] . ';port=' . (int)$form['db_port'] . ';charset=utf8mb4',
                $form['db_user'],
                $form['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','',$form['db_name']) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `" . str_replace('`','',$form['db_name']) . "`");
            try { $pdo->exec("SET time_zone = '+08:00'"); } catch (Exception $e) {}
        } catch (Exception $e) {
            $ok = false; $msg = '✗ 数据库连接失败: ' . $e->getMessage();
        }
    }

    // 3) 改写 config/database.php
    if ($ok) {
        $cfgFile = __DIR__ . '/config/database.php';
        if (!is_writable($cfgFile)) {
            $ok = false; $msg = '✗ config/database.php 不可写,请检查目录权限';
        } else {
            $content = file_get_contents($cfgFile);
            // 用 var_export 生成安全的 PHP 字符串字面量(自动转义引号等特殊字符)
            $newHost = var_export($form['db_host'], true);
            $newPort = (int)$form['db_port'];
            $newUser = var_export($form['db_user'], true);
            $newPass = var_export($form['db_pass'], true);
            $newName = var_export($form['db_name'], true);
            // 匹配 DB_* define 行并替换
            $content = preg_replace("/define\('DB_HOST',\s*'[^']*'\);/", "define('DB_HOST', " . $newHost . ");", $content);
            $content = preg_replace("/define\('DB_PORT',\s*\d+\);/", "define('DB_PORT', " . $newPort . ");", $content);
            $content = preg_replace("/define\('DB_USER',\s*'[^']*'\);/", "define('DB_USER', " . $newUser . ");", $content);
            $content = preg_replace("/define\('DB_PASS',\s*'[^']*'\);/", "define('DB_PASS', " . $newPass . ");", $content);
            $content = preg_replace("/define\('DB_NAME',\s*'[^']*'\);/", "define('DB_NAME', " . $newName . ");", $content);
            if ($content === null) {
                $ok = false; $msg = '✗ 配置文件改写失败(正则错误)';
            } else {
                file_put_contents($cfgFile, $content);
            }
        }
    }

    // 4) 执行 database.sql 建表(PDO MySQL 驱动支持一次执行多条语句)
    if ($ok) {
        try {
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) throw new Exception('database.sql 不存在');
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
        } catch (Exception $e) {
            $ok = false; $msg = '✗ 建表失败: ' . $e->getMessage();
        }
    }

    // 5) 创建管理员账号(预处理,防注入)
    if ($ok) {
        try {
            $hash = password_hash($form['admin_pass'], PASSWORD_DEFAULT);
            $api  = bin2hex(random_bytes(24));
            $st = $pdo->prepare('INSERT INTO users(username,password,email,role,api_key,created_at) VALUES(?,?,?,1,?,NOW())');
            $st->execute([$form['admin_user'], $hash, $form['admin_email'], $api]);
        } catch (Exception $e) {
            $ok = false; $msg = '✗ 创建管理员失败: ' . $e->getMessage();
        }
    }

    // 6) 确保上传目录存在且可写
    if ($ok) {
        $dirs = [__DIR__ . '/uploads', __DIR__ . '/uploads/thumbs'];
        foreach ($dirs as $d) {
            if (!is_dir($d)) @mkdir($d, 0755, true);
        }
    }

    // 7) 写入安装锁文件
    if ($ok) {
        $lockContent = "installed_at=" . date('Y-m-d H:i:s') . "\nadmin=" . $form['admin_user'] . "\n";
        if (@file_put_contents($moepicLockFile, $lockContent) === false) {
            $ok = false; $msg = '✗ 无法写入 install.lock,请检查项目根目录权限';
        }
    }

    if ($ok) {
        $msg = '🎉 安装成功!系统已就绪,请妥善保管管理员账号。';
        $step = 'done';
    } else {
        $step = 'form';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>萌图云 MoePic · 安装向导</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  body.theme-auto{background:linear-gradient(135deg,#ffe0ec 0%,#e0e7ff 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .install-box{max-width:520px;width:100%}
  .install-box .logo{font-size:1.8rem;text-align:center}
  .install-box .logo b{color:var(--pink,#ff6b81)}
  .install-box label{display:block;margin:12px 0 4px;font-weight:600;font-size:.9rem}
  .install-box input[type=text],.install-box input[type=password],.install-box input[type=email]{width:100%;padding:11px 14px;border-radius:12px;border:2px solid var(--pink-light,#ffd6e0);background:var(--cream,#fffafc);font-size:.95rem;box-sizing:border-box}
  .install-box input:focus{outline:none;border-color:var(--pink,#ff6b81)}
  .install-box .group{background:var(--cream,#fffafc);border-radius:14px;padding:14px 16px;margin:14px 0}
  .install-box .group-title{font-weight:700;color:var(--pink,#ff6b81);margin-bottom:4px}
  .install-box .btn-primary{width:100%;margin-top:16px;padding:13px;font-size:1rem}
</style>
</head>
<body class="theme-auto">
<div class="install-box card">
  <div class="logo">🌸 萌图云 <b>MoePic</b></div>
  <p class="sub" style="text-align:center;color:var(--muted);margin:4px 0 18px">二次元可爱风图床 · 一键安装向导 (´∀`)</p>

  <?php if ($msg): ?>
    <div class="alert <?= $ok ? 'alert-ok' : 'alert-err' ?>" style="margin-bottom:14px"><?= $msg ?></div>
  <?php endif; ?>

  <?php if ($step === 'form'): ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="step" value="install">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <div class="group">
      <div class="group-title">🗄️ 数据库连接信息</div>
      <label>数据库地址</label>
      <input type="text" name="db_host" value="<?= e($form['db_host']) ?>" placeholder="localhost" required>
      <label>数据库端口</label>
      <input type="text" name="db_port" value="<?= e($form['db_port']) ?>" placeholder="3306" required>
      <label>数据库用户名</label>
      <input type="text" name="db_user" value="<?= e($form['db_user']) ?>" placeholder="root" required>
      <label>数据库密码</label>
      <input type="password" name="db_pass" value="<?= e($form['db_pass']) ?>" placeholder="数据库密码">
      <label>数据库名</label>
      <input type="text" name="db_name" value="<?= e($form['db_name']) ?>" placeholder="moepic" required>
    </div>

    <div class="group">
      <div class="group-title">👤 管理员账号</div>
      <label>管理员用户名</label>
      <input type="text" name="admin_user" value="<?= e($form['admin_user']) ?>" placeholder="admin" required minlength="3">
      <label>管理员密码</label>
      <input type="password" name="admin_pass" placeholder="至少 6 位" required minlength="6">
      <label>邮箱(选填)</label>
      <input type="email" name="admin_email" value="<?= e($form['admin_email']) ?>" placeholder="admin@example.com">
    </div>

    <button type="submit" class="btn btn-primary">🚀 开始安装</button>
  </form>
  <?php elseif ($step === 'done'): ?>
  <div style="text-align:center">
    <div style="font-size:3rem;margin-bottom:10px">🎉</div>
    <h3 style="margin:8px 0">安装完成!</h3>
    <p style="color:var(--muted)">管理员账号:<b><?= e($form['admin_user']) ?></b></p>
    <p style="color:var(--muted);font-size:.88rem">install.lock 已生成,安装向导已锁定。</p>
    <a class="btn btn-primary" href="login.php" style="display:inline-block;margin-top:12px">前往登录 →</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
