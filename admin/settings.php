<?php
require __DIR__ . '/_helpers.php';
require_login();

$active = 'set';
$page_title = '설정';

$s = settings_load();
$test_result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'save') {
        $s['admin_email'] = trim((string)($_POST['admin_email'] ?? ''));
        $s['from_email']  = trim((string)($_POST['from_email']  ?? '')) ?: 'noreply@junosoft.co.kr';
        $s['tg_token']    = trim((string)($_POST['tg_token']    ?? ''));
        $s['tg_chat_id']  = trim((string)($_POST['tg_chat_id']  ?? ''));
        $s['site_url']    = trim((string)($_POST['site_url']    ?? '')) ?: 'https://junosoft.co.kr';
        settings_save($s);
        $_SESSION['flash']=['type'=>'ok','msg'=>'설정이 저장되었습니다.'];
        header('Location: settings.php'); exit;
    }
    if ($action === 'test_tg') {
        $ok = tg_notify("✅ <b>주노소프트 관리자 콘솔</b>\n텔레그램 알림 테스트 메시지입니다. 이 메시지가 보이면 정상 연결된 것입니다.\n\n시간: " . date('Y-m-d H:i:s'));
        $_SESSION['flash']=['type'=>$ok?'ok':'err','msg'=>$ok?'테스트 메시지를 발송했습니다. 텔레그램을 확인해 주세요.':'발송 실패. 토큰/Chat ID와 봇이 chat에 추가되었는지 확인해 주세요.'];
        header('Location: settings.php'); exit;
    }
    if ($action === 'seo_refresh') {
        $r = seo_refresh_all();
        $msg = "sitemap.xml({$r['urls']}건)·feed.xml({$r['feed']}건) 갱신 완료. ";
        $msg .= ($r['ping']['ok'] ?? false) ? "IndexNow 색인 요청 OK ({$r['ping']['count']}건)." : "IndexNow 발송 실패 또는 미적용 (사이트 URL 미설정).";
        $_SESSION['flash']=['type'=>($r['ping']['ok']??false)?'ok':'err','msg'=>$msg];
        header('Location: settings.php#seo'); exit;
    }
    if ($action === 'change_pw') {
        $cur = (string)($_POST['cur'] ?? '');
        $p1  = (string)($_POST['p1']  ?? '');
        $p2  = (string)($_POST['p2']  ?? '');
        if (!password_verify($cur, (string)admin_password_hash())) {
            $_SESSION['flash']=['type'=>'err','msg'=>'현재 비밀번호가 일치하지 않습니다.'];
        } elseif (mb_strlen($p1) < 8 || $p1 !== $p2) {
            $_SESSION['flash']=['type'=>'err','msg'=>'새 비밀번호가 유효하지 않거나 일치하지 않습니다 (8자 이상).'];
        } else {
            admin_set_password($p1);
            $_SESSION['flash']=['type'=>'ok','msg'=>'비밀번호가 변경되었습니다.'];
        }
        header('Location: settings.php'); exit;
    }
}

ob_start();
?>
<?php $seo = seo_status(); ?>
<div class="admin-h"><h1>설정</h1></div>

<form method="post" class="crd" id="seo">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
  <input type="hidden" name="action" value="seo_refresh"/>
  <h3 style="margin:0 0 6px;color:#0B1F3A;font-size:17px">🔍 검색엔진 자동 노출 (SEO)</h3>
  <p class="muted" style="margin:0 0 14px;font-size:13.5px">
    포트폴리오 추가/수정/삭제와 블로그 자동 발행 시 sitemap.xml·feed.xml이 자동으로 갱신되고 IndexNow API로 즉시 색인 요청이 전송됩니다 (Bing·네이버·Yandex·Seznam 즉시 반영, 구글은 sitemap 기반 발견).
  </p>
  <div class="kv-tbl" style="font-size:13.5px">
    <b>sitemap.xml</b><span><?= $seo['sitemap_exists'] ? '생성됨 · 마지막 갱신 '.date('Y-m-d H:i', $seo['sitemap_mtime']) : '<span style="color:#B0322B">미생성 — 아래 버튼으로 생성</span>' ?> · <a href="../sitemap.xml" target="_blank">파일 보기 ↗</a></span>
    <b>feed.xml (RSS)</b><span><?= $seo['feed_exists'] ? '생성됨 · 마지막 갱신 '.date('Y-m-d H:i', $seo['feed_mtime']) : '<span style="color:#B0322B">미생성</span>' ?> · <a href="../feed.xml" target="_blank">파일 보기 ↗</a></span>
    <b>IndexNow 키</b><span><?= $seo['indexnow_key'] ? h(substr($seo['indexnow_key'],0,8)).'…'.h(substr($seo['indexnow_key'],-4)).' (자동 생성됨)' : '<span style="color:#B0322B">아직 생성 안 됨</span>' ?></span>
    <b>사이트 URL</b><span><?= $seo['site_url'] ? h($seo['site_url']) : '<span style="color:#B0322B">미설정 — 아래 메일 섹션에서 설정 후 갱신</span>' ?></span>
  </div>
  <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
    <button class="btn btn-primary" type="submit">지금 sitemap·feed 갱신 + IndexNow 색인 요청</button>
    <a class="btn-sm" href="../sitemap.xml" target="_blank">sitemap.xml ↗</a>
    <a class="btn-sm" href="../feed.xml" target="_blank">feed.xml ↗</a>
    <a class="btn-sm" href="https://search.google.com/search-console" target="_blank">Google Search Console ↗</a>
    <a class="btn-sm" href="https://searchadvisor.naver.com" target="_blank">네이버 서치어드바이저 ↗</a>
  </div>
</form>

<form method="post" class="crd">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
  <h3 style="margin:0 0 14px;color:#0B1F3A;font-size:17px">📧 메일</h3>
  <div class="field-row">
    <div class="field">
      <label>관리자 수신 메일 <small style="color:#5C6577;font-weight:500">(문의 접수 시 알림 받을 주소)</small></label>
      <input type="email" name="admin_email" value="<?= h($s['admin_email'] ?? '') ?>" placeholder="contact@junosoft.co.kr"/>
    </div>
    <div class="field">
      <label>발신 메일 (From) <small style="color:#5C6577;font-weight:500">(도메인 메일 권장. SPF 일치)</small></label>
      <input type="email" name="from_email" value="<?= h($s['from_email'] ?? 'noreply@junosoft.co.kr') ?>"/>
    </div>
    <div class="field full">
      <label>사이트 URL <small style="color:#5C6577;font-weight:500">(텔레그램 메시지에 포함)</small></label>
      <input type="url" name="site_url" value="<?= h($s['site_url'] ?? 'https://junosoft.co.kr') ?>"/>
    </div>
  </div>

  <hr style="border:0;border-top:1px solid #E6E8EE;margin:20px 0">

  <h3 style="margin:0 0 6px;color:#0B1F3A;font-size:17px">📨 텔레그램 알림</h3>
  <p class="muted" style="margin:0 0 14px;font-size:13.5px">문의가 들어오면 텔레그램으로 즉시 알림이 갑니다. 봇 생성과 chat_id 확인 방법은 아래 안내를 참고하세요.</p>
  <div class="field-row">
    <div class="field">
      <label>봇 토큰 (Bot Token)</label>
      <input type="text" name="tg_token" value="<?= h($s['tg_token'] ?? '') ?>" placeholder="123456789:ABC-DEF..." autocomplete="off"/>
    </div>
    <div class="field">
      <label>Chat ID <small style="color:#5C6577;font-weight:500">(개인은 본인 ID, 그룹은 -로 시작)</small></label>
      <input type="text" name="tg_chat_id" value="<?= h($s['tg_chat_id'] ?? '') ?>" placeholder="123456789 또는 -100123456789" autocomplete="off"/>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
    <button class="btn btn-primary" type="submit" name="action" value="save">설정 저장</button>
    <button class="btn-sm" type="submit" name="action" value="test_tg">텔레그램 테스트 메시지 보내기</button>
  </div>
</form>

<div class="crd" style="background:#F6F7FB">
  <h3 style="margin:0 0 10px;color:#0B1F3A;font-size:15px">🤖 텔레그램 봇 만들기 (3분)</h3>
  <ol style="margin:0;padding-left:20px;color:#3d465c;font-size:14px;line-height:1.85">
    <li>텔레그램에서 <b>@BotFather</b> 검색 → 대화 시작 → <code>/newbot</code></li>
    <li>봇 이름과 사용자명(끝이 <code>_bot</code>) 입력 → <b>봇 토큰</b>이 나옴 → 위 "봇 토큰" 칸에 붙여넣기</li>
    <li>방금 만든 내 봇과 <b>대화 시작</b>(아무 메시지나 보내기). 그룹으로 받고 싶다면 그룹에 봇을 초대.</li>
    <li>브라우저에서 <code>https://api.telegram.org/bot&lt;토큰&gt;/getUpdates</code> 접속 → JSON 안의 <code>chat.id</code> 숫자가 <b>Chat ID</b>. 위 칸에 입력 후 저장.</li>
    <li>"텔레그램 테스트 메시지 보내기" 버튼으로 검증.</li>
  </ol>
</div>

<form method="post" class="crd">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
  <input type="hidden" name="action" value="change_pw"/>
  <h3 style="margin:0 0 14px;color:#0B1F3A;font-size:17px">🔒 관리자 비밀번호 변경</h3>
  <div class="field-row">
    <div class="field"><label>현재 비밀번호</label><input type="password" name="cur" autocomplete="current-password" required/></div>
    <div class="field"></div>
    <div class="field"><label>새 비밀번호 (8자 이상)</label><input type="password" name="p1" autocomplete="new-password" minlength="8" required/></div>
    <div class="field"><label>새 비밀번호 확인</label><input type="password" name="p2" autocomplete="new-password" minlength="8" required/></div>
  </div>
  <button class="btn-sm" type="submit">비밀번호 변경</button>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
