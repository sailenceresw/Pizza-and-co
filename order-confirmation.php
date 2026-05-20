<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

// Only show if it's the buyer's just-placed order, the owner, or an admin.
$allowed = $order && (
    ($_SESSION['last_order'] ?? null) === $id
    || (is_logged_in() && (int) $order['user_id'] === (int) current_user()['id'])
    || is_admin()
);

$pageTitle = 'Order Confirmed';
require __DIR__ . '/includes/header.php';

if (!$allowed) {
    echo '<div class="wrap section"><h1>Order not found</h1>'
        . '<p><a class="btn btn-primary" href="menu.php">Back to menu</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();
?>

<div class="wrap section" style="max-width:760px">
  <div class="center" style="margin-bottom:2rem">
    <div style="font-size:3.4rem" aria-hidden="true">🎉</div>
    <h1>Order received!</h1>
    <p class="muted">Order <strong>#<?= (int) $order['id'] ?></strong> ·
      We’ll have it ready for
      <strong><?= e($order['order_type']) ?></strong> around
      <strong><?= e($order['time_slot']) ?></strong>.</p>
    <?php $b = 's-' . (str_contains($order['status'], 'out') ? 'out'
        : str_replace(' ', '', $order['status'])); ?>
    <p><span class="status-badge <?= e($b) ?>"><?= e(ucfirst($order['status'])) ?></span></p>
  </div>

  <div class="form-card form-wide" style="max-width:none">
    <h2 style="font-size:1.25rem">Summary</h2>
    <?php foreach ($items as $it):
        $opts = options_summary(json_decode($it['options_json'], true) ?: []); ?>
      <div class="row" style="display:flex;justify-content:space-between;margin-bottom:.5rem">
        <span><?= (int) $it['qty'] ?>× <?= e($it['name']) ?>
          <?php if ($opts): ?><br><small class="muted"><?= e($opts) ?></small><?php endif; ?>
        </span>
        <span><?= money((float) $it['line_total']) ?></span>
      </div>
    <?php endforeach; ?>
    <hr class="soft" style="margin:1rem 0">
    <div class="row" style="display:flex;justify-content:space-between">
      <span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
    <div class="row" style="display:flex;justify-content:space-between">
      <span>Delivery</span><span><?= $order['delivery_fee'] > 0
        ? money((float) $order['delivery_fee']) : 'FREE' ?></span></div>
    <div class="row total" style="display:flex;justify-content:space-between;
         font-weight:800;font-size:1.2rem;margin-top:.6rem">
      <span>Total</span><span><?= money((float) $order['total']) ?></span></div>

    <hr class="soft" style="margin:1.2rem 0">
    <p><strong><?= e($order['customer_name']) ?></strong> · <?= e($order['phone']) ?></p>
    <?php if ($order['order_type'] === 'delivery'): ?>
      <p class="muted"><?= e($order['address']) ?>, <?= e($order['postcode']) ?></p>
    <?php else: ?>
      <p class="muted">Collection from <?= e(BRAND_ADDRESS) ?></p>
    <?php endif; ?>
    <p class="muted">Payment: <?= e($order['payment_method']) === 'card'
      ? 'Paid by card (demo)' : 'On arrival' ?></p>
  </div>

  <p class="center" style="margin-top:2rem">
    <?php if (is_logged_in()): ?>
      <a class="btn btn-primary" href="account.php">View in my account</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="menu.php">Order again</a>
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
