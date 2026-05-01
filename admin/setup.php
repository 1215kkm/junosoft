<?php
require __DIR__ . '/_helpers.php';

if (admin_is_setup()) { header('Location: login.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = (string)($_POST['p1'] ?? '');
    $p2 = (string)($_POST['p2'] ?? '');
    if (mb_strlen($p1) < 8) {
        $err = '비밀번호는 8자 이상이어야 합니다.';
    } elseif ($p1 !== $p2) {
        $err = '두 비밀번호가 일치하지 않습니다.';
    } else {
        admin_set_password($p1);
        $_SESSION['admin'] = true;
        $_SESSION['flash'] = ['type'=>'ok','msg'=>'관리자 비밀번호가 설정되었습니다.'];
        header('Location: index.php'); exit;
    }
}
?><!doctype html>
<html lang="ko"><head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />
<title>관리자 초기 설정 | 주노소프트</title>
<meta name="robots" content="noindex,nofollow" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
<link rel="stylesheet" href="../assets/css/style.css" />
<style>body{background:#0B1F3A;display:grid;place-items:center;min-height:100vh;padding:20px}.box{background:#fff;border-radius:16px;padding:40px;width:100%;max-width:440px;box-shadow:0 20px 50px rgba(0,0,0,.2)}</style>
</head><body>
<div class="box">
  <h2 style="margin:0 0 6px;color:#0B1F3A;font-size:24px">관리자 초기 설정</h2>
  <p class="muted" style="margin:0 0 22px;font-size:14px">최초 1회 비밀번호를 설정하세요. 이 화면은 설정 완료 후 사라집니다.</p>
  <?php if ($err): ?><div class="flash err" style="background:#FBEAE9;color:#B0322B;border:1px solid #F2C5C2;padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13.5px"><?= h($err) ?></div><?php endif; ?>
  <form method="post">
    <div class="field" style="margin-bottom:14px"><label>비밀번호 (8자 이상)</label><input type="password" name="p1" autocomplete="new-password" required minlength="8" autofocus/></div>
    <div class="field" style="margin-bottom:18px"><label>비밀번호 확인</label><input type="password" name="p2" autocomplete="new-password" required minlength="8"/></div>
    <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">비밀번호 설정 →</button>
  </form>
</div>
</body></html>
