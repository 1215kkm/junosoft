# 닷홈 호스팅 배포 가이드

## 1. 준비물
- 닷홈(dothome.co.kr) 호스팅 계정 (PHP+MySQL 또는 PHP만)
- FTP/SFTP 클라이언트 (FileZilla 권장)
- 도메인 (닷홈 무료 서브도메인 또는 본인 도메인 연결)

## 2. 첫 업로드 (FileZilla 기준)
1. FileZilla → 사이트 관리자 → 새 사이트
   - 호스트: `ftp.dothome.co.kr`
   - 프로토콜: FTP, 암호화: 가능하면 명시적 FTPS
   - 사용자: 닷홈 아이디 / 비밀번호: 닷홈 비밀번호
2. 원격: `/html` 디렉터리로 이동
3. 로컬: 이 저장소 루트
4. 다음 항목 **만** 업로드 (개발용 폴더는 제외)
   - `index.html`, `portfolio.html`, `apply.html`, `apply.php`, `thanks.html`
   - `privacy.html`, `terms.html`, `404.html`
   - `industry/` 폴더 전체
   - `assets/` 폴더 전체
   - `images/` 폴더 (이미지 채워 넣은 후)
   - `blog/` 폴더 전체
   - `robots.txt`, `sitemap.xml`, `.htaccess`
5. 업로드 제외:
   - `.git/`, `.github/`, `docs/`, `tools/`, `README.md`, `_inbox/` (자동 생성됨)

## 3. PHP 메일 설정 확인
`apply.php` 안에서 다음 두 곳을 실 운영 주소로 변경하세요.
```php
$adminTo  = 'contact@junosoft.co.kr';   // 신청 받을 운영자 메일
$fromAddr = 'noreply@junosoft.co.kr';   // 발신용 (도메인 메일 또는 닷홈 발급 메일)
```
- 닷홈 무료 호스팅도 PHP `mail()` / `mb_send_mail()` 송신은 가능합니다.
- **한글 깨짐 방지**: `apply.php`는 이미 UTF-8 + Base64 헤더로 처리됩니다.
- 운영 메일 받기가 잘 되지 않으면 "보낸 사람"을 도메인 메일로 두세요. 외부 도메인(@gmail 등)은 SPF 미일치로 스팸 처리될 수 있습니다.

## 4. 배포 후 점검 체크리스트
- [ ] `/` 접속 → 메인 정상 노출
- [ ] `/portfolio.html` → 50건 카드 노출 + 카테고리 필터 동작
- [ ] 카드의 "이 스타일로 신청하기" 클릭 → `apply.html?ref=p###` 이동, 상단 배너에 참고 작품 표시
- [ ] `/apply.html` → 폼 제출 → `/thanks.html` 이동, 운영자/신청자 메일 둘 다 수신
- [ ] `/sitemap.xml`, `/robots.txt` 200 응답
- [ ] 모바일에서 햄버거 메뉴, 레이아웃 정상
- [ ] 존재하지 않는 URL → `/404.html` 노출

## 5. 검색엔진 등록
1. **Google Search Console** (https://search.google.com/search-console/about)
   - 속성 추가 → 메타 태그 인증
   - `index.html`의 `<!-- google-site-verification: __PLACEHOLDER__ -->` 줄을 GSC에서 받은 메타로 교체
   - sitemap 제출: `https://junosoft.co.kr/sitemap.xml`
2. **네이버 서치어드바이저** (https://searchadvisor.naver.com)
   - 사이트 등록 → 메타 인증 → sitemap 제출 → robots.txt 확인
3. **Bing Webmaster Tools** — GSC import 옵션으로 30초 내 가져오기

## 6. 자주 발생하는 문제
- **메일이 안 오는 경우**: `_inbox/` 폴더에 백업 파일이 쌓이는지 먼저 확인. 쌓인다면 메일만 안 가는 상황 → 발신 주소를 도메인 메일로 변경, 닷홈 고객센터에 SPF 문의.
- **한글 메일 제목 깨짐**: `apply.php`는 `=?UTF-8?B?` 인코딩을 사용하므로 일반적으로 안전. 그래도 깨지는 메일 클라이언트가 있다면 본문은 UTF-8 plain으로 강제됨.
- **이미지 깨짐**: 카드 자동 폴백으로 `/images/placeholder/ph-{카테고리}.svg`가 노출되므로 사이트가 망가지지 않음. 이후 `/images/portfolio/p001.jpg ~ p050.jpg` 채워 넣기.
- **HTTPS 리다이렉트**: 닷홈 SSL 적용 후 `.htaccess`의 RewriteRule 주석을 해제.
