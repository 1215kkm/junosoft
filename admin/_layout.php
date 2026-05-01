<?php
/**
 * 관리자 페이지 레이아웃
 * 사용: 페이지 상단에서 require_login() 후 ob_start();
 *       페이지 하단에서 $page_title, $body = ob_get_clean(); include '_layout.php';
 */
$active = $active ?? '';
?><!doctype html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= h($page_title ?? '관리자') ?> | 주노소프트</title>
<meta name="robots" content="noindex,nofollow" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
<link rel="stylesheet" href="../assets/css/style.css" />
<style>
  body{background:#F4F6FB}
  .admin-shell{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
  .admin-side{background:#0B1F3A;color:#cfd6e3;padding:24px 0}
  .admin-side .brand{padding:0 22px 18px;color:#fff;font-weight:800;font-size:18px;border-bottom:1px solid #1d3358;margin-bottom:14px;display:flex;align-items:center;gap:8px}
  .admin-side .brand .dot{width:10px;height:10px;border-radius:50%;background:#C8A85A}
  .admin-side a{display:block;padding:10px 22px;color:#cfd6e3;font-weight:500;font-size:14.5px}
  .admin-side a:hover,.admin-side a.on{background:#13315C;color:#fff;border-left:3px solid #C8A85A;padding-left:19px}
  .admin-side .out{margin-top:auto;padding:14px 22px;color:#7d889e;font-size:12.5px;border-top:1px solid #1d3358}
  .admin-side .out a{padding:6px 0;color:#7d889e;font-size:12.5px}
  .admin-main{padding:30px 32px 60px;max-width:1100px}
  .admin-h{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:10px}
  .admin-h h1{margin:0;color:#0B1F3A;font-size:26px;letter-spacing:-.02em}
  .crd{background:#fff;border:1px solid #E6E8EE;border-radius:14px;padding:24px;margin-bottom:18px}
  .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
  .stat-row .crd{padding:18px;text-align:center}
  .stat-row .num{font-size:30px;font-weight:800;color:#0B1F3A}
  .stat-row .lbl{color:#5C6577;font-size:13px;margin-top:4px}
  table.t{width:100%;border-collapse:collapse}
  table.t th,table.t td{padding:11px 12px;text-align:left;border-bottom:1px solid #E6E8EE;font-size:14px;vertical-align:top}
  table.t th{background:#F6F7FB;color:#0B1F3A;font-size:12.5px;font-weight:700;letter-spacing:.02em}
  table.t tr:hover td{background:#FAFBFE}
  .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:700}
  .b-new{background:#FFF1E5;color:#B05B00}
  .b-read{background:#EAF1F8;color:#3B5C84}
  .b-done{background:#E6F4EA;color:#1F7A3A}
  .btn-sm{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;border:1px solid #E6E8EE;background:#fff;color:#0B1F3A}
  .btn-sm:hover{background:#0B1F3A;color:#fff;border-color:#0B1F3A}
  .btn-sm.danger{color:#B0322B;border-color:#F2C5C2}
  .btn-sm.danger:hover{background:#B0322B;color:#fff;border-color:#B0322B}
  .kv-tbl{display:grid;grid-template-columns:160px 1fr;gap:8px 16px;font-size:14px}
  .kv-tbl b{color:#0B1F3A;font-weight:700}
  .field-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
  .field-row .field.full{grid-column:1/-1}
  .field input,.field select,.field textarea{border:1px solid #E6E8EE;border-radius:10px;padding:11px 13px;font:inherit;font-size:14.5px;background:#fff;width:100%;outline:none}
  .field input:focus,.field select:focus,.field textarea:focus{border-color:#0B1F3A}
  .field label{font-weight:700;color:#0B1F3A;font-size:13.5px;margin-bottom:5px;display:block}
  .field textarea{min-height:120px;resize:vertical}
  .flash{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px}
  .flash.ok{background:#E6F4EA;color:#1F7A3A;border:1px solid #B7E0C5}
  .flash.err{background:#FBEAE9;color:#B0322B;border:1px solid #F2C5C2}
  @media (max-width:900px){
    .admin-shell{grid-template-columns:1fr}
    .admin-side{display:flex;overflow-x:auto;padding:8px}
    .admin-side .brand{padding:6px 12px;border:0;margin:0}
    .admin-side a{padding:8px 12px;white-space:nowrap;border:0!important}
    .admin-side a.on{border:0!important;padding:8px 12px}
    .admin-side .out{display:none}
    .stat-row{grid-template-columns:repeat(2,1fr)}
    .field-row{grid-template-columns:1fr}
  }
</style>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-side">
    <div class="brand"><span class="dot"></span><span>관리자 콘솔</span></div>
    <a href="index.php"      class="<?= $active==='dash'?'on':'' ?>">대시보드</a>
    <a href="inquiries.php"  class="<?= $active==='inq'?'on':'' ?>">문의 관리</a>
    <a href="portfolios.php" class="<?= $active==='pf'?'on':'' ?>">포트폴리오 관리</a>
    <a href="settings.php"   class="<?= $active==='set'?'on':'' ?>">설정 (텔레그램)</a>
    <a href="../" target="_blank">사이트 보기 ↗</a>
    <div class="out">
      관리자 로그인 중<br/>
      <a href="logout.php">로그아웃 →</a>
    </div>
  </aside>
  <main class="admin-main">
    <?php if (!empty($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?= $f['type']==='err'?'err':'ok' ?>"><?= h($f['msg']) ?></div>
    <?php endif; ?>
    <?= $body ?? '' ?>
  </main>
</div>
</body>
</html>
