<?php
require __DIR__ . '/_helpers.php';

if (!admin_is_setup()) { header('Location: setup.php'); exit; }
if (admin_login_ok())  { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = (string)($_POST['p'] ?? '');
    // 무차별 대입 완화
    $key = 'lock_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'x');
    $tries = (int)($_SESSION[$key] ?? 0);
    if ($tries >= 6) { $err = '시도가 너무 많습니다. 잠시 후 다시 시도해 주세요.'; }
    elseif (password_verify($p, (string)admin_password_hash())) {
        $_SESSION['admin'] = true;
        unset($_SESSION[$key]);
        session_regenerate_id(true);
        header('Location: index.php'); exit;
    } else {
        $_SESSION[$key] = $tries + 1;
        usleep(400000);
        $err = '비밀번호가 올바르지 않습니다.';
    }
}
?><!doctype html>
<html lang="ko"><head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />
<title>관리자 로그인 | 주노소프트</title>
<meta name="robots" content="noindex,nofollow" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
<link rel="stylesheet" href="../assets/css/style.css" />
<style>body{background:#0B1F3A;display:grid;place-items:center;min-height:100vh;padding:20px}.box{background:#fff;border-radius:16px;padding:40px;width:100%;max-width:400px;box-shadow:0 20px 50px rgba(0,0,0,.2)}</style>
</head><body>
<div class="box">
  <h2 style="margin:0 0 6px;color:#0B1F3A;font-size:24px;display:flex;align-items:center;gap:8px"><span style="width:10px;height:10px;border-radius:50%;background:#C8A85A;display:inline-block"></span>관리자 로그인</h2>
  <p class="muted" style="margin:0 0 22px;font-size:14px">주노소프트 운영자 콘솔</p>
  <?php if ($err): ?><div style="background:#FBEAE9;color:#B0322B;border:1px solid #F2C5C2;padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13.5px"><?= h($err) ?></div><?php endif; ?>
  <form method="post">
    <div class="field" style="margin-bottom:18px"><label>비밀번호</label><input type="password" name="p" autocomplete="current-password" required autofocus/></div>
    <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">로그인 →</button>
  </form>
</div>
</body></html>
