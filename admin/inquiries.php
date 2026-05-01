<?php
require __DIR__ . '/_helpers.php';
require_login();

$active = 'inq';
$page_title = '문의 관리';

$q = trim((string)($_GET['q'] ?? ''));
$st_filter = (string)($_GET['st'] ?? '');

$files = inq_list();
$rows = [];
foreach ($files as $f) {
    $d = json_decode((string)file_get_contents($f), true) ?: [];
    $d['_id'] = basename($f, '.json');
    if ($q !== '') {
        $hay = mb_strtolower(($d['company']??'').' '.($d['name']??'').' '.($d['phone']??'').' '.($d['email']??'').' '.($d['industry']??'').' '.($d['message']??''));
        if (mb_strpos($hay, mb_strtolower($q)) === false) continue;
    }
    if ($st_filter !== '' && ($d['status'] ?? 'new') !== $st_filter) continue;
    $rows[] = $d;
}

ob_start();
?>
<div class="admin-h">
  <h1>문의 관리 <span style="color:#5C6577;font-size:15px;font-weight:500">(전체 <?= count($files) ?>건 · 표시 <?= count($rows) ?>건)</span></h1>
</div>

<div class="crd">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
    <input type="search" name="q" value="<?= h($q) ?>" placeholder="업체·담당자·연락처·메시지 검색" style="flex:1;min-width:240px;border:1px solid #E6E8EE;border-radius:10px;padding:10px 14px;font-size:14px"/>
    <select name="st" style="border:1px solid #E6E8EE;border-radius:10px;padding:10px 14px;font-size:14px">
      <option value="">전체 상태</option>
      <option value="new"  <?= $st_filter==='new'?'selected':'' ?>>미확인</option>
      <option value="read" <?= $st_filter==='read'?'selected':'' ?>>확인</option>
      <option value="done" <?= $st_filter==='done'?'selected':'' ?>>완료</option>
    </select>
    <button class="btn-sm" type="submit">검색</button>
    <?php if ($q || $st_filter): ?><a class="btn-sm" href="inquiries.php">초기화</a><?php endif; ?>
  </form>

  <?php if (empty($rows)): ?>
    <p class="muted" style="text-align:center;padding:40px 0">조건에 맞는 문의가 없습니다.</p>
  <?php else: ?>
    <table class="t">
      <thead><tr>
        <th style="width:160px">접수일시</th>
        <th>업체명</th>
        <th>담당자</th>
        <th>연락처</th>
        <th>업종</th>
        <th>형태</th>
        <th>예산</th>
        <th style="width:80px">상태</th>
        <th style="width:60px"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['received_at'] ?? '') ?></td>
            <td><b><?= h($r['company'] ?? '') ?></b><?= !empty($r['ref']) ? '<br/><span style="color:#5C6577;font-size:12px">참고: '.h($r['ref']).'</span>' : '' ?></td>
            <td><?= h($r['name'] ?? '') ?></td>
            <td><?= h($r['phone'] ?? '') ?></td>
            <td><?= h($r['industry'] ?? '-') ?></td>
            <td><?= h($r['type'] ?? '-') ?></td>
            <td><?= h($r['budget'] ?? '-') ?></td>
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
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
