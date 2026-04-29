# 사용자가 채울 이미지 목록

이미지가 비어 있어도 사이트는 자동 폴백 SVG로 정상 동작합니다.
아래 경로/파일명대로 채워 넣으면 즉시 실 이미지로 교체됩니다.

## 1) 브랜드/공통 (5장)
| 경로 | 권장 사이즈 | 비고 |
|---|---|---|
| `/images/brand/logo.svg` (또는 .png) | 가로 200px+ | 헤더 로고 |
| `/images/brand/logo-white.svg` | 가로 200px+ | 어두운 배경용 (옵션) |
| `/images/brand/favicon.png` | 512x512 | 브라우저 탭 아이콘 |
| `/images/brand/og-default.jpg` | 1200x630 | SNS 공유 미리보기 |
| `/images/brand/apple-touch-icon.png` | 180x180 | iOS 홈 추가 |

## 2) 메인 히어로/섹션 (8장)
```
/images/main/hero-1.jpg              (1920x1080)
/images/main/hero-2.jpg              (1920x1080)
/images/main/hero-3.jpg              (1920x1080)
/images/main/about-office.jpg        (1280x800)
/images/main/process-1.jpg           (800x600)
/images/main/process-2.jpg           (800x600)
/images/main/cta-bg.jpg              (1920x600)
/images/main/award-strip.jpg         (1200x200)
```

## 3) 고객사 로고 띠 (12장)
모노톤 PNG/SVG 권장, 각 240x80
```
/images/clients/client-01.png
/images/clients/client-02.png
/images/clients/client-03.png
/images/clients/client-04.png
/images/clients/client-05.png
/images/clients/client-06.png
/images/clients/client-07.png
/images/clients/client-08.png
/images/clients/client-09.png
/images/clients/client-10.png
/images/clients/client-11.png
/images/clients/client-12.png
```

## 4) 포트폴리오 썸네일 (50장)
권장 사이즈 1200x900 (4:3), JPG 또는 WEBP. 파일명은 `portfolio.json` 의 `id` 와 1:1 매칭.
```
/images/portfolio/p001.jpg
/images/portfolio/p002.jpg
/images/portfolio/p003.jpg
/images/portfolio/p004.jpg
/images/portfolio/p005.jpg
/images/portfolio/p006.jpg
/images/portfolio/p007.jpg
/images/portfolio/p008.jpg
/images/portfolio/p009.jpg
/images/portfolio/p010.jpg
/images/portfolio/p011.jpg
/images/portfolio/p012.jpg
/images/portfolio/p013.jpg
/images/portfolio/p014.jpg
/images/portfolio/p015.jpg
/images/portfolio/p016.jpg
/images/portfolio/p017.jpg
/images/portfolio/p018.jpg
/images/portfolio/p019.jpg
/images/portfolio/p020.jpg
/images/portfolio/p021.jpg
/images/portfolio/p022.jpg
/images/portfolio/p023.jpg
/images/portfolio/p024.jpg
/images/portfolio/p025.jpg
/images/portfolio/p026.jpg
/images/portfolio/p027.jpg
/images/portfolio/p028.jpg
/images/portfolio/p029.jpg
/images/portfolio/p030.jpg
/images/portfolio/p031.jpg
/images/portfolio/p032.jpg
/images/portfolio/p033.jpg
/images/portfolio/p034.jpg
/images/portfolio/p035.jpg
/images/portfolio/p036.jpg
/images/portfolio/p037.jpg
/images/portfolio/p038.jpg
/images/portfolio/p039.jpg
/images/portfolio/p040.jpg
/images/portfolio/p041.jpg
/images/portfolio/p042.jpg
/images/portfolio/p043.jpg
/images/portfolio/p044.jpg
/images/portfolio/p045.jpg
/images/portfolio/p046.jpg
/images/portfolio/p047.jpg
/images/portfolio/p048.jpg
/images/portfolio/p049.jpg
/images/portfolio/p050.jpg
```

## 5) (선택) 포트폴리오 상세 보조 이미지
필요한 작품에만 채워 넣으면 됨. `portfolio.json` 항목에 `gallery` 배열을 추가하면 자동 노출됨.
```
/images/portfolio/details/p001-1.jpg
/images/portfolio/details/p001-2.jpg
/images/portfolio/details/p012-1.jpg
...
```

## 폴백 동작
이미지 파일이 없을 경우 카드 카테고리에 따라 다음 SVG가 자동 표시됩니다.
- `/images/placeholder/ph-홈페이지.svg`
- `/images/placeholder/ph-쇼핑몰.svg`
- `/images/placeholder/ph-랜딩페이지.svg`
- `/images/placeholder/ph-관리자.svg`
- `/images/placeholder/ph-모바일앱.svg`
- `/images/placeholder/ph-리뉴얼.svg`

각 SVG는 저장소에 함께 커밋되어 있어 처음 업로드 시점부터 깨지지 않습니다.
