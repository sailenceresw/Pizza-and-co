/* Cart: AJAX add/update/remove with live badge + ARIA announcements */
(function () {
  'use strict';

  var badge = document.getElementById('cartBadge');

  function announce(msg) {
    var live = document.getElementById('cartLive');
    if (!live) {
      live = document.createElement('div');
      live.id = 'cartLive';
      live.setAttribute('aria-live', 'polite');
      live.setAttribute('role', 'status');
      live.className = 'visually-hidden';
      document.body.appendChild(live);
    }
    live.textContent = msg;
  }

  function setBadge(n) {
    if (!badge) return;
    badge.textContent = n;
    badge.dataset.count = n;
    badge.hidden = n < 1;
    if (n > 0) {
      badge.animate(
        [{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }],
        { duration: 280 }
      );
    }
  }

  function post(action, data) {
    var body = new FormData();
    body.append('action', action);
    Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
    return fetch('api/cart.php', { method: 'POST', body: body })
      .then(function (r) { return r.json(); });
  }

  /* ---- Add to cart (forms with data-add-cart) ---- */
  document.querySelectorAll('form[data-add-cart]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type=submit]');
      if (btn) { btn.disabled = true; btn.textContent = 'Adding…'; }
      post('add', Object.fromEntries(new FormData(form)))
        .then(function (d) {
          if (d.ok) {
            setBadge(d.count);
            announce(d.name + ' added to cart. ' + d.count + ' items.');
            if (btn) { btn.disabled = false; btn.textContent = 'Added ✓ — add another?'; }
            showToast(d.name + ' added to your cart');
          } else {
            if (btn) { btn.disabled = false; btn.textContent = 'Add to cart'; }
            alert(d.message || 'Could not add item.');
          }
        })
        .catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = 'Add to cart'; }
        });
    });
  });

  /* ---- Quick-add buttons (menu cards) ---- */
  document.querySelectorAll('[data-quick-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      btn.disabled = true;
      post('add', { name: d.name, unit_price: d.price, options: d.options || '{}' })
        .then(function (res) {
          btn.disabled = false;
          if (res.ok) {
            setBadge(res.count);
            announce(d.name + ' added to cart.');
            showToast(d.name + ' added');
            btn.textContent = 'Added ✓';
            setTimeout(function () { btn.textContent = 'Add +'; }, 1400);
          }
        })
        .catch(function () { btn.disabled = false; });
    });
  });

  /* ---- Cart page line controls ---- */
  document.querySelectorAll('[data-cart-update]').forEach(function (el) {
    el.addEventListener('change', function () {
      post('update', { ref: el.dataset.ref, qty: el.value })
        .then(function () { location.reload(); });
    });
  });
  document.querySelectorAll('[data-cart-remove]').forEach(function (el) {
    el.addEventListener('click', function () {
      post('remove', { ref: el.dataset.ref })
        .then(function () { location.reload(); });
    });
  });

  /* ---- Toast ---- */
  function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    t.style.cssText =
      'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);' +
      'background:#221F1C;color:#fff;padding:.8rem 1.3rem;border-radius:999px;' +
      'font-weight:600;z-index:300;box-shadow:0 10px 30px rgba(0,0,0,.3);' +
      'opacity:0;transition:opacity .2s,transform .2s';
    document.body.appendChild(t);
    requestAnimationFrame(function () {
      t.style.opacity = '1';
      t.style.transform = 'translateX(-50%) translateY(-6px)';
    });
    setTimeout(function () {
      t.style.opacity = '0';
      setTimeout(function () { t.remove(); }, 250);
    }, 2200);
  }
})();
