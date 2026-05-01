<?php
require __DIR__ . '/_helpers.php';
require_login();

$id = (string)($_GET['id'] ?? '');
$inq = inq_load($id);
if (!$inq) { $_SESSION['flash']=['type'=>'err','msg'=>'문의를 찾을 수 없습니다.']; header('Location: inquiries.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'status') {
        $inq['status'] = in_array($_POST['status'] ?? '', ['new','read','done'], true) ? $_POST['status'] : 'read';
        $inq['memo']   = trim((string)($_POST['memo'] ?? ''));
        unset($inq['_id'], $inq['_file']);
        inq_save($id, $inq);
        $_SESSION['flash']=['type'=>'ok','msg'=>'저장되었습니다.'];
        header('Location: inquiry.php?id=' . urlencode($id)); exit;
    }
    if ($action === 'delete') {
        inq_delete($id);
        $_SESSION['flash']=['type'=>'ok','msg'=>'삭제되었습니다.'];
        header('Location: inquiries.php'); exit;
    }
}

// 처음 열람 시 자동으로 read 처리
if (($inq['status'] ?? 'new') === 'new') {
    $inq['status'] = 'read';
    $tmp = $inq; unset($tmp['_id'], $tmp['_file']);
    inq_save($id, $tmp);
}

$active = 'inq';
$page_title = '문의 상세';

ob_start();
?>
<div class="admin-h">
  <h1>문의 상세</h1>
  <a class="btn-sm" href="inquiries.php">← 목록</a>
</div>

<div class="crd">
  <h3 style="margin:0 0 14px;color:#0B1F3A;font-size:18px"><?= h($inq['company'] ?? '') ?> · <?= h($inq['name'] ?? '') ?></h3>

  <div class="kv-tbl">
    <b>접수일시</b><span><?= h($inq['received_at'] ?? '') ?></span>
    <b>참고 작품</b><span><?= !empty($inq['ref']) ? '<a href="../portfolio.html#'.h($inq['ref']).'" target="_blank">'.h($inq['ref']).'</a>' : '-' ?></span>
    <b>관심 패키지</b><span><?= h($inq['package'] ?? '-') ?></span>
    <b>업체명</b><span><?= h($inq['company'] ?? '') ?></span>
    <b>담당자</b><span><?= h($inq['name'] ?? '') ?></span>
    <b>연락처</b><span><a href="tel:<?= h($inq['phone'] ?? '') ?>"><?= h($inq['phone'] ?? '') ?></a></span>
    <b>이메일</b><span><a href="mailto:<?= h($inq['email'] ?? '') ?>"><?= h($inq['email'] ?? '') ?></a></span>
    <b>업종</b><span><?= h($inq['industry'] ?? '-') ?></span>
    <b>원하는 형태</b><span><?= h($inq['type'] ?? '-') ?></span>
    <b>예상 페이지</b><span><?= h($inq['pages'] ?? '-') ?></span>
    <b>예산</b><span><?= h($inq['budget'] ?? '-') ?></span>
    <b>희망 일정</b><span><?= h($inq['schedule'] ?? '-') ?></span>
    <b>도메인/호스팅</b><span><?= h($inq['hosting'] ?? '-') ?></span>
    <b>필요 기능</b><span><?= !empty($inq['features']) ? h(implode(', ', $inq['features'])) : '-' ?></span>
    <b>참고 URL</b><span><?= !empty($inq['reference']) ? '<a href="'.h($inq['reference']).'" target="_blank" rel="noopener">'.h($inq['reference']).'</a>' : '-' ?></span>
    <b>IP / UA</b><span style="color:#5C6577;font-size:12.5px"><?= h($inq['ip'] ?? '-') ?> · <?= h($inq['ua'] ?? '-') ?></span>
  </div>

  <div style="margin-top:18px;padding-top:18px;border-top:1px solid #E6E8EE">
    <b style="color:#0B1F3A">요구사항</b>
    <p style="margin:8px 0 0;white-space:pre-wrap;line-height:1.7"><?= h($inq['message'] ?? '(미작성)') ?></p>
  </div>
</div>

<form method="post" class="crd">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
  <input type="hidden" name="action" value="status"/>
  <h3 style="margin:0 0 14px;color:#0B1F3A;font-size:16px">처리 상태 / 메모</h3>
  <div class="field-row">
    <div class="field">
      <label>상태</label>
      <select name="status">
        <?php foreach (['new'=>'미확인','read'=>'확인','done'=>'완료'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($inq['status']??'new')===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"></div>
    <div class="field full">
      <label>내부 메모</label>
      <textarea name="memo" placeholder="처리 내용·통화 결과·견적 발송 여부 등"><?= h($inq['memo'] ?? '') ?></textarea>
    </div>
  </div>
  <div style="display:flex;gap:8px;justify-content:space-between;flex-wrap:wrap">
    <button class="btn btn-primary" type="submit">저장</button>
    <button class="btn-sm danger" type="submit" name="action" value="delete" onclick="return confirm('정말 삭제하시겠습니까? 복구할 수 없습니다.')">삭제</button>
  </div>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
