/* Core UI: mobile nav, dark mode, order modal, postcode checker, newsletter */
(function () {
  'use strict';

  /* ---- Mobile nav ---- */
  var navToggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('primary-nav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---- Dark mode toggle ---- */
  var themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var root = document.documentElement;
      var dark = root.getAttribute('data-theme') === 'dark';
      if (dark) {
        root.removeAttribute('data-theme');
        try { localStorage.setItem('theme', 'light'); } catch (e) {}
      } else {
        root.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('theme', 'dark'); } catch (e) {}
      }
    });
  }

  /* ---- Modal open/close ---- */
  function openModal(id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    m.setAttribute('aria-hidden', 'false');
    var f = m.querySelector('input, button, a');
    if (f) f.focus();
  }
  function closeModal(m) {
    m.classList.remove('open');
    m.setAttribute('aria-hidden', 'true');
  }
  document.querySelectorAll('[data-open-modal]').forEach(function (b) {
    b.addEventListener('click', function () { openModal(b.dataset.openModal); });
  });
  document.querySelectorAll('[data-close-modal]').forEach(function (b) {
    b.addEventListener('click', function () {
      closeModal(b.closest('.modal'));
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var open = document.querySelector('.modal.open');
      if (open) closeModal(open);
    }
  });

  /* ---- Order-type toggle inside modal ---- */
  document.querySelectorAll('.toggle-group').forEach(function (group) {
    group.querySelectorAll('.toggle').forEach(function (t) {
      t.addEventListener('click', function () {
        group.querySelectorAll('.toggle').forEach(function (x) {
          x.classList.remove('is-active');
          x.setAttribute('aria-selected', 'false');
        });
        t.classList.add('is-active');
        t.setAttribute('aria-selected', 'true');
        var type = t.dataset.orderType;
        var scope = group.closest('.modal-card, form, body');
        scope.querySelectorAll('[data-mode]').forEach(function (el) {
          el.hidden = el.dataset.mode !== type;
        });
        var hidden = scope.querySelector('input[name="order_type"]');
        if (hidden) hidden.value = type;
      });
    });
  });

  /* ---- Postcode checker (modal + any .pc-check widget) ---- */
  function wirePostcode(btn, input, result) {
    if (!btn || !input || !result) return;
    function run() {
      var pc = input.value.trim();
      if (!pc) { return; }
      result.textContent = 'Checking…';
      result.className = 'pc-result';
      fetch('api/postcode.php?postcode=' + encodeURIComponent(pc))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          result.textContent = d.message;
          result.classList.add(d.ok ? 'ok' : 'no');
        })
        .catch(function () {
          result.textContent = 'Could not check right now.';
          result.classList.add('no');
        });
    }
    btn.addEventListener('click', run);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); run(); }
    });
  }
  wirePostcode(
    document.getElementById('modalPcCheck'),
    document.getElementById('modalPostcode'),
    document.getElementById('modalPcResult')
  );
  wirePostcode(
    document.getElementById('heroPcCheck'),
    document.getElementById('heroPostcode'),
    document.getElementById('heroPcResult')
  );

  /* ---- Newsletter ---- */
  var nf = document.getElementById('newsletterForm');
  if (nf) {
    nf.addEventListener('submit', function (e) {
      e.preventDefault();
      var out = document.getElementById('newsResult');
      var email = document.getElementById('newsEmail').value;
      out.textContent = 'Signing up…';
      var body = new FormData();
      body.append('email', email);
      fetch('api/newsletter.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          out.textContent = d.message;
          if (d.ok) nf.reset();
        })
        .catch(function () { out.textContent = 'Something went wrong.'; });
    });
  }

  /* ---- Image gallery / lightbox (product pages) ---- */
  document.querySelectorAll('[data-lightbox]').forEach(function (el) {
    el.addEventListener('click', function () {
      var box = document.createElement('div');
      box.className = 'modal open';
      box.innerHTML = '<div class="modal-overlay"></div>' +
        '<div class="modal-card" style="max-width:560px">' +
        el.outerHTML.replace('data-lightbox', '') + '</div>';
      box.addEventListener('click', function () { box.remove(); });
      document.body.appendChild(box);
    });
  });
})();
