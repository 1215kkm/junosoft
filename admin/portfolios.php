<?php
require __DIR__ . '/_helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $list = portfolio_load();
        $list = array_values(array_filter($list, fn($p) => ($p['id'] ?? '') !== $id));
        portfolio_save($list);
        @seo_refresh_all([seo_url_base().'/portfolio.html', seo_url_base().'/']);
        $_SESSION['flash']=['type'=>'ok','msg'=>'삭제되었습니다. (sitemap·IndexNow 자동 갱신)'];
    }
    header('Location: portfolios.php'); exit;
}

$active = 'pf';
$page_title = '포트폴리오 관리';
$list = portfolio_load();

ob_start();
?>
<div class="admin-h">
  <h1>포트폴리오 관리 <span style="color:#5C6577;font-size:15px;font-weight:500">(<?= count($list) ?>건)</span></h1>
  <a class="btn btn-primary" href="portfolio-edit.php">+ 새 포트폴리오 추가</a>
</div>

<div class="crd">
  <?php if (empty($list)): ?>
    <p class="muted" style="text-align:center;padding:40px 0">등록된 포트폴리오가 없습니다.</p>
  <?php else: ?>
    <table class="t">
      <thead><tr>
        <th style="width:60px">ID</th>
        <th style="width:80px">썸네일</th>
        <th>제목</th>
        <th>업종</th>
        <th>분류</th>
        <th>기간</th>
        <th style="text-align:right">가격</th>
        <th style="width:130px"></th>
      </tr></thead>
      <tbody>
        <?php foreach ($list as $p): ?>
          <tr>
            <td><b><?= h($p['id'] ?? '') ?></b></td>
            <td><img src="../<?= h($p['thumb'] ?? '') ?>" alt="" style="width:60px;height:45px;object-fit:cover;border-radius:6px;background:#E6E8EE" onerror="this.src='../images/placeholder/ph-<?= h($p['category'] ?? '홈페이지') ?>.svg'"/></td>
            <td><b style="color:#0B1F3A"><?= h($p['title'] ?? '') ?></b><br/><span style="color:#5C6577;font-size:12.5px"><?= h(mb_substr($p['summary'] ?? '', 0, 50)) ?></span></td>
            <td><?= h($p['industry'] ?? '') ?></td>
            <td><span class="badge b-read"><?= h($p['category'] ?? '') ?></span></td>
            <td><?= h($p['period'] ?? '') ?></td>
            <td style="text-align:right"><b><?= number_format((int)($p['price'] ?? 0)) ?>원</b></td>
            <td>
              <a class="btn-sm" href="portfolio-edit.php?id=<?= h($p['id'] ?? '') ?>">수정</a>
              <form method="post" style="display:inline" onsubmit="return confirm('정말 삭제할까요?')">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="id" value="<?= h($p['id'] ?? '') ?>"/>
                <button class="btn-sm danger" type="submit">삭제</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="crd" style="background:#F6F7FB">
  <h3 style="margin:0 0 8px;color:#0B1F3A;font-size:15px">💡 이미지 업로드 안내</h3>
  <p style="margin:0;color:#5C6577;font-size:13.5px;line-height:1.7">
    포트폴리오 썸네일은 FTP로 <code>/images/portfolio/p001.jpg</code> 형식으로 업로드 후, 이 페이지의 "썸네일 경로"에 동일한 경로를 입력하시면 됩니다.<br/>
    예) p051을 추가하려면 → FTP로 <code>images/portfolio/p051.jpg</code> 업로드 → 추가 화면에서 ID에 <code>p051</code>, 썸네일 경로에 <code>images/portfolio/p051.jpg</code> 입력.
  </p>
</div>
<?php
$body = ob_get_clean();
include __DIR__ . '/_layout.php';
