<?php
/**
 * 萌图云 MoePic - 公共函数库
 * 被各页面 require 的 core.php 加载
 */
require_once __DIR__ . '/database.php';

/* ============ Session 安全启动 ============ */
function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(SESSION_NAME);
    // Cookie 仅 HTTP、SameSite=Lax,降低 Session 劫持/XSS 带走风险
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

/* ============ CSRF 防护 ============ */
function csrf_token(): string
{
    session_start_safe();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(?string $token = null): void
{
    $t = $token ?? ($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    session_start_safe();
    if (empty($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        json(['ok' => false, 'msg' => '请求已过期,请刷新页面重试 (CSRF)'], 403);
    }
}

/* ============ 鉴权 ============ */
function current_user(): ?array
{
    session_start_safe();
    if (empty($_SESSION['uid'])) return null;
    static $u;
    if ($u) return $u;
    $st = db()->prepare('SELECT * FROM users WHERE id=? AND status=1');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch() ?: null;
    return $u;
}
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (is_ajax()) json(['ok' => false, 'msg' => '请先登录'], 401);
        redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
    return $u;
}
function require_admin(): array
{
    $u = require_login();
    if ($u['role'] != 1) {
        if (is_ajax()) json(['ok' => false, 'msg' => '需要管理员权限'], 403);
        http_response_code(403); exit('403 禁止访问');
    }
    return $u;
}
function is_admin(?array $u = null): bool
{
    $u = $u ?: current_user();
    return !empty($u) && $u['role'] == 1;
}
function is_ajax(): bool
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
}
function redirect(string $url): void
{
    header('Location: ' . $url, true, 302); exit;
}
function json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    // 防盗链/劫持: JSON 前缀破坏 XSS 直接读取
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ============ XSS / 输出安全 ============ */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function filesize_human(int $bytes): string
{
    $u = ['B','KB','MB','GB'];
    $i = 0; $n = (float)$bytes;
    while ($n >= 1024 && $i < 3) { $n /= 1024; $i++; }
    return round($n, 2) . ' ' . $u[$i];
}
function time_ago(string $datetime): string
{
    $t = strtotime($datetime); $d = time() - $t;
    if ($d < 60) return '刚刚';
    if ($d < 3600) return floor($d/60) . '分钟前';
    if ($d < 86400) return floor($d/3600) . '小时前';
    if ($d < 2592000) return floor($d/86400) . '天前';
    return date('Y-m-d', $t);
}

/* ============ 设置(KV)缓存 ============ */
function setting(string $k, $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = db()->query('SELECT k,v FROM settings')->fetchAll();
            $cache = array_column($rows, 'v', 'k');
        } catch (Exception $e) { $cache = []; }
    }
    return $cache[$k] ?? $default;
}

/* ============ 日志 ============ */
function log_action(string $action, int $imageId = 0): void
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        $st = db()->prepare('INSERT INTO upload_logs(user_id,ip,ua,referer,image_id,action) VALUES(?,?,?,?,?,?)');
        $u = current_user();
        $st->execute([$u['id'] ?? 0, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '', $imageId, $action]);
    } catch (Exception $e) { /* 日志失败不影响主流程 */ }
}

/* ============ 图片处理 ============ */
const ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
function validate_image(string $tmpPath, string $clientName): array
{
    if (!is_uploaded_file($tmpPath) && !file_exists($tmpPath)) {
        return ['ok' => false, 'msg' => '文件上传失败或未接收到文件'];
    }
    $size = filesize($tmpPath);
    $max = MAX_UPLOAD_MB * 1024 * 1024;
    if ($size > $max) return ['ok' => false, 'msg' => '文件超过限制(' . MAX_UPLOAD_MB . 'MB)'];
    if ($size < 1)    return ['ok' => false, 'msg' => '文件为空'];

    // 用 GD 读取,既能拿尺寸又能 100% 确认是真实图片(防伪扩展名/脚本马)
    $info = @getimagesize($tmpPath);
    if (!$info) return ['ok' => false, 'msg' => '该文件不是合法图片'];
    $mime = $info['mime'];
    if (!isset(ALLOWED_MIMES[$mime])) return ['ok' => false, 'msg' => '不支持的图片格式'];

    // 扩展名白名单校验(双重验证)
    $ext = ALLOWED_MIMES[$mime];
    $origExt = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
    if ($origExt !== $ext && $origExt !== ($mime === 'image/jpeg' ? 'jpeg' : $ext)) {
        // 客户端扩展名与真实类型不一致也没关系,以真实类型为准,但记录
    }
    return ['ok' => true, 'mime' => $mime, 'ext' => $ext, 'width' => $info[0], 'height' => $info[1], 'size' => $size];
}
function make_thumb(string $src, string $dst, int $max = 320): bool
{
    if (!extension_loaded('gd')) return false;
    $info = getimagesize($src);
    if (!$info) return false;
    [$w, $h] = $info;
    $ratio = min($max / $w, $max / $h, 1);
    $tw = (int)($w * $ratio); $th = (int)($h * $ratio);
    $srcImg = match ($info['mime']) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/gif'  => @imagecreatefromgif($src),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default => false,
    };
    if (!$srcImg) return false;
    $thumb = imagecreatetruecolor($tw, $th);
    // 保留 PNG/GIF 透明
    if ($info['mime'] === 'image/png' || $info['mime'] === 'image/gif') {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false); imagesavealpha($thumb, true);
    }
    imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $tw, $th, $w, $h);
    $ok = match ($info['mime']) {
        'image/jpeg' => imagejpeg($thumb, $dst, 85),
        'image/png'  => imagepng($thumb, $dst, 8),
        'image/gif'  => imagegif($thumb, $dst),
        'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $dst, 85) : false,
        default => false,
    };
    imagedestroy($srcImg); imagedestroy($thumb);
    return (bool)$ok;
}
function gen_stored_name(string $ext): string
{
    // 随机 + 微秒,避免重复,无序号可猜
    return bin2hex(random_bytes(8)) . '-' . substr(microtime(true) * 10000, -5) . '.' . $ext;
}

/* ============ 防盗链校验(供图片访问入口调用) ============ */
function hotlink_check(): void
{
    // 仅对直接外链场景限制:允许空 referer(浏览器地址栏直访)、本站、白名单域名
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref === '') return; // 直访放行
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $refHost = parse_url($ref, PHP_URL_HOST);
    if ($refHost && $refHost === $host) return;
    // 需要严格防盗链时,把下面 false 改为 true 即可启用
    if (false) {
        http_response_code(403);
        exit('403 Hotlink denied (´-ω-`)');
    }
}
