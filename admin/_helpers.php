<?php
/**
 * 관리자 공통 헬퍼
 */
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
mb_internal_encoding('UTF-8');

const DATA_DIR     = __DIR__ . '/../_data';
const INQ_DIR      = DATA_DIR . '/inquiries';
const SETTINGS     = DATA_DIR . '/settings.json';
const ADMIN_CONF   = __DIR__ . '/_config.php';
const PORTFOLIO_FILE = __DIR__ . '/../assets/data/portfolio.json';

if (!is_dir(DATA_DIR))   @mkdir(DATA_DIR, 0750, true);
if (!is_dir(INQ_DIR))    @mkdir(INQ_DIR, 0750, true);

function admin_is_setup(): bool {
    return file_exists(ADMIN_CONF);
}
function admin_password_hash(): ?string {
    if (!admin_is_setup()) return null;
    $hash = null; require ADMIN_CONF; return $hash;
}
function admin_set_password(string $plain): void {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $php  = "<?php\n// 자동 생성된 관리자 설정. 수동 편집 가능.\n\$hash = " . var_export($hash, true) . ";\n";
    file_put_contents(ADMIN_CONF, $php);
    @chmod(ADMIN_CONF, 0640);
}
function admin_login_ok(): bool {
    return !empty($_SESSION['admin']) && $_SESSION['admin'] === true;
}
function require_login(): void {
    if (!admin_is_setup()) { header('Location: setup.php'); exit; }
    if (!admin_login_ok()) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(400); exit('CSRF token mismatch');
    }
}
function settings_load(): array {
    if (!file_exists(SETTINGS)) return [
        'admin_email' => '', 'from_email' => 'noreply@junosoft.co.kr',
        'tg_token' => '', 'tg_chat_id' => '',
        'site_url' => 'https://junosoft.co.kr',
    ];
    return json_decode((string)file_get_contents(SETTINGS), true) ?: [];
}
function settings_save(array $s): void {
    file_put_contents(SETTINGS, json_encode($s, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @chmod(SETTINGS, 0640);
}
function inq_list(): array {
    $files = glob(INQ_DIR . '/*.json') ?: [];
    rsort($files);
    return $files;
}
function inq_load(string $id): ?array {
    $id = preg_replace('/[^0-9A-Za-z_\-]/', '', $id);
    $f  = INQ_DIR . "/{$id}.json";
    if (!is_file($f)) return null;
    $d = json_decode((string)file_get_contents($f), true);
    if (!$d) return null;
    $d['_id'] = $id; $d['_file'] = $f;
    return $d;
}
function inq_save(string $id, array $d): void {
    $id = preg_replace('/[^0-9A-Za-z_\-]/', '', $id);
    file_put_contents(INQ_DIR . "/{$id}.json", json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function inq_delete(string $id): void {
    $id = preg_replace('/[^0-9A-Za-z_\-]/', '', $id);
    @unlink(INQ_DIR . "/{$id}.json");
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function tg_notify(string $text): bool {
    $s = settings_load();
    if (empty($s['tg_token']) || empty($s['tg_chat_id'])) return false;
    $url = 'https://api.telegram.org/bot' . $s['tg_token'] . '/sendMessage';
    $payload = ['chat_id' => $s['tg_chat_id'], 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_TIMEOUT => 8,
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return $code === 200 && $res !== false;
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($payload), 'timeout' => 8,
    ]]);
    return @file_get_contents($url, false, $ctx) !== false;
}

function portfolio_load(): array {
    if (!is_file(PORTFOLIO_FILE)) return [];
    return json_decode((string)file_get_contents(PORTFOLIO_FILE), true) ?: [];
}
function portfolio_save(array $list): void {
    file_put_contents(PORTFOLIO_FILE, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

require_once __DIR__ . '/_seo.php';
