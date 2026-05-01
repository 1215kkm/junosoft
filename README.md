# 주노소프트 (junosoft.co.kr) — 웹 에이전시 사이트

웹사이트·앱 제작 전문 에이전시 "주노소프트"의 공식 사이트.
정적 HTML/CSS/JS + 가벼운 PHP(견적 폼 처리)로 구성되어 있어 **닷홈(dothome.co.kr) 호스팅에 그대로 업로드**하면 동작합니다.

## 페이지 구성
| URL | 설명 |
|---|---|
| `/` | 메인. 히어로·서비스·작업절차·대표 작품 8건·후기·FAQ·CTA |
| `/portfolio.html` | 포트폴리오 50건. 카테고리/검색/정렬 + 정찰제 가격표 |
| `/apply.html` | 무료 견적 신청 폼 (참고 작품/패키지 자동 첨부) |
| `/apply.php` | 신청 폼 수신 → 메일 발송 → `/thanks.html` |
| `/thanks.html` | 신청 완료 안내 |
| `/industry/{hospital,academy,cafe,lawyer,factory}.html` | 업종별 랜딩 5종 |
| `/blog/` | 자동 발행 블로그 (Gemini API) |
| `/privacy.html`, `/terms.html`, `/404.html` | 정책/약관/에러 |
| `/admin/` | 관리자 콘솔 (문의 관리·포트폴리오 CRUD·텔레그램 알림 설정) |

## 빠른 시작 (로컬 미리보기)
```bash
python3 -m http.server 8000
# http://localhost:8000 접속
```
> `apply.php` 의 메일 송신은 PHP가 있는 환경(닷홈 등)에서만 동작합니다. 로컬 미리보기에선 폼 UI만 확인 가능합니다.

## 배포
- 자세한 절차: [`docs/dothome-deploy.md`](docs/dothome-deploy.md)
- 자동 배포(선택): GitHub Actions 워크플로우에 `FTP_SERVER`, `FTP_USER`, `FTP_PASSWORD` 시크릿 등록 시 자동 글 발행과 동시에 닷홈으로 푸시됩니다.

## 이미지 채우기
사이트는 SVG 폴백으로 처음부터 깨지지 않게 동작합니다. 실 이미지를 채워 넣을 위치는 [`docs/image-list.md`](docs/image-list.md) 참고.

## 자동 홍보
- Gemini AI로 매주 화/금 09:00 KST 자동 블로그 발행 — [`tools/auto-blog/run.php`](tools/auto-blog/run.php)
- 외부 등록·검색엔진 등록·광고 채널 체크리스트 — [`docs/promotion-checklist.md`](docs/promotion-checklist.md)
- 토픽 큐 — [`tools/auto-blog/topics.txt`](tools/auto-blog/topics.txt) (40개 주제 시드)

## 관리자 콘솔 + 텔레그램 알림
- 사용 가이드 — [`docs/admin-guide.md`](docs/admin-guide.md)
- 첫 접속 시 `/admin/` → 비밀번호 설정 1회 → 이후 로그인
- 기능: 문의 목록/상세/메모/상태/삭제, 포트폴리오 추가·수정·삭제, 텔레그램 봇 설정 + 테스트 발송
- 문의는 `_data/inquiries/*.json` 으로 보존 (메일+텔레그램 동시 발송과 별개로 항상 저장)

## 운영자 교체 포인트
- `/admin/settings.php` 에서: 관리자 수신 메일·발신 메일·사이트 URL·텔레그램 토큰/Chat ID
- `index.html` / 각 페이지의 `google-site-verification`, `naver-site-verification` 메타
- (선택) `index.html` 의 GA4 / 메타 픽셀 스크립트 주석 해제 후 측정 ID 입력
- 푸터의 사업자등록번호/통신판매업/주소/대표자/연락처

## 디렉터리
```
.
├── index.html / portfolio.html / apply.html / thanks.html
├── apply.php / privacy.html / terms.html / 404.html
├── industry/ (5)
├── admin/ (관리자 콘솔: 로그인·문의·포트폴리오·설정)
├── _data/ (런타임: 문의 JSON·설정·접근 차단)
├── assets/css/ assets/js/ assets/data/portfolio.json
├── images/ (사용자가 채움 + placeholder/)
├── blog/ (자동 생성)
├── tools/auto-blog/ (Gemini 발행 스크립트)
├── .github/workflows/auto-blog.yml
├── docs/ (배포·홍보·관리자·이미지 가이드)
├── robots.txt / sitemap.xml / .htaccess
```

## 라이선스
사내 운영용 사이트. 외부 공개 라이선스 없음.
