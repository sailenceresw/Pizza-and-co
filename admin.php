<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();

$STATUSES = ['received', 'preparing', 'out for delivery', 'complete', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = $_POST['form'] ?? '';

    if ($form === 'status') {
        $oid = (int) ($_POST['order_id'] ?? 0);
        $new = (string) ($_POST['status'] ?? '');
        if (in_array($new, $STATUSES, true)) {
            $pdo->prepare('UPDATE orders SET status=? WHERE id=?')
                ->execute([$new, $oid]);
            flash("Order #$oid → $new");
        }
        redirect('admin.php' . (empty($_POST['filter']) ? '' : '?filter=' . urlencode($_POST['filter'])));
    }

    if ($form === 'product_toggle') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id=?')
            ->execute([$pid]);
        redirect('admin.php?tab=menu');
    }

    if ($form === 'product_price') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $price = (float) ($_POST['base_price'] ?? 0);
        if ($price > 0) {
            $pdo->prepare('UPDATE products SET base_price=? WHERE id=?')
                ->execute([$price, $pid]);
        }
        redirect('admin.php?tab=menu');
    }
}

$tab = $_GET['tab'] ?? 'orders';
$filter = $_GET['filter'] ?? '';

// --- Stats ---
$today = date('Y-m-d');
$stats = [
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue' => (float) $pdo->query(
        "SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'"
    )->fetchColumn(),
    'today' => (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE date(created_at)='$today'"
    )->fetchColumn(),
    'pending' => (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE status IN ('received','preparing','out for delivery')"
    )->fetchColumn(),
];
$aov = $stats['orders'] ? $stats['revenue'] / $stats['orders'] : 0;

$pageTitle = 'Admin Dashboard';
$pageClass = 'admin';
require __DIR__ . '/includes/header.php';

$badge = static fn(string $s): string => 's-' . (str_contains($s, 'out')
    ? 'out' : str_replace(' ', '', $s));
?>
<div class="wrap section">
  <h1>Kitchen dashboard</h1>
  <p class="muted">Signed in as <?= e(current_user()['name']) ?> ·
    <a href="logout.php">Sign out</a></p>

  <div class="admin-stats">
    <div class="stat"><div class="n"><?= $stats['orders'] ?></div>
      <div class="muted">Total orders</div></div>
    <div class="stat"><div class="n"><?= money($stats['revenue']) ?></div>
      <div class="muted">Revenue</div></div>
    <div class="stat"><div class="n"><?= money($aov) ?></div>
      <div class="muted">Avg order value</div></div>
    <div class="stat"><div class="n"><?= $stats['pending'] ?></div>
      <div class="muted">In progress</div></div>
  </div>

  <div class="steps" style="margin-bottom:2rem">
    <a class="btn btn-<?= $tab === 'orders' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=orders">Orders</a>
    <a class="btn btn-<?= $tab === 'menu' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=menu">Menu &amp; prices</a>
    <a class="btn btn-<?= $tab === 'inbox' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=inbox">Inbox &amp; news</a>
  </div>

  <?php if ($tab === 'orders'):
      $where = in_array($filter, $STATUSES, true)
          ? 'WHERE status = ' . $pdo->quote($filter) : '';
      $orders = $pdo->query(
          "SELECT * FROM orders $where ORDER BY
           CASE status WHEN 'received' THEN 0 WHEN 'preparing' THEN 1
           WHEN 'out for delivery' THEN 2 ELSE 3 END, created_at DESC"
      )->fetchAll();
      $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?'); ?>

    <div class="flow" style="margin-bottom:1.5rem">
      <a class="pill-info" href="?tab=orders">All</a>
      <?php foreach ($STATUSES as $s): ?>
        <a class="pill-info" href="?tab=orders&filter=<?= urlencode($s) ?>"><?= e($s) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?><p class="muted">No orders here.</p><?php endif; ?>
    <?php foreach ($orders as $o):
        $itemStmt->execute([$o['id']]);
        $its = $itemStmt->fetchAll(); ?>
      <div class="order-row">
        <header>
          <div>
            <strong>#<?= (int) $o['id'] ?> · <?= e($o['customer_name']) ?></strong>
            <span class="muted"> · <?= e($o['phone']) ?> ·
              <?= e(ucfirst($o['order_type'])) ?> @ <?= e($o['time_slot']) ?>
              · <?= e($o['created_at']) ?></span>
          </div>
          <span class="status-badge <?= $badge($o['status']) ?>"><?= e($o['status']) ?></span>
        </header>

        <?php if ($o['order_type'] === 'delivery'): ?>
          <p class="muted" style="margin:.5rem 0">📍 <?= e($o['address']) ?>,
            <?= e($o['postcode']) ?></p>
        <?php endif; ?>

        <ul style="margin:.6rem 0">
          <?php foreach ($its as $it): ?>
            <li><?= (int) $it['qty'] ?>× <?= e($it['name']) ?>
              <span class="muted"><?= e(options_summary(
                json_decode($it['options_json'], true) ?: [])) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($o['notes']): ?>
          <p class="note">📝 <?= e($o['notes']) ?></p>
        <?php endif; ?>

        <div class="card-foot">
          <strong><?= money((float) $o['total']) ?>
            <span class="muted">(<?= e($o['payment_method']) ?>)</span></strong>
          <form method="post" class="flow" style="align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="status">
            <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <label class="visually-hidden" for="st<?= (int) $o['id'] ?>">Status</label>
            <select name="status" id="st<?= (int) $o['id'] ?>">
              <?php foreach ($STATUSES as $s): ?>
                <option <?= $o['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm">Update</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

  <?php elseif ($tab === 'menu'):
      $prods = $pdo->query(
          'SELECT p.*, c.name AS cat FROM products p
           JOIN categories c ON c.id=p.category_id ORDER BY c.sort, p.sort'
      )->fetchAll(); ?>
    <p class="muted">Toggle availability or adjust a price — changes are live.</p>
    <?php foreach ($prods as $p): ?>
      <div class="order-row" style="display:flex;justify-content:space-between;
           align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
          <strong><?= e($p['name']) ?></strong>
          <span class="muted"> · <?= e($p['cat']) ?></span>
          <?php if (!$p['is_active']): ?>
            <span class="status-badge s-cancelled">Hidden</span><?php endif; ?>
        </div>
        <div class="flow" style="align-items:center">
          <form method="post" class="flow" style="align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="product_price">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <input type="number" step="0.01" name="base_price"
                   value="<?= e(number_format((float) $p['base_price'], 2, '.', '')) ?>"
                   style="width:100px" aria-label="Price for <?= e($p['name']) ?>">
            <button class="btn btn-ghost btn-sm">Save £</button>
          </form>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="product_toggle">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn-dark btn-sm">
              <?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

  <?php else: /* inbox */
      $msgs = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
      $news = $pdo->query('SELECT * FROM newsletter ORDER BY created_at DESC')->fetchAll(); ?>
    <div class="cart-layout">
      <div>
        <h2>Contact messages (<?= count($msgs) ?>)</h2>
        <?php if (!$msgs): ?><p class="muted">Inbox empty.</p><?php endif; ?>
        <?php foreach ($msgs as $m): ?>
          <div class="order-row">
            <header>
              <strong><?= e($m['name']) ?> — <?= e($m['subject']) ?></strong>
              <span class="muted"><?= e($m['created_at']) ?></span>
            </header>
            <p class="muted"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p>
            <p><?= e($m['body']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <aside class="summary">
        <h2 style="font-size:1.2rem">Newsletter (<?= count($news) ?>)</h2>
        <ul class="foot-links">
          <?php foreach ($news as $n): ?>
            <li><?= e($n['email']) ?></li>
          <?php endforeach; ?>
          <?php if (!$news): ?><li class="muted">No signups yet.</li><?php endif; ?>
        </ul>
      </aside>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
