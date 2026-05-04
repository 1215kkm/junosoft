<?php
/**
 * 자동 블로그 발행 스크립트
 *
 * 동작:
 *   1. tools/auto-blog/topics.txt 의 첫 미사용 주제를 가져온다
 *   2. Gemini API 로 글(JSON) 생성 — 제목/슬러그/요약/본문/메타
 *   3. blog/posts/YYYY-MM-DD-slug.html 파일을 생성
 *   4. blog/index.html 갱신
 *   5. sitemap.xml 갱신
 *   6. topics.txt 의 사용된 주제 앞에 '# DONE ' 표시
 *
 * 환경변수:
 *   GEMINI_API_KEY  Gemini API 키 (필수)
 *   GEMINI_MODEL    기본 'gemini-2.5-flash'
 *   SITE_BASE_URL   기본 'https://junosoft.co.kr'
 *
 * 실행:
 *   php tools/auto-blog/run.php
 */

declare(strict_types=1);

$ROOT      = dirname(__DIR__, 2);
$BLOG_DIR  = $ROOT . '/blog';
$POSTS_DIR = $BLOG_DIR . '/posts';
$TOPICS    = __DIR__ . '/topics.txt';

$BASE = getenv('SITE_BASE_URL') ?: 'https://junosoft.co.kr';
$KEY  = getenv('GEMINI_API_KEY');
$MODEL= getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash';

if (!$KEY) { fwrite(STDERR, "ERROR: GEMINI_API_KEY 미설정\n"); exit(1); }
if (!is_dir($POSTS_DIR)) { mkdir($POSTS_DIR, 0755, true); }

// 1) 주제 선택
$lines = file($TOPICS, FILE_IGNORE_NEW_LINES);
$idx = -1;
foreach ($lines as $i => $l) {
    $t = trim($l);
    if ($t === '' || str_starts_with($t, '#')) continue;
    $idx = $i; break;
}
if ($idx < 0) { fwrite(STDERR, "남은 주제 없음\n"); exit(0); }
$topic = trim($lines[$idx]);
echo "선택 주제: {$topic}\n";

// 2) Gemini 호출
$prompt = <<<EOT
너는 한국어 웹 에이전시(주노소프트, junosoft.co.kr)의 블로그 작가다.
주제: "{$topic}"
요건:
- 1,000~1,400자 한국어 본문
- HTML 본문(<p>, <h2>, <h3>, <ul>, <li>, <strong> 만 사용. <html>/<body> 금지)
- SEO 친화: 자연스러운 키워드 반복, 첫 문단에 핵심 결론
- 마지막에 "주노소프트(junosoft.co.kr)에 무료 견적 신청" CTA 한 줄
- 응답은 반드시 다음 JSON만 출력 (코드블록 금지):
{
 "title": "30~55자 한국어 제목",
 "slug": "url-safe-slug-english-or-romanized-50chars",
 "description": "120~155자 한국어 메타 디스크립션",
 "content_html": "본문 HTML"
}
EOT;

$payload = [
    'contents' => [['role'=>'user','parts'=>[['text'=>$prompt]]]],
    'generationConfig' => [
        'temperature' => 0.7,
        'responseMimeType' => 'application/json'
    ]
];
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key={$KEY}";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 90,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);
if ($code !== 200 || !$resp) { fwrite(STDERR, "Gemini HTTP {$code}: {$resp}\n"); exit(2); }

$json = json_decode($resp, true);
$text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
if ($text === '') { fwrite(STDERR, "응답 비어있음\n"); exit(3); }
$post = json_decode($text, true);
if (!$post || empty($post['title']) || empty($post['content_html'])) {
    fwrite(STDERR, "응답 파싱 실패: {$text}\n"); exit(4);
}

// 3) 파일 작성
$date = date('Y-m-d');
$slug = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($post['slug'] ?? ''));
$slug = trim($slug, '-') ?: substr(md5($post['title']), 0, 12);
$filename = "{$date}-{$slug}.html";
$filepath = "{$POSTS_DIR}/{$filename}";
$desc = htmlspecialchars($post['description'] ?? '', ENT_QUOTES, 'UTF-8');
$titleEsc = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');
$canonical = "{$BASE}/blog/posts/{$filename}";

$html = <<<HTML
<!doctype html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>{$titleEsc} | 주노소프트 블로그</title>
<meta name="description" content="{$desc}" />
<link rel="canonical" href="{$canonical}" />
<meta property="og:title" content="{$titleEsc}" />
<meta property="og:description" content="{$desc}" />
<meta property="og:url" content="{$canonical}" />
<meta property="og:image" content="{$BASE}/images/brand/og-default.jpg" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
<link rel="stylesheet" href="/assets/css/style.css" />
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BlogPosting","headline":"{$titleEsc}","datePublished":"{$date}","author":{"@type":"Organization","name":"주노소프트"},"mainEntityOfPage":"{$canonical}"}
</script>
</head>
<body>
<header class="site-header"><div class="container nav"><a class="brand" href="/"><span class="dot"></span><span>주노소프트</span></a><nav class="menu"><a href="/portfolio.html">포트폴리오</a><a href="/blog/">블로그</a><a class="cta" href="/apply.html">무료 견적</a></nav></div></header>
<article class="section"><div class="container" style="max-width:760px">
  <p class="muted" style="font-size:13px">발행 {$date} · 카테고리: 웹 에이전시 인사이트</p>
  <h1 style="color:var(--navy);font-size:34px;line-height:1.25;letter-spacing:-.02em">{$titleEsc}</h1>
  <div style="margin-top:20px;color:#1f2740;font-size:16.5px;line-height:1.85">
{$post['content_html']}
  </div>
  <div class="cta-banner" style="margin-top:36px"><div><h3>지금 무료 견적 신청하시면 1영업일 내 회신!</h3><p>홈페이지·쇼핑몰·앱 — 정찰제 견적서 즉시 발송</p></div><a class="btn btn-gold" href="/apply.html">무료 견적 신청 →</a></div>
</div></article>
<footer class="footer"><div class="container foot-bottom" style="border:0"><div>© 2026 주노소프트</div><div><a href="/privacy.html">개인정보처리방침</a> · <a href="/terms.html">이용약관</a></div></div></footer>
</body>
</html>
HTML;

file_put_contents($filepath, $html);
echo "작성 완료: {$filepath}\n";

// 4) blog/index.html 갱신
$posts = glob("{$POSTS_DIR}/*.html");
rsort($posts);
$items = '';
foreach (array_slice($posts, 0, 30) as $p) {
    $bn = basename($p);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)\.html$/', $bn, $m)) continue;
    $d = $m[1];
    $h = file_get_contents($p);
    preg_match('/<title>(.*?)\s\|/u', $h, $t);
    preg_match('/<meta name="description" content="(.*?)"/u', $h, $dd);
    $tt = $t[1] ?? $bn;
    $de = $dd[1] ?? '';
    $items .= "<a class=\"card reveal\" style=\"display:block\" href=\"/blog/posts/{$bn}\"><div class=\"muted\" style=\"font-size:12.5px\">{$d}</div><h3>{$tt}</h3><p>{$de}</p></a>\n";
}

$blogIndex = <<<HTML
<!doctype html>
<html lang="ko">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>웹 에이전시 인사이트 블로그 | 주노소프트</title>
<meta name="description" content="홈페이지·쇼핑몰·앱 제작에 대한 실전 가이드와 사례를 매주 업데이트합니다." />
<link rel="canonical" href="{$BASE}/blog/" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
<link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>
<header class="site-header"><div class="container nav"><a class="brand" href="/"><span class="dot"></span><span>주노소프트</span></a><nav class="menu"><a href="/portfolio.html">포트폴리오</a><a href="/blog/">블로그</a><a class="cta" href="/apply.html">무료 견적</a></nav></div></header>
<section class="section"><div class="container">
  <div class="sec-head"><span class="eyebrow">BLOG</span><h2>웹 에이전시 인사이트</h2><p>매주 화/금 업데이트</p></div>
  <div class="cards">{$items}</div>
</div></section>
<footer class="footer"><div class="container foot-bottom" style="border:0"><div>© 2026 주노소프트</div><div><a href="/privacy.html">개인정보처리방침</a></div></div></footer>
<script src="/assets/js/main.js"></script>
</body>
</html>
HTML;
file_put_contents($BLOG_DIR . '/index.html', $blogIndex);
echo "blog/index.html 갱신\n";

// 5) sitemap.xml 갱신
$sitemap = $ROOT . '/sitemap.xml';
$entries = '';
foreach (array_slice($posts, 0, 200) as $p) {
    $bn = basename($p);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $bn, $m)) continue;
    $entries .= "  <url><loc>{$BASE}/blog/posts/{$bn}</loc><lastmod>{$m[1]}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
}
$xml = file_get_contents($sitemap);
// 기존 blog 항목 제거 후 추가
$xml = preg_replace('#\s*<url>\s*<loc>[^<]+/blog/[^<]*</loc>.*?</url>#s', '', $xml);
$xml = str_replace('</urlset>', "  <url><loc>{$BASE}/blog/</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>\n{$entries}</urlset>", $xml);
file_put_contents($sitemap, $xml);
echo "sitemap.xml 갱신\n";

// 6) feed.xml 갱신 (RSS)
$feed = $ROOT . '/feed.xml';
$rssItems = '';
foreach (array_slice($posts, 0, 30) as $p) {
    $bn = basename($p);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $bn, $dm)) continue;
    $h2 = (string)file_get_contents($p);
    preg_match('/<title>(.*?)\s\|/u', $h2, $tt2);
    preg_match('/<meta name="description" content="(.*?)"/u', $h2, $dd2);
    $title2 = htmlspecialchars($tt2[1] ?? $bn, ENT_XML1);
    $desc2  = htmlspecialchars($dd2[1] ?? '', ENT_XML1);
    $link2  = "{$BASE}/blog/posts/{$bn}";
    $pub    = date(DATE_RSS, strtotime($dm[1] . ' 09:00:00'));
    $rssItems .= "    <item>\n      <title>{$title2}</title>\n      <link>{$link2}</link>\n      <description>{$desc2}</description>\n      <pubDate>{$pub}</pubDate>\n      <guid isPermaLink=\"true\">{$link2}</guid>\n    </item>\n";
}
$rss  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n  <channel>\n";
$rss .= "    <title>주노소프트 블로그</title>\n    <link>{$BASE}/blog/</link>\n    <atom:link href=\"{$BASE}/feed.xml\" rel=\"self\" type=\"application/rss+xml\" />\n";
$rss .= "    <description>홈페이지·쇼핑몰·앱 제작 실전 가이드. 매주 화/금 자동 업데이트.</description>\n    <language>ko</language>\n    <lastBuildDate>".date(DATE_RSS)."</lastBuildDate>\n";
$rss .= $rssItems;
$rss .= "  </channel>\n</rss>\n";
file_put_contents($feed, $rss);
echo "feed.xml 갱신\n";

// 7) IndexNow 즉시 색인 요청 (Bing/Naver/Yandex/Seznam)
$INDEXNOW_KEY = getenv('INDEXNOW_KEY') ?: '';
if ($INDEXNOW_KEY !== '') {
    $host = parse_url($BASE, PHP_URL_HOST) ?: '';
    if ($host && !str_contains($host, 'localhost')) {
        // 키 검증 파일 보장
        $keyFile = $ROOT . "/{$INDEXNOW_KEY}.txt";
        if (!is_file($keyFile)) @file_put_contents($keyFile, $INDEXNOW_KEY);
        $payload = json_encode([
            'host'        => $host,
            'key'         => $INDEXNOW_KEY,
            'keyLocation' => "{$BASE}/{$INDEXNOW_KEY}.txt",
            'urlList'     => [
                "{$BASE}/blog/posts/{$filename}",
                "{$BASE}/blog/",
                "{$BASE}/sitemap.xml",
                "{$BASE}/feed.xml",
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ch2 = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch2, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch2);
        $idxStatus = (int)curl_getinfo($ch2, CURLINFO_RESPONSE_CODE);
        curl_close($ch2);
        echo "IndexNow ping → HTTP {$idxStatus}\n";
    }
} else {
    echo "INDEXNOW_KEY 미설정 — IndexNow 핑 스킵 (선택사항)\n";
}

// 8) topics.txt 마킹
$lines[$idx] = "# DONE {$date} :: " . $topic;
file_put_contents($TOPICS, implode("\n", $lines));
echo "topic 마킹 완료\n";
echo "DONE\n";
