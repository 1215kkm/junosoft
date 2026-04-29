(function () {
  // Hamburger
  const ham = document.getElementById('hamburger');
  const menu = document.getElementById('menu');
  if (ham && menu) {
    ham.addEventListener('click', () => menu.classList.toggle('open'));
    menu.addEventListener('click', e => { if (e.target.tagName === 'A') menu.classList.remove('open'); });
  }

  // Year
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Counter
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const animate = el => {
      const target = parseFloat(el.dataset.count);
      const decimal = parseInt(el.dataset.decimal || '0', 10);
      const suffix = el.dataset.suffix || '';
      const dur = 1400;
      const start = performance.now();
      const tick = now => {
        const t = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - t, 3);
        const v = target * eased;
        el.textContent = (decimal ? v.toFixed(decimal) : Math.round(v).toLocaleString()) + suffix;
        if (t < 1) requestAnimationFrame(tick);
        else el.textContent = (decimal ? target.toFixed(decimal) : Math.round(target).toLocaleString()) + suffix;
      };
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (en.isIntersecting) { animate(en.target); io.unobserve(en.target); }
      });
    }, { threshold: 0.4 });
    counters.forEach(c => io.observe(c));
  }

  // Reveal on scroll
  const reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    const io2 = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (en.isIntersecting) { en.target.classList.add('in'); io2.unobserve(en.target); }
      });
    }, { threshold: 0.15 });
    reveals.forEach(r => io2.observe(r));
  }
})();
