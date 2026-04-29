<?php
/**
 * 견적 신청 폼 수신 (닷홈 PHP 환경 기준)
 * - 한글 메일 깨짐 방지: UTF-8 + Base64 인코딩 헤더
 * - 스팸 방지: honeypot + 최소 응답 시간
 */
declare(strict_types=1);
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /apply.html'); exit;
}

// ----- Anti-spam -----
if (!empty($_POST['website'])) { http_response_code(204); exit; }
$ts = isset($_POST['ts']) ? (int)$_POST['ts'] : 0;
if ($ts > 0 && (microtime(true) * 1000 - $ts) < 3000) {
    http_response_code(204); exit; // 3초 미만 자동 제출 차단
}

// ----- Helpers -----
function s(string $k, int $max = 1000): string {
    $v = isset($_POST[$k]) ? (string)$_POST[$k] : '';
    $v = trim($v);
    $v = preg_replace("/[\r\n]+/u", ' ', $v); // 헤더 인젝션 방지
    return mb_substr($v, 0, $max);
}
function arr(string $k): array {
    return isset($_POST[$k]) && is_array($_POST[$k]) ? array_map('strval', $_POST[$k]) : [];
}
function enc_subject(string $s): string {
    return '=?UTF-8?B?' . base64_encode($s) . '?=';
}

$company  = s('company', 80);
$name     = s('name', 40);
$phone    = s('phone', 20);
$email    = s('email', 80);
$industry = s('industry', 40);
$type     = s('type', 40);
$pages    = s('pages', 40);
$budget   = s('budget', 40);
$schedule = s('schedule', 40);
$hosting  = s('hosting', 80);
$features = arr('features');
$reference= s('reference', 200);
$message  = s('message', 2000);
$ref      = s('ref', 40);
$package  = s('package', 40);
$agree    = isset($_POST['agree']);

// ----- Validate -----
$errors = [];
if ($company === '') $errors[] = '업체명';
if ($name === '')    $errors[] = '담당자';
if (!preg_match('/^[0-9\-+ ]{8,20}$/', $phone)) $errors[] = '연락처';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = '이메일';
if (!$agree) $errors[] = '개인정보 동의';

if ($errors) {
    http_response_code(400);
    echo '<meta charset="utf-8"><h2>입력 항목을 확인해 주세요</h2><p>누락/형식 오류: '
        . htmlspecialchars(implode(', ', $errors), ENT_QUOTES, 'UTF-8')
        . '</p><p><a href="/apply.html">← 신청서로 돌아가기</a></p>';
    exit;
}

// ----- Compose mail -----
$ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '-';
$now  = date('Y-m-d H:i:s');

$lines = [
    "[새 견적 신청]",
    "신청일시: {$now}",
    "참고 작품: " . ($ref ?: '-'),
    "관심 패키지: " . ($package ?: '-'),
    str_repeat('-', 40),
    "업체명: {$company}",
    "담당자: {$name}",
    "연락처: {$phone}",
    "이메일: {$email}",
    "업종: " . ($industry ?: '-'),
    "원하는 형태: " . ($type ?: '-'),
    "예상 페이지: " . ($pages ?: '-'),
    "예산: " . ($budget ?: '-'),
    "일정: " . ($schedule ?: '-'),
    "도메인/호스팅: " . ($hosting ?: '-'),
    "필요 기능: " . ($features ? implode(', ', $features) : '-'),
    "참고 URL: " . ($reference ?: '-'),
    str_repeat('-', 40),
    "[요구사항]",
    $message ?: '(미작성)',
    str_repeat('-', 40),
    "IP: {$ip}",
    "UA: {$ua}",
];
$body = implode("\n", $lines);

// ----- Send -----
$adminTo   = 'contact@junosoft.co.kr'; // ← 닷홈에 올린 뒤 실제 수신 주소로 교체
$fromAddr  = 'noreply@junosoft.co.kr';
$fromName  = '주노소프트 견적신청';

$headers  = "From: " . enc_subject($fromName) . " <{$fromAddr}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$subject = enc_subject("[견적신청] {$company} / {$type}");

@mb_send_mail($adminTo, $subject, $body, $headers);

// 자동 회신
$autoBody = "{$name}님, 견적 신청이 정상 접수되었습니다.\n\n"
          . "1영업일(평일 10:00–18:00) 이내에 담당 PM이 회신드릴 예정입니다.\n"
          . "급하신 경우 02-0000-0000 으로 연락 주세요.\n\n"
          . "[접수 내역]\n{$body}\n\n— 주노소프트 드림";
$autoSubject = enc_subject("[주노소프트] 견적 신청이 접수되었습니다.");
$autoHeaders = "From: " . enc_subject('주노소프트') . " <{$fromAddr}>\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
@mb_send_mail($email, $autoSubject, $autoBody, $autoHeaders);

// 로컬 백업 (메일 실패 대비)
$logDir = __DIR__ . '/_inbox';
if (!is_dir($logDir)) @mkdir($logDir, 0750);
@file_put_contents($logDir . '/' . date('Ymd_His_') . preg_replace('/\W+/', '_', $email) . '.txt', $body);

header('Location: /thanks.html'); exit;
