<?php
/**
 * 萌图云 MoePic - 管理后台 · 访问/上传日志
 */
require_once __DIR__ . '/../config/core.php';
$admin = require_admin();
$p = max(1,(int)($_GET['p']??1)); $per = 50;
$total = db()->query('SELECT COUNT(*) c FROM upload_logs')->fetch()['c'];
$st = db()->prepare('SELECT l.*,u.username,i.filename FROM upload_logs l LEFT JOIN users u ON u.id=l.user_id LEFT JOIN images i ON i.id=l.image_id ORDER BY l.id DESC LIMIT ?,?');
$st->execute([($p-1)*$per,$per]); $logs = $st->fetchAll();
$pages = max(1,ceil($total/$per));

// 按日统计(图表)
$day = [];
for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-{$i} days"));$day[$d]=['up'=>0,'view'=>0];}
$rows = db()->query("SELECT DATE(created_at) d,action,COUNT(*) c FROM upload_logs WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY d,action");
foreach($rows->fetchAll() as $r){ if(isset($day[$r['d']])) $day[$r['d']][$r['action']]=$r['c']; }
$maxD = max(array_map(fn($v)=>$v['up']+$v['view'],$day)) ?: 1;
?>
<!DOCTYPE html>
<html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>访问日志 - 管理后台</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php include __DIR__.'/../partials/nav.php'; ?>
<main class="container"><div class="dash">
<aside class="sidebar card" style="height:fit-content">
  <a href="/admin/">📊 仪表盘</a>
  <a href="/admin/images.php">🖼️ 图片管理</a>
  <a href="/admin/users.php">👥 用户管理</a>
  <a href="/admin/settings.php">⚙️ 站点设置</a>
  <a href="/admin/logs.php" class="active">📜 访问日志</a>
  <a href="/user/">🏠 返回前台</a>
</aside>
<section>
  <div class="user-head"><div><h2>📜 访问 & 上传日志</h2><p class="text-muted">共 <?= $total ?> 条记录</p></div></div>
  <div class="card mb">
    <h3>📈 近7天 上传/访问 趋势</h3>
    <div style="display:flex;align-items:flex-end;gap:10px;height:130px;margin-top:14px">
      <?php foreach($day as $d=>$c): $h=round(($c['up']+$c['view'])/$maxD*100);?>
        <div style="flex:1;text-align:center" title="<?= $d ?>">
          <div style="background:linear-gradient(var(--pink),var(--purple));border-radius:8px 8px 0 0;height:<?= $h ?>%;min-height:4px"></div>
          <div style="font-size:.7rem;color:var(--muted);margin-top:4px"><?= date('m/d',strtotime($d)) ?></div>
        </div>
      <?php endforeach;?>
    </div>
    <p class="text-muted" style="font-size:.82rem;margin-top:8px">🟣 柱高 = 上传+访问总数 · 详情见下表</p>
  </div>
  <div class="card" style="padding:0;overflow:hidden">
  <table>
    <thead><tr><th>时间</th><th>用户</th><th>动作</th><th>IP</th><th>图片 / Referer</th></tr></thead>
    <tbody>
    <?php foreach($logs as $l):?>
    <tr>
      <td><?= $l['created_at'] ?></td>
      <td>@<?= e($l['username']?:'游客') ?></td>
      <td><span class="badge <?= $l['action']==='upload'?'badge-admin':'' ?>"><?= $l['action'] ?></span></td>
      <td class="hide-sm"><?= e($l['ip']) ?></td>
      <td class="hide-sm" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($l['filename']?:$l['referer']) ?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  </div>
  <?php if($pages>1):?><div class="toolbar" style="justify-content:center;margin-top:16px">
    <?php for($i=1;$i<=$pages;$i++):?><a class="btn <?= $i==$p?'btn-primary':'btn-ghost' ?>" href="?p=<?= $i ?>" style="min-width:44px"><?= $i ?></a><?php endfor;?>
  </div><?php endif;?>
</section>
</div></main>
<?php include __DIR__.'/../partials/footer.php'; ?>
</body></html>
