<?php
/**
 * SEO 자동화 라이브러리
 * - sitemap.xml 자동 빌드 (페이지 + 포트폴리오 이미지 sitemap + 블로그 글)
 * - feed.xml RSS 자동 빌드 (블로그 글 30건)
 * - IndexNow API 핑 (Bing/Naver/Yandex/Seznam 즉시 색인)
 * - robots.txt 정합성 보장
 */
declare(strict_types=1);

const SITEMAP_FILE = __DIR__ . '/../sitemap.xml';
const FEED_FILE    = __DIR__ . '/../feed.xml';
const ROOT_DIR     = __DIR__ . '/..';
const BLOG_POSTS   = __DIR__ . '/../blog/posts';

function seo_url_base(): string {
    $s = settings_load();
    return rtrim($s['site_url'] ?? 'https://junosoft.co.kr', '/');
}

function seo_indexnow_key(): string {
    $s = settings_load();
    if (empty($s['indexnow_key'])) {
        $s['indexnow_key'] = bin2hex(random_bytes(16));
        settings_save($s);
    }
    $key = $s['indexnow_key'];
    $key_file = ROOT_DIR . "/{$key}.txt";
    if (!is_file($key_file)) @file_put_contents($key_file, $key);
    return $key;
}

function seo_indexnow_ping(array $urls): array {
    $urls = array_values(array_filter(array_unique($urls)));
    if (!$urls) return ['ok'=>false,'reason'=>'no urls'];

    $key  = seo_indexnow_key();
    $base = seo_url_base();
    $host = parse_url($base, PHP_URL_HOST) ?: '';
    if ($host === '' || str_contains($host, 'localhost')) {
        return ['ok'=>false, 'reason'=>'site_url 미설정 또는 localhost'];
    }

    $payload = [
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => "{$base}/{$key}.txt",
        'urlList'     => array_slice($urls, 0, 10000),
    ];
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => $body, CURLOPT_TIMEOUT => 10,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['ok'=>$code>=200 && $code<300, 'status'=>$code, 'count'=>count($urls)];
    }

    $ctx = stream_context_create(['http'=>[
        'method'=>'POST', 'header'=>"Content-Type: application/json\r\n",
        'content'=>$body, 'timeout'=>10, 'ignore_errors'=>true,
    ]]);
    $res = @file_get_contents('https://api.indexnow.org/indexnow', false, $ctx);
    return ['ok'=>$res !== false, 'count'=>count($urls)];
}

function seo_sitemap_rebuild(): array {
    $base = seo_url_base();
    $today = date('Y-m-d');

    $static = [
        ['',                        'weekly',  '1.0'],
        ['portfolio.html',          'weekly',  '0.9'],
        ['apply.html',              'monthly', '0.8'],
        ['industry/hospital.html',  'monthly', '0.7'],
        ['industry/academy.html',   'monthly', '0.7'],
        ['industry/cafe.html',      'monthly', '0.7'],
        ['industry/lawyer.html',    'monthly', '0.7'],
        ['industry/factory.html',   'monthly', '0.7'],
        ['blog/',                   'weekly',  '0.7'],
        ['privacy.html',            'yearly',  '0.3'],
        ['terms.html',              'yearly',  '0.3'],
    ];

    $portfolios = portfolio_load();

    $blog = [];
    if (is_dir(BLOG_POSTS)) {
        foreach (glob(BLOG_POSTS.'/*.html') ?: [] as $f) {
            $bn = basename($f);
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $bn, $m)) {
                $blog[] = ['blog/posts/'.$bn, 'monthly', '0.6', $m[1]];
            }
        }
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemaps-image/1.1">'."\n";

    $all = [];
    foreach ($static as [$path,$cf,$pr]) {
        $loc = "{$base}/{$path}";
        $xml .= "  <url>\n    <loc>".htmlspecialchars($loc, ENT_XML1)."</loc>\n";
        $xml .= "    <lastmod>{$today}</lastmod>\n    <changefreq>{$cf}</changefreq>\n    <priority>{$pr}</priority>\n";
        if ($path === 'portfolio.html' && $portfolios) {
            foreach (array_slice($portfolios, 0, 200) as $p) {
                if (empty($p['thumb'])) continue;
                $img   = "{$base}/" . ltrim((string)$p['thumb'], '/');
                $title = htmlspecialchars((string)($p['title'] ?? ''), ENT_XML1);
                $cap   = htmlspecialchars((string)($p['summary'] ?? ''), ENT_XML1);
                $xml  .= "    <image:image>\n      <image:loc>".htmlspecialchars($img, ENT_XML1)."</image:loc>\n      <image:title>{$title}</image:title>\n      <image:caption>{$cap}</image:caption>\n    </image:image>\n";
            }
        }
        $xml .= "  </url>\n";
        $all[] = $loc;
    }
    foreach ($blog as [$path,$cf,$pr,$dt]) {
        $loc = "{$base}/{$path}";
        $xml .= "  <url>\n    <loc>".htmlspecialchars($loc, ENT_XML1)."</loc>\n";
        $xml .= "    <lastmod>{$dt}</lastmod>\n    <changefreq>{$cf}</changefreq>\n    <priority>{$pr}</priority>\n  </url>\n";
        $all[] = $loc;
    }
    $xml .= '</urlset>'."\n";

    file_put_contents(SITEMAP_FILE, $xml);
    return $all;
}

function seo_feed_rebuild(): int {
    $base = seo_url_base();
    if (!is_dir(BLOG_POSTS)) {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>'."\n<rss version=\"2.0\"><channel><title>주노소프트 블로그</title><link>{$base}/blog/</link><description>웹 에이전시 인사이트</description><language>ko</language></channel></rss>\n";
        file_put_contents(FEED_FILE, $rss);
        return 0;
    }
    $files = glob(BLOG_POSTS.'/*.html') ?: [];
    rsort($files);
    $items = '';
    $count = 0;
    foreach (array_slice($files, 0, 30) as $f) {
        $h = (string)file_get_contents($f);
        $bn = basename($f);
        preg_match('/<title>(.*?)\s\|/u', $h, $t);
        preg_match('/<meta name="description" content="(.*?)"/u', $h, $d);
        preg_match('/^(\d{4}-\d{2}-\d{2})/', $bn, $dm);
        $title = htmlspecialchars($t[1] ?? $bn, ENT_XML1);
        $desc  = htmlspecialchars($d[1] ?? '', ENT_XML1);
        $date  = ($dm[1] ?? date('Y-m-d')) . ' 09:00:00';
        $link  = "{$base}/blog/posts/{$bn}";
        $items .= "    <item>\n      <title>{$title}</title>\n      <link>{$link}</link>\n      <description>{$desc}</description>\n      <pubDate>".date(DATE_RSS, strtotime($date))."</pubDate>\n      <guid isPermaLink=\"true\">{$link}</guid>\n    </item>\n";
        $count++;
    }
    $rss  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n  <channel>\n";
    $rss .= "    <title>주노소프트 블로그</title>\n    <link>{$base}/blog/</link>\n    <atom:link href=\"{$base}/feed.xml\" rel=\"self\" type=\"application/rss+xml\" />\n";
    $rss .= "    <description>홈페이지·쇼핑몰·앱 제작 실전 가이드. 매주 화/금 자동 업데이트.</description>\n    <language>ko</language>\n";
    $rss .= "    <lastBuildDate>".date(DATE_RSS)."</lastBuildDate>\n";
    $rss .= $items;
    $rss .= "  </channel>\n</rss>\n";
    file_put_contents(FEED_FILE, $rss);
    return $count;
}

function seo_refresh_all(array $extra_urls = []): array {
    $urls = seo_sitemap_rebuild();
    $feed_count = seo_feed_rebuild();
    $ping_urls = array_unique(array_merge($urls, $extra_urls));
    $ping = seo_indexnow_ping($ping_urls);
    return ['urls'=>count($urls), 'feed'=>$feed_count, 'ping'=>$ping];
}

function seo_status(): array {
    $s = settings_load();
    return [
        'sitemap_exists' => is_file(SITEMAP_FILE),
        'sitemap_mtime'  => is_file(SITEMAP_FILE) ? filemtime(SITEMAP_FILE) : 0,
        'feed_exists'    => is_file(FEED_FILE),
        'feed_mtime'     => is_file(FEED_FILE) ? filemtime(FEED_FILE) : 0,
        'indexnow_key'   => $s['indexnow_key'] ?? '',
        'site_url'       => $s['site_url'] ?? '',
    ];
}
