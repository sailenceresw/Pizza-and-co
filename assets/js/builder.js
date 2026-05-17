/* Pizza Builder — the headline JS feature.
   Live price, visual topping placement, keyboard-accessible, AJAX add. */
(function () {
  'use strict';

  var root = document.getElementById('builder');
  if (!root) return;

  var BASE = parseFloat(root.dataset.base || '6.5');
  var stage = document.getElementById('builderBase');
  var totalEl = document.getElementById('builderTotal');
  var summaryEl = document.getElementById('builderSummary');

  var state = {
    size: null, sizePrice: 0, sizeLabel: '',
    crust: null, crustPrice: 0, crustLabel: '',
    sauce: 'tomato', sauceLabel: 'Tomato',
    toppings: [] // {name, price}
  };

  function recalc() {
    var t = BASE + state.sizePrice + state.crustPrice;
    state.toppings.forEach(function (x) { t += x.price; });
    totalEl.textContent = '£' + t.toFixed(2);
    var bits = [];
    if (state.sizeLabel) bits.push(state.sizeLabel);
    if (state.crustLabel) bits.push(state.crustLabel);
    bits.push(state.sauceLabel + ' sauce');
    bits.push(state.toppings.length
      ? state.toppings.map(function (x) { return x.name; }).join(', ')
      : 'no extra toppings');
    summaryEl.textContent = bits.join(' · ');
    return t;
  }

  /* size + crust radio groups */
  root.querySelectorAll('input[name="b_size"]').forEach(function (r) {
    r.addEventListener('change', function () {
      state.size = r.value;
      state.sizePrice = parseFloat(r.dataset.price);
      state.sizeLabel = r.dataset.label;
      recalc();
    });
  });
  root.querySelectorAll('input[name="b_crust"]').forEach(function (r) {
    r.addEventListener('change', function () {
      state.crust = r.value;
      state.crustPrice = parseFloat(r.dataset.price);
      state.crustLabel = r.dataset.label;
      recalc();
    });
  });

  /* sauce */
  root.querySelectorAll('[data-sauce]').forEach(function (b) {
    b.addEventListener('click', function () {
      root.querySelectorAll('[data-sauce]').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
      state.sauce = b.dataset.sauce;
      state.sauceLabel = b.textContent.trim();
      stage.className = 'builder-base sauce-' + state.sauce;
      recalc();
    });
  });

  /* toppings (toggle pills) — also draw a dot on the pizza */
  function drawDot(name, color) {
    var d = document.createElement('span');
    d.className = 'b-top';
    d.dataset.name = name;
    d.style.background = color;
    d.style.left = (20 + Math.random() * 60) + '%';
    d.style.top = (20 + Math.random() * 60) + '%';
    stage.appendChild(d);
  }
  root.querySelectorAll('[data-topping]').forEach(function (pill) {
    pill.setAttribute('role', 'button');
    pill.setAttribute('tabindex', '0');
    pill.setAttribute('aria-pressed', 'false');
    function toggle() {
      var name = pill.dataset.topping;
      var price = parseFloat(pill.dataset.price);
      var color = pill.dataset.color || '#C0392B';
      var i = state.toppings.findIndex(function (x) { return x.name === name; });
      if (i > -1) {
        state.toppings.splice(i, 1);
        pill.classList.remove('on');
        pill.setAttribute('aria-pressed', 'false');
        var dot = stage.querySelector('.b-top[data-name="' + CSS.escape(name) + '"]');
        if (dot) dot.remove();
      } else {
        if (state.toppings.length >= 8) {
          alert('Max 8 toppings — that is one loaded pizza already!');
          return;
        }
        state.toppings.push({ name: name, price: price });
        pill.classList.add('on');
        pill.setAttribute('aria-pressed', 'true');
        drawDot(name, color);
      }
      recalc();
    }
    pill.addEventListener('click', toggle);
    pill.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
  });

  /* add to cart */
  var addBtn = document.getElementById('builderAdd');
  addBtn.addEventListener('click', function () {
    if (!state.size) { alert('Pick a size first 🍕'); return; }
    var price = recalc();
    var opts = {
      size: state.sizeLabel,
      crust: state.crustLabel || 'Classic',
      sauce: state.sauceLabel,
      toppings: state.toppings.map(function (x) { return x.name; })
    };
    addBtn.disabled = true;
    var body = new FormData();
    body.append('action', 'add');
    body.append('name', 'Custom Pizza');
    body.append('unit_price', price.toFixed(2));
    body.append('options', JSON.stringify(opts));
    fetch('api/cart.php', { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        addBtn.disabled = false;
        if (d.ok) {
          var badge = document.getElementById('cartBadge');
          if (badge) {
            badge.textContent = d.count; badge.dataset.count = d.count;
            badge.hidden = false;
          }
          addBtn.textContent = 'Added ✓ — build another?';
          setTimeout(function () { addBtn.textContent = 'Add custom pizza to cart'; }, 1800);
        }
      })
      .catch(function () { addBtn.disabled = false; });
  });

  recalc();
})();
