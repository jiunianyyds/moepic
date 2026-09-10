<?php
/**
 * 萌图云 MoePic - 退出登录
 */
require_once __DIR__ . '/config/core.php';
session_start_safe();
session_destroy();
redirect('/');
