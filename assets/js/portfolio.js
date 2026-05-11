(function () {
  const grid = document.getElementById('pfGrid');
  const tabs = document.getElementById('pfTabs');
  const search = document.getElementById('pfSearch');
  const sort = document.getElementById('pfSort');
  if (!grid) return;

  let DATA = [];
  let viewList = [];
  let cat = '전체';

  // URL ?cat= 초기 탭
  const qp = new URLSearchParams(location.search);
  if (qp.get('cat')) cat = qp.get('cat');

  const fmt = n => n.toLocaleString('ko-KR');

  const card = (p, i) => `
    <div class="pf-card" id="${p.id}" data-idx="${i}">
      <button type="button" class="pf-open" data-idx="${i}" aria-label="${p.title} 크게 보기">
        <span class="thumb"><img src="${p.thumb}" alt="${p.title}" loading="lazy" onerror="this.src='images/placeholder/ph-${p.category}.svg'"/></span>
        <span class="body">
          <span class="cat">${p.category} · ${p.industry}</span>
          <h4>${p.title}</h4>
          <span class="desc">${p.summary}</span>
          <span class="tags">${(p.tags||[]).slice(0,3).map(t=>`<span class="tag">#${t}</span>`).join('')}</span>
        </span>
      </button>
      <a class="apply" href="apply.html?ref=${p.id}&style=${encodeURIComponent(p.category)}">이 스타일로 신청하기 →</a>
    </div>`;

  function render() {
    const q = (search?.value || '').trim().toLowerCase();
    let list = DATA.slice();
    if (cat !== '전체') list = list.filter(p => p.category === cat);
    if (q) list = list.filter(p => (p.title + p.industry + p.summary + (p.tags || []).join(' ')).toLowerCase().includes(q));
    if (sort?.value === 'priceAsc') list.sort((a, b) => a.price - b.price);
    else if (sort?.value === 'priceDesc') list.sort((a, b) => b.price - a.price);

    viewList = list;
    grid.innerHTML = list.length
      ? list.map((p, i) => card(p, i)).join('')
      : '<div class="pf-empty">검색 조건에 맞는 작품이 없습니다. 다른 키워드를 시도해 보세요.</div>';
  }

  // ---------- Modal ----------
  const modal = document.createElement('div');
  modal.className = 'pf-modal';
  modal.hidden = true;
  modal.innerHTML = `
    <button type="button" class="pf-m-close" aria-label="닫기">×</button>
    <button type="button" class="pf-m-prev" aria-label="이전">‹</button>
    <button type="button" class="pf-m-next" aria-label="다음">›</button>
    <div class="pf-m-inner">
      <img class="pf-m-img" alt="" />
      <div class="pf-m-meta">
        <div class="pf-m-cat"></div>
        <h3 class="pf-m-title"></h3>
        <p class="pf-m-desc"></p>
        <a class="pf-m-apply btn btn-primary" href="#">이 스타일로 신청하기 →</a>
      </div>
    </div>`;
  document.body.appendChild(modal);
  const mImg   = modal.querySelector('.pf-m-img');
  const mCat   = modal.querySelector('.pf-m-cat');
  const mTitle = modal.querySelector('.pf-m-title');
  const mDesc  = modal.querySelector('.pf-m-desc');
  const mApply = modal.querySelector('.pf-m-apply');
  const mInner = modal.querySelector('.pf-m-inner');
  let cur = 0;

  function openModal(idx) {
    if (!viewList.length) return;
    cur = (idx + viewList.length) % viewList.length;
    const p = viewList[cur];
    const big = p.detail || (p.thumb || '').replace(/(\.[a-z]+)$/i, '-2$1');
    mImg.src = big;
    mImg.onerror = () => {
      // 큰 이미지가 없으면 썸네일로, 그것마저 없으면 카테고리 placeholder
      if (mImg.dataset.fallback !== '1') { mImg.dataset.fallback = '1'; mImg.src = p.thumb; }
      else if (mImg.dataset.fallback !== '2') { mImg.dataset.fallback = '2'; mImg.src = `images/placeholder/ph-${p.category}.svg`; }
    };
    mImg.dataset.fallback = '';
    mImg.alt = p.title;
    mCat.textContent = `${p.category} · ${p.industry} · ${p.period}`;
    mTitle.textContent = p.title;
    mDesc.textContent = p.summary || '';
    mApply.href = `apply.html?ref=${p.id}&style=${encodeURIComponent(p.category)}`;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    mInner.scrollTop = 0;
  }
  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    mImg.src = '';
  }
  function move(d) { openModal(cur + d); }

  grid.addEventListener('click', e => {
    const btn = e.target.closest('.pf-open');
    if (!btn) return;
    e.preventDefault();
    openModal(parseInt(btn.dataset.idx, 10) || 0);
  });
  modal.querySelector('.pf-m-close').addEventListener('click', closeModal);
  modal.querySelector('.pf-m-prev').addEventListener('click', () => move(-1));
  modal.querySelector('.pf-m-next').addEventListener('click', () => move(1));
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', e => {
    if (modal.hidden) return;
    if (e.key === 'Escape') closeModal();
    else if (e.key === 'ArrowLeft') move(-1);
    else if (e.key === 'ArrowRight') move(1);
  });

  function setTab(target) {
    cat = target;
    tabs?.querySelectorAll('.pf-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
    render();
  }

  fetch('assets/data/portfolio.json').then(r => r.json()).then(d => {
    DATA = d;
    if (cat !== '전체') setTab(cat); else render();
    injectItemListJsonLd(d);
  });

  function injectItemListJsonLd(list) {
    const origin = location.origin && location.origin.startsWith('http') ? location.origin : 'https://junosoft.co.kr';
    const items = list.slice(0, 50).map((p, i) => ({
      '@type': 'ListItem',
      'position': i + 1,
      'item': {
        '@type': 'CreativeWork',
        'name': p.title,
        'description': p.summary,
        'image': origin + '/' + (p.thumb || '').replace(/^\//, ''),
        'url': origin + '/portfolio.html#' + p.id,
        'offers': { '@type': 'Offer', 'priceCurrency': 'KRW', 'price': p.price }
      }
    }));
    const ld = {
      '@context': 'https://schema.org',
      '@type': 'ItemList',
      'name': '주노소프트 포트폴리오',
      'numberOfItems': list.length,
      'itemListElement': items
    };
    const s = document.createElement('script');
    s.type = 'application/ld+json';
    s.textContent = JSON.stringify(ld);
    document.head.appendChild(s);
  }

  tabs?.addEventListener('click', e => {
    const b = e.target.closest('.pf-tab'); if (!b) return; setTab(b.dataset.cat);
  });
  search?.addEventListener('input', render);
  sort?.addEventListener('change', render);
})();
