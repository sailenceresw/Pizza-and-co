<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$lines = cart();
$subtotal = cart_subtotal();

if (!$lines) {
    flash('Your cart is empty.');
    redirect('menu.php');
}
if ($subtotal < MIN_ORDER) {
    flash('Minimum order is ' . money(MIN_ORDER) . '.');
    redirect('cart.php');
}

$u = current_user();
$errors = [];

// Build the next few collection/delivery time slots.
$slots = [];
$start = new DateTime('now');
$start->modify('+45 minutes');
$mins = (int) $start->format('i');
$start->modify('+' . ((15 - $mins % 15) % 15) . ' minutes');
for ($i = 0; $i < 10; $i++) {
    $slots[] = (clone $start)->modify("+" . ($i * 20) . " minutes")->format('H:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = ($_POST['order_type'] ?? 'delivery') === 'collection' ? 'collection' : 'delivery';
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $slot = (string) ($_POST['time_slot'] ?? '');
    $pay = in_array($_POST['payment_method'] ?? '', ['cash', 'card'], true)
        ? $_POST['payment_method'] : 'cash';
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $addr = '';
    $postcode = '';
    $deliveryFee = 0.0;

    if (strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!preg_match('/^[0-9 +()]{7,}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!in_array($slot, $slots, true)) {
        $errors[] = 'Please pick a time slot.';
    }

    if ($type === 'delivery') {
        $l1 = trim((string) ($_POST['line1'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $postcode = strtoupper(trim((string) ($_POST['postcode'] ?? '')));
        if (strlen($l1) < 3 || strlen($city) < 2) {
            $errors[] = 'Please complete your delivery address.';
        }
        $zone = check_delivery($postcode);
        if (!$zone['ok']) {
            $errors[] = 'Sorry — ' . $postcode . ' is outside our delivery zone. '
                . 'You can switch to collection above.';
        } else {
            $deliveryFee = $subtotal >= FREE_DELIVERY_THRESHOLD ? 0.0 : $zone['fee'];
        }
        $addr = trim($l1 . ', ' . ($_POST['line2'] ?? '') . ', ' . $city);
    }

    if (!$errors) {
        $total = round($subtotal + $deliveryFee, 2);
        $pdo->beginTransaction();
        $ins = $pdo->prepare(
            'INSERT INTO orders
             (user_id,order_type,status,customer_name,phone,email,address,
              postcode,time_slot,payment_method,notes,subtotal,delivery_fee,total)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([
            $u['id'] ?? null, $type, 'received', $name, $phone, $email,
            $addr, $postcode, $slot, $pay, $notes,
            $subtotal, $deliveryFee, $total,
        ]);
        $orderId = (int) $pdo->lastInsertId();
        $li = $pdo->prepare(
            'INSERT INTO order_items
             (order_id,name,options_json,qty,unit_price,line_total)
             VALUES (?,?,?,?,?,?)'
        );
        foreach ($lines as $line) {
            $li->execute([
                $orderId, $line['name'], json_encode($line['options']),
                $line['qty'], $line['unit_price'],
                round($line['unit_price'] * $line['qty'], 2),
            ]);
        }
        $pdo->commit();
        cart_clear();
        $_SESSION['last_order'] = $orderId;
        redirect('order-confirmation.php?id=' . $orderId);
    }
}

$pageTitle = 'Checkout';
$pageClass = 'checkout';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap section">
  <h1>Checkout</h1>
  <div class="steps">
    <span>1 · Your details</span><span>2 · Time &amp; payment</span>
    <span>3 · Confirm</span>
  </div>

  <?php if ($errors): ?>
    <div class="note" style="border-color:var(--tomato)">
      <strong class="error-text">Please check the form:</strong>
      <ul style="margin:.5rem 0 0"><?php foreach ($errors as $e):?>
        <li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="cart-layout">
    <form method="post" class="stack" autocomplete="on">
      <?= csrf_field() ?>
      <input type="hidden" name="order_type" value="delivery">

      <div class="form-card form-wide" style="max-width:none">
        <h2 style="font-size:1.3rem">Delivery or collection?</h2>
        <div class="toggle-group" role="tablist" aria-label="Order type">
          <button type="button" class="toggle is-active" data-order-type="delivery"
                  role="tab" aria-selected="true">🛵 Delivery</button>
          <button type="button" class="toggle" data-order-type="collection"
                  role="tab" aria-selected="false">🏬 Collection</button>
        </div>

        <div class="grid cols-2">
          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" required
                   value="<?= e($_POST['name'] ?? ($u['name'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" required
                   value="<?= e($_POST['phone'] ?? ($u['phone'] ?? '')) ?>">
          </div>
        </div>
        <div class="field">
          <label for="email">Email (for your receipt)</label>
          <input type="email" id="email" name="email"
                 value="<?= e($_POST['email'] ?? ($u['email'] ?? '')) ?>">
        </div>

        <div data-mode="delivery">
          <div class="field">
            <label for="line1">Address line 1</label>
            <input type="text" id="line1" name="line1"
                   value="<?= e($_POST['line1'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="line2">Address line 2 (optional)</label>
            <input type="text" id="line2" name="line2"
                   value="<?= e($_POST['line2'] ?? '') ?>">
          </div>
          <div class="grid cols-2">
            <div class="field">
              <label for="city">Town / City</label>
              <input type="text" id="city" name="city"
                     value="<?= e($_POST['city'] ?? 'Thornaby') ?>">
            </div>
            <div class="field">
              <label for="postcode">Postcode</label>
              <input type="text" id="postcode" name="postcode"
                     placeholder="TS17 0EJ" value="<?= e($_POST['postcode'] ?? '') ?>">
              <p class="muted" id="pcLive" style="font-size:.85rem;margin:.4rem 0 0"></p>
            </div>
          </div>
        </div>

        <div data-mode="collection" hidden>
          <p class="note">Collect from <strong><?= e(BRAND_ADDRESS) ?></strong>
            (<?= e(BRAND_POSTCODE) ?>). No delivery fee.</p>
        </div>
      </div>

      <div class="form-card form-wide" style="max-width:none">
        <h2 style="font-size:1.3rem">Time &amp; payment</h2>
        <div class="field">
          <label for="time_slot">Preferred time</label>
          <select id="time_slot" name="time_slot" required>
            <option value="">Choose a slot…</option>
            <?php foreach ($slots as $s): ?>
              <option value="<?= e($s) ?>" <?= ($_POST['time_slot'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Payment</label>
          <ul class="opt-list">
            <li><label><span><input type="radio" name="payment_method"
              value="cash" checked> Cash / card on arrival</span></label></li>
            <li><label><span><input type="radio" name="payment_method"
              value="card"> Pay by card now</span>
              <span class="pill-info">DEMO</span></label></li>
          </ul>
          <div class="note" data-card hidden>
            <strong>Demo payment.</strong> No real card is taken — this stub
            simulates a successful payment for the coursework build.
            <div class="grid cols-2" style="margin-top:.7rem">
              <input type="text" placeholder="Card number 4242 4242 4242 4242" disabled>
              <input type="text" placeholder="MM/YY · CVC" disabled>
            </div>
          </div>
        </div>
        <div class="field">
          <label for="notes">Order notes (allergies, directions…)</label>
          <textarea id="notes" name="notes" rows="2"><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>
      </div>

      <button class="btn btn-primary" style="align-self:flex-start">
        Place order
      </button>
    </form>

    <aside class="summary">
      <h2 style="font-size:1.3rem">Your order</h2>
      <?php foreach ($lines as $line): ?>
        <div class="row">
          <span><?= (int) $line['qty'] ?>× <?= e($line['name']) ?></span>
          <span><?= money($line['unit_price'] * $line['qty']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr class="soft" style="margin:1rem 0">
      <div class="row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div class="row"><span>Delivery</span>
        <span id="sumDelivery"><?= $subtotal >= FREE_DELIVERY_THRESHOLD
          ? 'FREE' : money(DELIVERY_FEE) ?></span></div>
      <div class="row total"><span>Total</span>
        <span id="sumTotal"><?= money($subtotal +
          ($subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : DELIVERY_FEE)) ?></span></div>
      <p class="muted" style="font-size:.8rem;margin-top:1rem">
        <?php if (is_logged_in()): ?>Signed in as <?= e($u['email']) ?>.
        <?php else: ?><a href="login.php?next=checkout.php">Sign in</a> to save
        this order to your account.<?php endif; ?>
      </p>
    </aside>
  </div>
</div>

<script>
(function () {
  var sub = <?= json_encode($subtotal) ?>;
  var fee = <?= json_encode((float) DELIVERY_FEE) ?>;
  var freeAt = <?= json_encode((float) FREE_DELIVERY_THRESHOLD) ?>;
  var typeInput = document.querySelector('input[name=order_type]');
  var sumD = document.getElementById('sumDelivery');
  var sumT = document.getElementById('sumTotal');

  function refresh() {
    var deliver = typeInput.value === 'delivery';
    var d = (!deliver || sub >= freeAt) ? 0 : fee;
    sumD.textContent = !deliver ? '—' : (d === 0 ? 'FREE' : '£' + d.toFixed(2));
    sumT.textContent = '£' + (sub + d).toFixed(2);
  }
  document.querySelectorAll('.toggle').forEach(function (b) {
    b.addEventListener('click', refresh);
  });
  // live postcode hint
  var pc = document.getElementById('postcode');
  var live = document.getElementById('pcLive');
  if (pc) pc.addEventListener('blur', function () {
    if (!pc.value.trim()) return;
    fetch('api/postcode.php?postcode=' + encodeURIComponent(pc.value))
      .then(function (r) { return r.json(); })
      .then(function (x) {
        live.textContent = x.message;
        live.style.color = x.ok ? 'var(--green)' : 'var(--tomato)';
      });
  });
  // card stub reveal
  document.querySelectorAll('input[name=payment_method]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelector('[data-card]').hidden =
        document.querySelector('input[name=payment_method]:checked').value !== 'card';
    });
  });
  refresh();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
