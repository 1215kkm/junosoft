# SEO 자동화 가이드

이 사이트에 들어 있는 SEO/검색 노출 관련 자동화 장치 정리.

## 1. 정적으로 박혀 있는 SEO 기본 (변경 없이 항상 작동)

| 항목 | 어디에 |
|---|---|
| 한국어 `lang="ko"` + 시맨틱 HTML | 모든 페이지 |
| `<title>` / `<meta name="description">` | 페이지별 고유 |
| Open Graph + Twitter Card | 모든 페이지 |
| JSON-LD `Organization` / `WebSite` (SearchAction) | `index.html` |
| JSON-LD `BreadcrumbList` | `portfolio.html` |
| JSON-LD `Service` | 업종 랜딩 5종 |
| JSON-LD `BlogPosting` | 자동 발행 글 each |
| `canonical` | 모든 페이지 |
| `robots.txt` + `sitemap.xml` 참조 | 사이트 루트 |
| `.htaccess` gzip + 캐시 헤더 | 닷홈 환경 |

## 2. 자동으로 갱신되는 것들

### A. sitemap.xml — 자동 빌드
`admin/_seo.php :: seo_sitemap_rebuild()`

- 정적 페이지 11개 + 업종 랜딩 5종 + 블로그 글 전부 포함
- `portfolio.html` 항목 안에 **이미지 sitemap**(`<image:image>`)으로 50건 썸네일 + 제목 + 캡션 포함 → 구글 이미지 검색 노출
- 마지막 수정일(`<lastmod>`) 자동
- **트리거 시점**:
  - 관리자에서 포트폴리오 추가/수정/삭제 시 자동 호출
  - 자동 블로그 발행 시(GitHub Actions) 자동 호출
  - 관리자 설정 페이지 "지금 sitemap·feed 갱신" 버튼

### B. feed.xml — RSS 피드
`admin/_seo.php :: seo_feed_rebuild()`

- 블로그 최근 30개 자동 수집
- `<link rel="alternate" type="application/rss+xml">` 가 메인·포트폴리오·블로그 페이지 head에 박혀 있어 검색엔진/리더가 발견 가능
- 네이버 블로그/티스토리 자동 수집기, Feedly 등 외부 채널이 자동 구독 가능

### C. IndexNow API — 즉시 색인 요청
`admin/_seo.php :: seo_indexnow_ping($urls)`

- **Bing · 네이버 · Yandex · Seznam** 즉시 색인 (구글은 IndexNow 미지원이지만 sitemap으로 발견)
- 첫 호출 시 32자 키 자동 생성 → `_data/settings.json`에 저장 + 사이트 루트에 `{key}.txt` 검증 파일 자동 생성
- **자동 호출 시점**:
  - 포트폴리오 변경 후 → `portfolio.html`, `/` 핑
  - 자동 블로그 글 발행 후 → 새 글 URL + sitemap + feed 핑
  - 설정 페이지 수동 트리거 → 모든 URL 핑

### D. 포트폴리오 ItemList JSON-LD — 동적 주입
`assets/js/portfolio.js :: injectItemListJsonLd()`

- `portfolio.html` 로드 후 portfolio.json 50건을 읽어 `<script type="application/ld+json">` ItemList 주입
- 각 항목은 `CreativeWork` + `Offer`(가격) 포함 → 구글 리치 결과 후보
- 구글봇은 JS 실행하므로 색인됨

## 3. GitHub Actions 자동 발행 + 색인

`.github/workflows/auto-blog.yml`

- 매주 화·금 09:00 KST 자동 실행
- `tools/auto-blog/run.php` 가 Gemini로 새 글 생성 → 사이트맵 갱신 → feed.xml 갱신 → IndexNow 핑 → git commit/push
- 필요한 GitHub Secrets:
  - `GEMINI_API_KEY` (필수): Gemini API 키
  - `INDEXNOW_KEY` (선택, 강력 권장): 관리자 콘솔 설정 페이지에서 자동 생성된 키 복사
  - `FTP_SERVER`, `FTP_USER`, `FTP_PASSWORD` (선택): 닷홈으로 자동 배포

## 4. 사용자가 한 번 해야 하는 일

### 1회만
1. 닷홈에 사이트 업로드
2. `/admin/` 접속 → 비밀번호 설정
3. `/admin/settings.php` → **사이트 URL** 입력 (`https://junosoft.co.kr`) + 저장
4. 같은 페이지 SEO 섹션의 **"지금 sitemap·feed 갱신 + IndexNow 색인 요청"** 버튼 클릭
   - sitemap.xml, feed.xml, `{key}.txt` 검증 파일이 자동 생성됨
   - IndexNow 키가 settings에 저장됨
5. 그 키를 복사해서 GitHub Secrets `INDEXNOW_KEY` 에 등록 (자동 블로그도 핑하게 하려면)

### 검색엔진 등록 (각 30초 ~ 2분)
1. **Google Search Console** → 메타 태그 인증 → sitemap 제출
2. **네이버 서치어드바이저** → 메타 인증 → sitemap 제출
3. **Bing Webmaster Tools** → GSC import (30초)

### 그 다음부터는
- 포트폴리오 추가/수정 → 즉시 sitemap 갱신 + IndexNow 핑 (자동)
- 매주 화·금 자동 블로그 발행 + IndexNow 핑 (자동)
- 사용자는 손 댈 일 없음

## 5. 효과 측정
- Google Search Console → "성능" → 클릭/노출/CTR
- 네이버 서치어드바이저 → "검색 사용자 분석"
- Bing Webmaster → "Search Performance"

신규 사이트 기준 보통 **1~2주차에 색인 시작**, **4~8주차에 롱테일 키워드 노출**, **3~6개월차에 본격 트래픽** 흐름이 일반적입니다.
