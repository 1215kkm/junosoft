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
   - `admin/` 폴더 전체 (관리자 콘솔)
   - `_data/` 폴더 (`.htaccess` 포함, `inquiries/`는 비어 있어도 됨)
   - `assets/` 폴더 전체
   - `images/` 폴더 (이미지 채워 넣은 후)
   - `blog/` 폴더 전체
   - `robots.txt`, `sitemap.xml`, `.htaccess`, `.nojekyll`
5. 업로드 제외:
   - `.git/`, `.github/`, `docs/`, `tools/`, `README.md`
6. 디렉터리 권한 (FileZilla → 우클릭 → 파일 권한)
   - `_data/` 와 하위 → `0750` (서버가 쓰기 가능, 외부 접근은 .htaccess로 차단)
   - `admin/` 자체는 기본값(0755) 그대로

## 3. 관리자 콘솔 첫 설정 (필수)
1. 업로드 후 브라우저에서 `https://본인도메인/admin/` 접속
2. 자동으로 setup 화면이 뜸 → **비밀번호 8자 이상 설정**
3. 대시보드 진입 → 좌측 메뉴 "설정"으로 이동
4. **관리자 수신 메일** / **발신 메일(From)** 입력 후 저장
5. 텔레그램 알림을 쓰려면 같은 페이지에서 **봇 토큰**과 **Chat ID** 입력 후 "테스트 메시지" 버튼으로 검증

> 자세한 텔레그램 봇 만들기 절차: [`docs/admin-guide.md`](admin-guide.md)

## 4. 메일 발송 보충
- 닷홈 무료 호스팅도 PHP `mb_send_mail()` 송신은 가능합니다.
- **한글 깨짐 방지**: `apply.php`는 UTF-8 + Base64 헤더로 처리됩니다.
- 운영 메일 수신이 잘 되지 않으면 발신 주소를 도메인 메일로 설정하세요. 외부 도메인(@gmail 등)은 SPF 미일치로 스팸 분류될 수 있습니다.
- 메일이 실패해도 문의는 `_data/inquiries/*.json` 에 항상 저장되며 관리자 콘솔에서 확인 가능합니다.

## 5. 배포 후 점검 체크리스트
- [ ] `/` 접속 → 메인 정상 노출
- [ ] `/portfolio.html` → 50건 카드 노출 + 카테고리 필터 동작
- [ ] 카드의 "이 스타일로 신청하기" 클릭 → `apply.html?ref=p###` 이동, 상단 배너에 참고 작품 표시
- [ ] `/apply.html` → 폼 제출 → `/thanks.html` 이동, 운영자/신청자 메일 둘 다 수신
- [ ] `/sitemap.xml`, `/robots.txt` 200 응답
- [ ] 모바일에서 햄버거 메뉴, 레이아웃 정상
- [ ] 존재하지 않는 URL → `/404.html` 노출
- [ ] `/admin/` 첫 접속 시 setup → 비밀번호 설정 가능
- [ ] 관리자 로그인 후 대시보드에 테스트 신청이 카운트됨
- [ ] `/_data/` 직접 접속 시 403 (외부 차단 정상 동작)
- [ ] 텔레그램 설정 후 "테스트 메시지" 버튼이 정상 동작

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
