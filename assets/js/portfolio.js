(function () {
  const grid = document.getElementById('pfGrid');
  const tabs = document.getElementById('pfTabs');
  const search = document.getElementById('pfSearch');
  const sort = document.getElementById('pfSort');
  if (!grid) return;

  let DATA = [];
  let cat = '전체';

  // URL ?cat= 초기 탭
  const qp = new URLSearchParams(location.search);
  if (qp.get('cat')) cat = qp.get('cat');

  const fmt = n => n.toLocaleString('ko-KR');

  const card = p => `
    <a class="pf-card" id="${p.id}" href="apply.html?ref=${p.id}&style=${encodeURIComponent(p.category)}">
      <div class="thumb"><img src="${p.thumb}" alt="${p.title}" loading="lazy" onerror="this.src='images/placeholder/ph-${p.category}.svg'"/></div>
      <div class="body">
        <div class="cat">${p.category} · ${p.industry}</div>
        <h4>${p.title}</h4>
        <p style="margin:0;color:#5C6577;font-size:13.5px;min-height:38px">${p.summary}</p>
        <div class="tags">${(p.tags||[]).slice(0,3).map(t=>`<span class="tag">#${t}</span>`).join('')}</div>
        <div class="meta" style="margin-top:10px"><span>${p.period}</span><span class="price">${fmt(p.price)}원~</span></div>
      </div>
      <span class="apply">이 스타일로 신청하기 →</span>
    </a>`;

  function render() {
    const q = (search?.value || '').trim().toLowerCase();
    let list = DATA.slice();
    if (cat !== '전체') list = list.filter(p => p.category === cat);
    if (q) list = list.filter(p => (p.title + p.industry + p.summary + (p.tags || []).join(' ')).toLowerCase().includes(q));
    if (sort?.value === 'priceAsc') list.sort((a, b) => a.price - b.price);
    else if (sort?.value === 'priceDesc') list.sort((a, b) => b.price - a.price);

    grid.innerHTML = list.length
      ? list.map(card).join('')
      : '<div class="pf-empty">검색 조건에 맞는 작품이 없습니다. 다른 키워드를 시도해 보세요.</div>';
  }

  function setTab(target) {
    cat = target;
    tabs?.querySelectorAll('.pf-tab').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
    render();
  }

  fetch('assets/data/portfolio.json').then(r => r.json()).then(d => {
    DATA = d;
    if (cat !== '전체') setTab(cat); else render();
  });

  tabs?.addEventListener('click', e => {
    const b = e.target.closest('.pf-tab'); if (!b) return; setTab(b.dataset.cat);
  });
  search?.addEventListener('input', render);
  sort?.addEventListener('change', render);
})();
