<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Your Cart';
$pageClass = 'cart';

$lines = cart();
$subtotal = cart_subtotal();
$belowMin = $subtotal > 0 && $subtotal < MIN_ORDER;

require __DIR__ . '/includes/header.php';
?>

<div class="wrap section">
  <h1>Your cart</h1>

  <?php if (!$lines): ?>
    <div class="empty-state">
      <div class="big" aria-hidden="true">🛒</div>
      <h2>Your cart is empty</h2>
      <p class="muted">Let’s fix that — the parmos are calling.</p>
      <a class="btn btn-primary" href="menu.php">Browse the menu</a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <div>
        <?php foreach ($lines as $line):
            $opts = options_summary($line['options']); ?>
          <div class="cart-line">
            <div>
              <div class="name"><?= e($line['name']) ?></div>
              <?php if ($opts): ?><div class="opts"><?= e($opts) ?></div><?php endif; ?>
              <div class="muted"><?= money($line['unit_price']) ?> each</div>
            </div>
            <div class="qty-stepper" aria-label="Quantity for <?= e($line['name']) ?>">
              <button type="button" data-cart-step="-1" data-ref="<?= e($line['ref']) ?>"
                      aria-label="Decrease">−</button>
              <input type="text" value="<?= (int) $line['qty'] ?>" readonly
                     data-qty-for="<?= e($line['ref']) ?>">
              <button type="button" data-cart-step="1" data-ref="<?= e($line['ref']) ?>"
                      aria-label="Increase">+</button>
            </div>
            <div style="text-align:right">
              <strong><?= money($line['unit_price'] * $line['qty']) ?></strong><br>
              <button class="btn btn-ghost btn-sm" data-cart-remove
                      data-ref="<?= e($line['ref']) ?>">Remove</button>
            </div>
          </div>
        <?php endforeach; ?>

        <p style="margin-top:1rem">
          <a href="menu.php">← Add more items</a> ·
          <button class="btn btn-ghost btn-sm" id="clearCart">Clear cart</button>
        </p>
      </div>

      <aside class="summary">
        <h2 style="font-size:1.3rem">Summary</h2>
        <div class="row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
        <div class="row"><span>Delivery</span>
          <span><?= $subtotal >= FREE_DELIVERY_THRESHOLD ? 'FREE' : 'from ' . money(DELIVERY_FEE) ?></span></div>
        <div class="row total"><span>Total</span><span><?= money($subtotal) ?>+</span></div>

        <?php if ($belowMin): ?>
          <p class="note" style="margin:1rem 0">Minimum order is
            <?= money(MIN_ORDER) ?>. Add <?= money(MIN_ORDER - $subtotal) ?> more
            to check out.</p>
          <button class="btn btn-primary btn-block" disabled>Checkout</button>
        <?php else: ?>
          <a class="btn btn-primary btn-block" href="checkout.php">Go to checkout →</a>
        <?php endif; ?>
        <p class="muted" style="font-size:.82rem;margin-top:1rem">
          Free delivery over <?= money(FREE_DELIVERY_THRESHOLD) ?>.
          Final delivery fee confirmed at checkout by postcode.</p>
      </aside>
    </div>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-cart-step]').forEach(function (b) {
  b.addEventListener('click', function () {
    var ref = b.dataset.ref;
    var input = document.querySelector('[data-qty-for="' + ref + '"]');
    var q = Math.max(0, parseInt(input.value, 10) + parseInt(b.dataset.cartStep, 10));
    var body = new FormData();
    body.append('action', 'update'); body.append('ref', ref); body.append('qty', q);
    fetch('api/cart.php', { method: 'POST', body: body }).then(function () {
      location.reload();
    });
  });
});
var clear = document.getElementById('clearCart');
if (clear) clear.addEventListener('click', function () {
  if (!confirm('Empty your whole cart?')) return;
  var body = new FormData(); body.append('action', 'clear');
  fetch('api/cart.php', { method: 'POST', body: body }).then(function () {
    location.reload();
  });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
