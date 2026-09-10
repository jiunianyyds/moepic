<?php
/**
 * 萌图云 MoePic - 管理后台 · 仪表盘
 */
require_once __DIR__ . '/../config/core.php';
$admin = require_admin();

// 统计数据
$stats = [];
$stats['users']    = db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$stats['images']   = db()->query('SELECT COUNT(*) c FROM images')->fetch()['c'];
$stats['size']     = db()->query('SELECT COALESCE(SUM(size),0) c FROM images')->fetch()['c'];
$stats['uploads_today'] = db()->query("SELECT COUNT(*) c FROM upload_logs WHERE action='upload' AND created_at>=CURDATE()")->fetch()['c'];
$stats['views_today']   = db()->query("SELECT COUNT(*) c FROM upload_logs WHERE action='view' AND created_at>=CURDATE()")->fetch()['c'];
$totalSize = filesize_human($stats['size']);

// 近7天上传趋势(给图表用)
$chart = [];
for ($i=6;$i>=0;$i--){
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $row = db()->prepare("SELECT COUNT(*) c FROM images WHERE DATE(created_at)=?");
  $row->execute([$d]); $chart[$d] = $row->fetch()['c'];
}
$maxChart = max($chart) ?: 1;

// 最近日志
$logs = db()->query('SELECT l.*,u.username,i.filename FROM upload_logs l LEFT JOIN users u ON u.id=l.user_id LEFT JOIN images i ON i.id=l.image_id ORDER BY l.id DESC LIMIT 12')->fetchAll();
$me = $admin;
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>管理后台 - <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <div class="text-center" style="margin-bottom:12px">
    <span class="badge badge-admin">🛡️ 管理员</span>
  </div>
  <a href="/admin/" class="active">📊 仪表盘</a>
  <a href="/admin/images.php">🖼️ 图片管理</a>
  <a href="/admin/users.php">👥 用户管理</a>
  <a href="/admin/settings.php">⚙️ 站点设置</a>
  <a href="/admin/logs.php">📜 访问日志</a>
  <a href="/user/">🏠 返回前台</a>
  <a href="/logout.php" style="color:#ff6b81">🚪 退出</a>
</aside>
<section>
  <div class="user-head"><div>
    <h2>📊 仪表盘</h2>
    <p class="text-muted">欢迎回来, <?= e($admin['username']) ?> 管理员 (´∀`)</p>
  </div></div>

  <div class="stat-row">
    <div class="stat"><div class="num"><?= $stats['images'] ?></div><div>图片总数</div></div>
    <div class="stat"><div class="num"><?= $stats['users'] ?></div><div>注册用户</div></div>
    <div class="stat"><div class="num"><?= $totalSize ?></div><div>占用空间</div></div>
    <div class="stat"><div class="num"><?= $stats['uploads_today'] ?></div><div>今日上传</div></div>
    <div class="stat"><div class="num"><?= $stats['views_today'] ?></div><div>今日访问</div></div>
  </div>

  <!-- 7天趋势图(纯CSS柱状) -->
  <div class="card mb">
    <h3>📈 近7天上传趋势</h3>
    <div style="display:flex;align-items:flex-end;gap:10px;height:140px;margin-top:14px">
      <?php foreach($chart as $d=>$c): ?>
        <div style="flex:1;text-align:center" title="<?= $d ?>: <?= $c ?>张">
          <div style="background:linear-gradient(var(--pink),var(--purple));border-radius:8px 8px 0 0;height:<?= round($c/$maxChart*100) ?>%;min-height:4px;color:#fff;display:flex;align-items:flex-start;justify-content:center;padding-top:4px;font-size:.75rem"><?= $c?:'' ?></div>
          <div style="font-size:.72rem;color:var(--muted);margin-top:4px"><?= date('m/d',strtotime($d)) ?></div>
        </div>
      <?php endforeach;?>
    </div>
  </div>

  <div class="card">
    <h3>📜 最近活动</h3>
    <table>
      <thead><tr><th>时间</th><th>用户</th><th>动作</th><th>详情</th></tr></thead>
      <tbody>
      <?php foreach($logs as $l):?>
        <tr>
          <td><?= time_ago($l['created_at']) ?></td>
          <td>@<?= e($l['username']?:'游客') ?></td>
          <td><span class="badge <?= $l['action']==='upload'?'badge-admin':'' ?>"><?= $l['action'] ?></span></td>
          <td class="hide-sm"><?= e($l['filename']?:$l['ip']) ?></td>
        </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
</body></html>
