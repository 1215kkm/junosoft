<?php
/**
 * 견적 신청 폼 수신 (닷홈 PHP 환경 기준)
 * 동작:
 *   1. 입력 검증 + honeypot/시간 검증
 *   2. _data/inquiries/{ts}.json 저장 (관리자 페이지에서 조회)
 *   3. 관리자 메일 + 신청자 자동 회신 메일 발송
 *   4. 텔레그램 즉시 알림 (settings.json 의 봇 토큰/chat_id 사용)
 *   5. /thanks.html 로 리다이렉트
 */
declare(strict_types=1);
require __DIR__ . '/admin/_helpers.php';

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: apply.html'); exit; }

// ----- Anti-spam -----
if (!empty($_POST['website'])) { http_response_code(204); exit; }
$ts_form = isset($_POST['ts']) ? (int)$_POST['ts'] : 0;
if ($ts_form > 0 && (microtime(true) * 1000 - $ts_form) < 3000) { http_response_code(204); exit; }

// ----- Helpers -----
function s(string $k, int $max = 1000): string {
    $v = isset($_POST[$k]) ? (string)$_POST[$k] : '';
    $v = preg_replace("/[\r\n]+/u", ' ', trim($v));
    return mb_substr($v, 0, $max);
}
function arr_input(string $k): array {
    return isset($_POST[$k]) && is_array($_POST[$k]) ? array_map('strval', $_POST[$k]) : [];
}
function enc_subject(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }

// ----- Collect -----
$data = [
    'company'   => s('company', 80),
    'name'      => s('name', 40),
    'phone'     => s('phone', 20),
    'email'     => s('email', 80),
    'industry'  => s('industry', 40),
    'type'      => s('type', 40),
    'pages'     => s('pages', 40),
    'budget'    => s('budget', 40),
    'schedule'  => s('schedule', 40),
    'hosting'   => s('hosting', 80),
    'features'  => arr_input('features'),
    'reference' => s('reference', 200),
    'message'   => s('message', 2000),
    'ref'       => s('ref', 40),
    'package'   => s('package', 40),
];
$agree = isset($_POST['agree']);

// ----- Validate (간단 모드: 성함·연락처·이메일·동의만 필수) -----
$errors = [];
if ($data['name'] === '')    $errors[] = '성함';
if (!preg_match('/^[0-9\-+ ]{8,20}$/', $data['phone'])) $errors[] = '연락처';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))  $errors[] = '이메일';
if (!$agree) $errors[] = '개인정보 동의';

if ($errors) {
    http_response_code(400);
    echo '<meta charset="utf-8"><h2>입력 항목을 확인해 주세요</h2><p>누락/형식 오류: '
        . htmlspecialchars(implode(', ', $errors), ENT_QUOTES, 'UTF-8')
        . '</p><p><a href="apply.html">← 신청서로 돌아가기</a></p>';
    exit;
}

// ----- Save JSON (admin console 에서 조회) -----
$inq_id = date('Ymd_His_') . substr(bin2hex(random_bytes(3)), 0, 6);
$record = $data + [
    'status'      => 'new',
    'memo'        => '',
    'received_at' => date('Y-m-d H:i:s'),
    'ip'          => $_SERVER['REMOTE_ADDR'] ?? '-',
    'ua'          => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 200),
];
inq_save($inq_id, $record);

// ----- Build mail body -----
$lines = [
    "[새 견적 신청]",
    "신청일시: {$record['received_at']}",
    "참고 작품: " . ($data['ref'] ?: '-'),
    "관심 패키지: " . ($data['package'] ?: '-'),
    str_repeat('-', 40),
    "업체명: {$data['company']}",
    "담당자: {$data['name']}",
    "연락처: {$data['phone']}",
    "이메일: {$data['email']}",
    "업종: " . ($data['industry'] ?: '-'),
    "원하는 형태: " . ($data['type'] ?: '-'),
    "예상 페이지: " . ($data['pages'] ?: '-'),
    "예산: " . ($data['budget'] ?: '-'),
    "일정: " . ($data['schedule'] ?: '-'),
    "도메인/호스팅: " . ($data['hosting'] ?: '-'),
    "필요 기능: " . ($data['features'] ? implode(', ', $data['features']) : '-'),
    "참고 URL: " . ($data['reference'] ?: '-'),
    str_repeat('-', 40),
    "[요구사항]",
    $data['message'] ?: '(미작성)',
];
$body = implode("\n", $lines);

// ----- Settings -----
$cfg = settings_load();
$site = rtrim($cfg['site_url'] ?? 'https://junosoft.co.kr', '/');
$adminTo  = $cfg['admin_email'] ?: 'contact@junosoft.co.kr';
$fromAddr = $cfg['from_email']  ?: 'noreply@junosoft.co.kr';

// ----- Mail to admin -----
$headers  = "From: " . enc_subject('주노소프트 견적신청') . " <{$fromAddr}>\r\n";
$headers .= "Reply-To: {$data['email']}\r\n";
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
@mb_send_mail($adminTo, enc_subject("[견적신청] {$data['company']} / " . ($data['type'] ?: '미지정')), $body, $headers);

// ----- Auto-reply to applicant -----
$autoBody = "{$data['name']}님, 견적 신청이 정상 접수되었습니다.\n\n"
          . "1영업일(평일 10:00–18:00) 이내에 담당 PM이 회신드립니다.\n"
          . "급하신 경우 02-0000-0000 으로 연락 주세요.\n\n"
          . "[접수 내역]\n{$body}\n\n— 주노소프트 드림";
$autoHeaders = "From: " . enc_subject('주노소프트') . " <{$fromAddr}>\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
@mb_send_mail($data['email'], enc_subject("[주노소프트] 견적 신청이 접수되었습니다."), $autoBody, $autoHeaders);

// ----- Telegram -----
$tg_text = "🔔 <b>새 견적 신청</b>\n"
         . "<b>업체:</b> " . htmlspecialchars($data['company']) . "\n"
         . "<b>담당자:</b> " . htmlspecialchars($data['name']) . " · " . htmlspecialchars($data['phone']) . "\n"
         . "<b>이메일:</b> " . htmlspecialchars($data['email']) . "\n"
         . "<b>업종/형태:</b> " . htmlspecialchars(($data['industry'] ?: '-') . ' / ' . ($data['type'] ?: '-')) . "\n"
         . "<b>예산/일정:</b> " . htmlspecialchars(($data['budget'] ?: '-') . ' / ' . ($data['schedule'] ?: '-')) . "\n"
         . ($data['ref']     ? "<b>참고 작품:</b> " . htmlspecialchars($data['ref']) . "\n" : '')
         . ($data['package'] ? "<b>관심 패키지:</b> " . htmlspecialchars($data['package']) . "\n" : '')
         . "\n<b>메시지:</b>\n" . htmlspecialchars(mb_substr($data['message'] ?: '(미작성)', 0, 500))
         . "\n\n👉 {$site}/admin/inquiry.php?id={$inq_id}";
@tg_notify($tg_text);

header('Location: thanks.html'); exit;
