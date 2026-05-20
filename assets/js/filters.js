/* Menu filtering + live search (client-side, no reload) */
(function () {
  'use strict';

  var search = document.getElementById('menuSearch');
  var diet = Array.prototype.slice.call(
    document.querySelectorAll('[data-filter-diet]')
  );
  var spice = document.getElementById('filterSpice');
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-product]'));
  var blocks = Array.prototype.slice.call(document.querySelectorAll('.cat-block'));
  var countEl = document.getElementById('resultCount');

  function apply() {
    var q = (search && search.value || '').trim().toLowerCase();
    var wants = diet.filter(function (c) { return c.checked; })
      .map(function (c) { return c.dataset.filterDiet; });
    var maxSpice = spice ? parseInt(spice.value, 10) : 3;
    var shown = 0;

    items.forEach(function (el) {
      var name = el.dataset.name.toLowerCase();
      var d = el.dataset;
      var ok = true;
      if (q && name.indexOf(q) === -1) ok = false;
      wants.forEach(function (w) {
        if (d[w] !== '1') ok = false;
      });
      if (parseInt(d.spice, 10) > maxSpice) ok = false;
      el.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    blocks.forEach(function (b) {
      var any = b.querySelectorAll('[data-product]:not([style*="display: none"])').length;
      b.style.display = any ? '' : 'none';
    });

    if (countEl) {
      countEl.textContent = shown + (shown === 1 ? ' item' : ' items');
    }
    var none = document.getElementById('noResults');
    if (none) none.hidden = shown !== 0;
  }

  if (search) search.addEventListener('input', apply);
  diet.forEach(function (c) { c.addEventListener('change', apply); });
  if (spice) {
    spice.addEventListener('input', function () {
      var lbl = document.getElementById('spiceVal');
      var names = ['Any', 'Mild', 'Medium', 'Hot'];
      if (lbl) lbl.textContent = names[parseInt(spice.value, 10)] || 'Any';
      apply();
    });
  }

  /* Active category highlight on scroll */
  var navLinks = Array.prototype.slice.call(document.querySelectorAll('.cat-nav a'));
  if ('IntersectionObserver' in window && navLinks.length) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          navLinks.forEach(function (a) {
            a.classList.toggle('active', a.getAttribute('href') === '#' + en.target.id);
          });
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    blocks.forEach(function (b) { obs.observe(b); });
  }
})();
