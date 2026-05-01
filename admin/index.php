<?php
require __DIR__ . '/_helpers.php';
require_login();

$active = 'dash';
$page_title = '대시보드';

$files = inq_list();
$total = count($files);
$unread = 0;
$recent = [];
foreach ($files as $i => $f) {
    $d = json_decode((string)file_get_contents($f), true) ?: [];
    if (empty($d['status']) || $d['status'] === 'new') $unread++;
    if ($i < 6) {
        $d['_id'] = basename($f, '.json');
        $recent[] = $d;
    }
}
$pf_count = count(portfolio_load());
$s = settings_load();
$tg_ok = !empty($s['tg_token']) && !empty($s['tg_chat_id']);

ob_start();
?>
<div class="admin-h">
  <h1>대시보드</h1>
  <a class="btn-sm" href="../" target="_blank">사이트 보기 ↗</a>
</div>

<div class="stat-row">
  <div class="crd"><div class="num"><?= $unread ?></div><div class="lbl">미확인 문의</div></div>
  <div class="crd"><div class="num"><?= $total ?></div><div class="lbl">전체 문의</div></div>
  <div class="crd"><div class="num"><?= $pf_count ?></div><div class="lbl">등록된 포트폴리오</div></div>
  <div class="crd"><div class="num" style="font-size:18px;color:<?= $tg_ok?'#1F7A3A':'#B0322B' ?>"><?= $tg_ok?'정상':'미설정' ?></div><div class="lbl">텔레그램 알림</div></div>
</div>

<div class="crd">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3 style="margin:0;color:#0B1F3A;font-size:18px">최근 문의</h3>
    <a class="btn-sm" href="inquiries.php">전체 보기 →</a>
  </div>
  <?php if (empty($recent)): ?>
    <p class="muted" style="text-align:center;padding:30px 0">아직 접수된 문의가 없습니다.</p>
  <?php else: ?>
    <table class="t">
      <thead><tr><th style="width:160px">접수일시</th><th>업체명</th><th>담당자</th><th>업종</th><th>형태</th><th style="width:80px">상태</th><th style="width:60px"></th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= h($r['received_at'] ?? '') ?></td>
            <td><b><?= h($r['company'] ?? '') ?></b></td>
            <td><?= h($r['name'] ?? '') ?></td>
            <td><?= h($r['industry'] ?? '-') ?></td>
            <td><?= h($r['type'] ?? '-') ?></td>
            <td>
              <?php $st = $r['status'] ?? 'new'; ?>
              <span class="badge <?= $st==='new'?'b-new':($st==='done'?'b-done':'b-read') ?>"><?= $st==='new'?'미확인':($st==='done'?'완료':'확인') ?></span>
            </td>
            <td><a class="btn-sm" href="inquiry.php?id=<?= h($r['_id']) ?>">보기</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php if (!$tg_ok): ?>
<div class="crd" style="border-color:#F2D88B;background:#FFF7E5">
  <h3 style="margin:0 0 6px;color:#7a5a14;font-size:16px">⚠ 텔레그램 알림이 설정되지 않았습니다.</h3>
  <p style="margin:0;color:#7a5a14;font-size:13.5px">
    문의가 들어와도 텔레그램으로 알림이 가지 않습니다.
    <a href="settings.php" style="text-decoration:underline">설정 페이지</a>에서 봇 토큰과 chat_id를 입력해 주세요.
  </p>
</div>
<?php endif; ?>

<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
