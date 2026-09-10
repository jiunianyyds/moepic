<!-- 顶部导航 -->
<header class="nav">
  <div class="container row">
    <a href="/" class="logo"><span class="emoji">🌸</span> <?= e(setting('site_name')) ?> <b>MoePic</b></a>
    <nav class="nav-links">
      <a href="/"><span>🏠</span> 首页</a>
      <?php if ($me = current_user()): ?>
        <a href="/user/"><span>🖼️</span> 我的图库</a>
        <?php if (is_admin($me)): ?><a href="/admin/"><span>🛡️</span> 后台</a><?php endif; ?>
        <a href="/user/profile.php"><span>⚙️</span> @<?= e($me['username']) ?></a>
        <a href="/logout.php"><span>🚪</span> 退出</a>
      <?php else: ?>
        <a href="/login.php"><span>🔑</span> 登录</a>
        <a href="/register.php"><span>🐰</span> 注册</a>
      <?php endif; ?>
      <button class="theme-btn" id="themeBtn" title="切换夜间模式">🌙</button>
    </nav>
  </div>
</header>
