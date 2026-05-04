<?php
require __DIR__ . '/_helpers.php';
require_login();

$active = 'kw';
$page_title = '키워드 도구';

const TOPICS_FILE = __DIR__ . '/../tools/auto-blog/topics.txt';

function fetch_google_suggest(string $q, string $hl = 'ko'): array {
    $q = trim($q);
    if ($q === '') return [];
    $url = 'https://suggestqueries.google.com/complete/search?' . http_build_query([
        'client' => 'firefox', 'hl' => $hl, 'q' => $q,
    ]);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $r = curl_exec($ch);
        curl_close($ch);
    } else {
        $r = @file_get_contents($url);
    }
    if (!$r) return [];
    $j = json_decode($r, true);
    return is_array($j[1] ?? null) ? array_values(array_unique($j[1])) : [];
}

function topics_existing(): array {
    if (!is_file(TOPICS_FILE)) return [];
    return array_map('trim', file(TOPICS_FILE, FILE_IGNORE_NEW_LINES));
}
function topics_append(array $items): int {
    $existing = topics_existing();
    $set = [];
    foreach ($existing as $l) {
        if ($l === '' || str_starts_with($l, '#')) continue;
        $set[mb_strtolower($l)] = true;
    }
    $added = 0;
    foreach ($items as $it) {
        $it = trim($it);
        if ($it === '') continue;
        $k = mb_strtolower($it);
        if (isset($set[$k])) continue;
        $set[$k] = true;
        $existing[] = $it;
        $added++;
    }
    if ($added) file_put_contents(TOPICS_FILE, implode("\n", $existing) . "\n");
    return $added;
}

$results = [];
$query = trim((string)($_GET['q'] ?? ''));
$expanded = (string)($_GET['exp'] ?? '0') === '1';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $picked = $_POST['kw'] ?? [];
    $topics = [];
    foreach ((array)$picked as $kw) {
        $kw = trim((string)$kw);
        if ($kw === '') continue;
        // 키워드를 글 주제 형식으로 변환
        $topics[] = $kw . " 가이드 — 비용·사례·체크리스트";
    }
    $extra = trim((string)($_POST['extra'] ?? ''));
    if ($extra !== '') {
        foreach (preg_split('/\r?\n/', $extra) as $line) {
            $line = trim($line);
            if ($line !== '') $topics[] = $line;
        }
    }
    $added = topics_append($topics);
    $_SESSION['flash']=['type'=>$added>0?'ok':'err','msg'=>$added>0 ? "{$added}개 주제가 토픽 큐에 추가되었습니다. 자동 블로그가 화·금에 자동 발행합니다." : '추가할 주제가 없습니다 (이미 존재 또는 비어있음).'];
    header('Location: keywords.php' . ($query !== '' ? '?q='.urlencode($query).($expanded?'&exp=1':'') : '')); exit;
}

if ($query !== '') {
    $base = fetch_google_suggest($query);
    $results = $base;
    if ($expanded) {
        // 알파벳·자음 확장: 너무 무거우니 한정
        $alphabet = ['ㄱ','ㄴ','ㄷ','ㄹ','ㅁ','ㅂ','ㅅ','ㅇ','ㅈ','ㅊ','ㅋ','ㅌ','ㅍ','ㅎ',
                     '비용','추천','업체','만들기','가격','후기','잘하는','싼','빠른'];
        foreach ($alphabet as $a) {
            $more = fetch_google_suggest("{$query} {$a}");
            $results = array_merge($results, $more);
            if (count($results) > 80) break;
        }
        $results = array_values(array_unique($results));
    }
}

ob_start();
?>
<div class="admin-h">
  <h1>키워드 도구 <span style="color:#5C6577;font-size:14px;font-weight:500">— Google 연관검색어 자동 수집</span></h1>
  <a class="btn-sm" href="settings.php#seo">SEO 설정</a>
</div>

<div class="crd">
  <h3 style="margin:0 0 6px;color:#0B1F3A;font-size:16px">🔎 연관 검색어 수집</h3>
  <p class="muted" style="margin:0 0 14px;font-size:13.5px">
    핵심 키워드 1개 입력 → Google이 자동완성으로 보여주는 실제 사용자 검색어를 가져옵니다.
    원하는 항목을 선택해 자동 블로그 토픽 큐에 추가하면, 다음 화·금 발행 시 해당 주제로 글이 자동 작성됩니다.
  </p>
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="search" name="q" value="<?= h($query) ?>" placeholder='예: "홈페이지 제작", "쇼핑몰 만들기", "병원 홈페이지"' style="flex:1;min-width:280px;border:1px solid #E6E8EE;border-radius:10px;padding:10px 14px;font-size:14px"/>
    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px"><input type="checkbox" name="exp" value="1" <?= $expanded?'checked':'' ?>/> 확장 검색 (느림, ~10초)</label>
    <button class="btn-sm" type="submit">검색</button>
  </form>
</div>

<?php if ($query !== ''): ?>
  <form method="post" class="crd">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
    <h3 style="margin:0 0 10px;color:#0B1F3A;font-size:16px">결과 <span style="color:#5C6577;font-size:13px;font-weight:500">— "<?= h($query) ?>" 관련 <?= count($results) ?>개</span></h3>
    <?php if (empty($results)): ?>
      <p class="muted" style="padding:20px 0;text-align:center">결과가 없습니다. 더 일반적인 단어로 시도해 보세요.</p>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:8px;margin-bottom:14px">
        <?php foreach ($results as $kw): ?>
          <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:#F6F7FB;border:1px solid #E6E8EE;border-radius:8px;cursor:pointer;font-size:14px">
            <input type="checkbox" name="kw[]" value="<?= h($kw) ?>" checked/>
            <span><?= h($kw) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="field">
        <label>직접 입력 (한 줄에 한 주제, 위 결과 외 추가할 내용)</label>
        <textarea name="extra" placeholder="예) 학원 홈페이지 제작 견적 받을 때 확인할 5가지" style="min-height:80px;border:1px solid #E6E8EE;border-radius:10px;padding:10px 13px;width:100%;font:inherit;font-size:14px"></textarea>
      </div>
      <div style="margin-top:12px"><button class="btn btn-primary" type="submit">선택 항목을 토픽 큐에 추가 →</button></div>
    <?php endif; ?>
  </form>
<?php endif; ?>

<div class="crd" style="background:#F6F7FB">
  <h3 style="margin:0 0 8px;color:#0B1F3A;font-size:15px">💡 효과적인 사용 패턴</h3>
  <ol style="margin:0;padding-left:20px;color:#3d465c;font-size:14px;line-height:1.85">
    <li>"홈페이지 제작", "쇼핑몰 만들기", "치과 홈페이지" 같은 <b>일반 카테고리 키워드</b>로 검색 (경쟁사 상표명 X)</li>
    <li>나온 연관어 중 우리가 답할 수 있는 것만 선택 → 토픽 큐에 추가</li>
    <li>주 2회 자동 발행되는 글이 그 키워드를 흡수 → 4–8주 뒤 같은 검색결과에 함께 노출</li>
    <li><b>롱테일</b>(긴 키워드)일수록 경쟁이 약해 1위 잡기 쉬움 — "병원 홈페이지 제작 비용 비교" 같은 형식이 골드</li>
  </ol>
</div>

<div class="crd" style="border-color:#F2D88B;background:#FFF7E5">
  <h3 style="margin:0 0 8px;color:#7a5a14;font-size:15px">⚠ 하지 말아야 할 것 (페널티/법적 리스크)</h3>
  <ul style="margin:0;padding-left:20px;color:#7a5a14;font-size:13.5px;line-height:1.8">
    <li>경쟁사 <b>상표명</b>을 키워드/숨김 텍스트로 넣지 마세요 (상표법 침해 + 구글 cloaking 페널티)</li>
    <li>경쟁사 직접 비방 비교 콘텐츠는 표시광고법 위반 (사실 입증 없으면 처벌)</li>
    <li>구글 광고에 경쟁사 등록상표명 입찰은 분쟁 사례 다수 — 일반 키워드만 사용</li>
  </ul>
</div>
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
