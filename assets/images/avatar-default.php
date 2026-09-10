<?php
/**
 * 萌图云 MoePic - 默认头像生成器 (SVG)
 * 直接输出一只可爱的猫耳头像,无需外部图片文件
 * 用法: <img src="assets/images/avatar-default.php">
 */
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
$seed = substr(md5($_GET['s'] ?? 'moepic'), 0, 6);
$r = hexdec(substr($seed,0,2)); $g = hexdec(substr($seed,2,2)); $b = hexdec(substr($seed,4,2));
$bg = sprintf('rgb(%d,%d,%d)', $r, $g, $b);
?>
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="<?= $bg ?>" stop-opacity="0.9"/>
      <stop offset="100%" stop-color="#ffd6e7"/>
    </linearGradient>
  </defs>
  <circle cx="100" cy="100" r="96" fill="url(#g)"/>
  <!-- 猫耳 -->
  <polygon points="40,55 60,15 85,55" fill="#ff8fb1"/>
  <polygon points="160,55 140,15 115,55" fill="#ff8fb1"/>
  <!-- 脸 -->
  <circle cx="100" cy="105" r="55" fill="#fff5fa"/>
  <!-- 眼睛 -->
  <ellipse cx="82" cy="100" rx="6" ry="9" fill="#4a4458"/>
  <ellipse cx="118" cy="100" rx="6" ry="9" fill="#4a4458"/>
  <circle cx="84" cy="97" r="2" fill="#fff"/>
  <circle cx="120" cy="97" r="2" fill="#fff"/>
  <!-- 腮红 -->
  <ellipse cx="72" cy="118" rx="8" ry="5" fill="#ffb3c6" opacity="0.8"/>
  <ellipse cx="128" cy="118" rx="8" ry="5" fill="#ffb3c6" opacity="0.8"/>
  <!-- 嘴 -->
  <path d="M92 120 Q100 128 108 120" stroke="#ff8fb1" stroke-width="3" fill="none" stroke-linecap="round"/>
  <!-- 蝴蝶结 -->
  <polygon points="100,30 88,45 100,55 112,45" fill="#a78bfa"/>
</svg>
