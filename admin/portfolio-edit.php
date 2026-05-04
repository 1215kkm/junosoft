<?php
require __DIR__ . '/_helpers.php';
require_login();

$active = 'pf';
$list = portfolio_load();
$id = (string)($_GET['id'] ?? '');
$is_new = $id === '';

$item = ['id'=>'', 'title'=>'', 'industry'=>'', 'category'=>'홈페이지', 'thumb'=>'', 'period'=>'', 'price'=>0, 'summary'=>'', 'tags'=>[]];
if (!$is_new) {
    foreach ($list as $p) if (($p['id'] ?? '') === $id) { $item = $p + $item; break; }
    if (($item['id'] ?? '') === '') { $_SESSION['flash']=['type'=>'err','msg'=>'포트폴리오를 찾을 수 없습니다.']; header('Location: portfolios.php'); exit; }
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $newId  = preg_replace('/[^0-9A-Za-z_\-]/', '', (string)($_POST['id'] ?? ''));
    $title  = trim((string)($_POST['title'] ?? ''));
    $cat    = (string)($_POST['category'] ?? '');
    $allowed_cats = ['홈페이지','쇼핑몰','랜딩페이지','관리자','모바일앱','리뉴얼'];

    if ($newId === '' || $title === '') {
        $err = 'ID와 제목은 필수입니다.';
    } elseif (!in_array($cat, $allowed_cats, true)) {
        $err = '분류 값이 올바르지 않습니다.';
    } else {
        $rec = [
            'id'       => $newId,
            'title'    => $title,
            'industry' => trim((string)($_POST['industry'] ?? '')),
            'category' => $cat,
            'thumb'    => trim((string)($_POST['thumb'] ?? '')) ?: "images/portfolio/{$newId}.jpg",
            'period'   => trim((string)($_POST['period'] ?? '')),
            'price'    => (int)preg_replace('/[^0-9]/', '', (string)($_POST['price'] ?? '0')),
            'summary'  => trim((string)($_POST['summary'] ?? '')),
            'tags'     => array_values(array_filter(array_map('trim', explode(',', (string)($_POST['tags'] ?? ''))))),
        ];

        if ($is_new) {
            foreach ($list as $p) if (($p['id'] ?? '') === $newId) { $err = '같은 ID의 포트폴리오가 이미 있습니다.'; break; }
            if (!$err) { array_unshift($list, $rec); }
        } else {
            $found = false;
            foreach ($list as &$p) if (($p['id'] ?? '') === $id) { $p = $rec; $found = true; break; }
            unset($p);
            if (!$found) $err = '원본을 찾을 수 없습니다.';
        }

        if (!$err) {
            portfolio_save($list);
            @seo_refresh_all([seo_url_base().'/portfolio.html', seo_url_base().'/']);
            $_SESSION['flash']=['type'=>'ok','msg'=>($is_new?'추가':'수정').'되었습니다. (sitemap·IndexNow 자동 갱신)'];
            header('Location: portfolios.php'); exit;
        }
        $item = $rec; // 재표시
    }
}

$page_title = $is_new ? '포트폴리오 추가' : '포트폴리오 수정';

ob_start();
?>
<div class="admin-h">
  <h1><?= h($page_title) ?></h1>
  <a class="btn-sm" href="portfolios.php">← 목록</a>
</div>

<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>

<form method="post" class="crd">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
  <div class="field-row">
    <div class="field">
      <label>ID <span style="color:#B0322B">*</span> <small style="color:#5C6577;font-weight:500">(예: p051, 영문/숫자/-/_)</small></label>
      <input type="text" name="id" value="<?= h($item['id']) ?>" required pattern="[0-9A-Za-z_\-]+" maxlength="20" <?= $is_new?'':'readonly' ?>/>
    </div>
    <div class="field">
      <label>분류 <span style="color:#B0322B">*</span></label>
      <select name="category" required>
        <?php foreach (['홈페이지','쇼핑몰','랜딩페이지','관리자','모바일앱','리뉴얼'] as $c): ?>
          <option <?= $item['category']===$c?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full">
      <label>제목 <span style="color:#B0322B">*</span></label>
      <input type="text" name="title" value="<?= h($item['title']) ?>" required maxlength="100"/>
    </div>
    <div class="field">
      <label>업종 <small style="color:#5C6577;font-weight:500">(예: 치과/의료)</small></label>
      <input type="text" name="industry" value="<?= h($item['industry']) ?>" maxlength="40"/>
    </div>
    <div class="field">
      <label>제작 기간 <small style="color:#5C6577;font-weight:500">(예: 4주)</small></label>
      <input type="text" name="period" value="<?= h($item['period']) ?>" maxlength="20"/>
    </div>
    <div class="field">
      <label>가격(원) <small style="color:#5C6577;font-weight:500">(숫자만, 예: 1890000)</small></label>
      <input type="number" name="price" value="<?= (int)($item['price'] ?? 0) ?>" min="0" step="10000"/>
    </div>
    <div class="field">
      <label>썸네일 경로 <small style="color:#5C6577;font-weight:500">(비워두면 자동: images/portfolio/{ID}.jpg)</small></label>
      <input type="text" name="thumb" value="<?= h($item['thumb']) ?>" maxlength="200" placeholder="images/portfolio/p051.jpg"/>
    </div>
    <div class="field full">
      <label>한줄 설명</label>
      <textarea name="summary" maxlength="200" style="min-height:80px"><?= h($item['summary']) ?></textarea>
    </div>
    <div class="field full">
      <label>태그 <small style="color:#5C6577;font-weight:500">(쉼표로 구분, 예: 반응형, 결제, SEO)</small></label>
      <input type="text" name="tags" value="<?= h(implode(', ', (array)($item['tags'] ?? []))) ?>" maxlength="200"/>
    </div>
  </div>
  <div style="display:flex;gap:10px;margin-top:10px">
    <button class="btn btn-primary" type="submit"><?= $is_new?'추가':'수정 저장' ?></button>
    <a class="btn-sm" href="portfolios.php">취소</a>
  </div>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
