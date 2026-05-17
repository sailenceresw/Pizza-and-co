<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$u = current_user();
$tab = $_GET['tab'] ?? 'orders';
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = $_POST['form'] ?? '';

    if ($form === 'profile') {
        $pdo->prepare('UPDATE users SET name=?, phone=? WHERE id=?')
            ->execute([trim($_POST['name'] ?? ''), trim($_POST['phone'] ?? ''), $u['id']]);
        flash('Profile updated.');
        redirect('account.php?tab=profile');
    }

    if ($form === 'password') {
        if (!password_verify($_POST['current'] ?? '', $u['password_hash'])) {
            $msg = 'Current password is wrong.';
        } elseif (strlen($_POST['new'] ?? '') < 6) {
            $msg = 'New password must be 6+ characters.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                ->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]);
            flash('Password changed.');
            redirect('account.php?tab=profile');
        }
        $tab = 'profile';
    }

    if ($form === 'address') {
        $pdo->prepare(
            'INSERT INTO addresses (user_id,label,line1,line2,city,postcode)
             VALUES (?,?,?,?,?,?)'
        )->execute([
            $u['id'], trim($_POST['label'] ?? 'Home'), trim($_POST['line1'] ?? ''),
            trim($_POST['line2'] ?? ''), trim($_POST['city'] ?? ''),
            strtoupper(trim($_POST['postcode'] ?? '')),
        ]);
        flash('Address saved.');
        redirect('account.php?tab=addresses');
    }

    if ($form === 'address_delete') {
        $pdo->prepare('DELETE FROM addresses WHERE id=? AND user_id=?')
            ->execute([(int) $_POST['id'], $u['id']]);
        redirect('account.php?tab=addresses');
    }

    if ($form === 'reorder') {
        $oid = (int) ($_POST['order_id'] ?? 0);
        $own = $pdo->prepare('SELECT 1 FROM orders WHERE id=? AND user_id=?');
        $own->execute([$oid, $u['id']]);
        if ($own->fetchColumn()) {
            $its = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');
            $its->execute([$oid]);
            foreach ($its->fetchAll() as $it) {
                cart_add(
                    $it['name'], (float) $it['unit_price'],
                    json_decode($it['options_json'], true) ?: [], (int) $it['qty']
                );
            }
            flash('Items added back to your cart.');
            redirect('cart.php');
        }
    }
}

$orders = $pdo->prepare(
    'SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC'
);
$orders->execute([$u['id']]);
$orders = $orders->fetchAll();

$addresses = $pdo->prepare('SELECT * FROM addresses WHERE user_id=? ORDER BY id');
$addresses->execute([$u['id']]);
$addresses = $addresses->fetchAll();

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id=?');

$badge = static fn(string $s): string => 's-' . str_replace(' ', '', strtolower(
    str_contains($s, 'out') ? 'out' : $s
));

$pageTitle = 'My Account';
$pageClass = 'account';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <h1>Hi, <?= e(explode(' ', $u['name'])[0]) ?> 👋</h1>
  <p class="muted">Manage your orders, addresses and details.
    <a href="logout.php">Sign out</a></p>

  <div class="steps" role="tablist" style="margin:1.5rem 0 2rem">
    <a class="btn btn-<?= $tab === 'orders' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=orders">Orders (<?= count($orders) ?>)</a>
    <a class="btn btn-<?= $tab === 'addresses' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=addresses">Addresses</a>
    <a class="btn btn-<?= $tab === 'favourites' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=favourites">Favourites</a>
    <a class="btn btn-<?= $tab === 'profile' ? 'primary' : 'ghost' ?> btn-sm"
       href="?tab=profile">Profile</a>
  </div>

  <?php if ($msg): ?><p class="error-text"><?= e($msg) ?></p><?php endif; ?>

  <?php if ($tab === 'orders'): ?>
    <?php if (!$orders): ?>
      <div class="empty-state">
        <div class="big">🍕</div><h2>No orders yet</h2>
        <a class="btn btn-primary" href="menu.php">Place your first order</a>
      </div>
    <?php endif; ?>
    <?php foreach ($orders as $o):
        $itemStmt->execute([$o['id']]);
        $its = $itemStmt->fetchAll(); ?>
      <div class="order-row">
        <header>
          <div>
            <strong>Order #<?= (int) $o['id'] ?></strong>
            <span class="muted"> · <?= e($o['created_at']) ?>
              · <?= e(ucfirst($o['order_type'])) ?></span>
          </div>
          <span class="status-badge <?= $badge($o['status']) ?>">
            <?= e($o['status']) ?></span>
        </header>
        <ul style="margin:.8rem 0">
          <?php foreach ($its as $it): ?>
            <li><?= (int) $it['qty'] ?>× <?= e($it['name']) ?>
              <span class="muted"><?= e(options_summary(
                json_decode($it['options_json'], true) ?: [])) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <div class="card-foot">
          <strong><?= money((float) $o['total']) ?></strong>
          <span class="flow">
            <a class="btn btn-ghost btn-sm"
               href="order-confirmation.php?id=<?= (int) $o['id'] ?>">Details</a>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="reorder">
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button class="btn btn-primary btn-sm">Reorder</button>
            </form>
          </span>
        </div>
      </div>
    <?php endforeach; ?>

  <?php elseif ($tab === 'addresses'): ?>
    <div class="cart-layout">
      <div>
        <?php if (!$addresses): ?>
          <p class="muted">No saved addresses yet.</p>
        <?php endif; ?>
        <?php foreach ($addresses as $a): ?>
          <div class="cart-line">
            <div>
              <div class="name"><?= e($a['label']) ?></div>
              <div class="opts"><?= e($a['line1']) ?>
                <?= $a['line2'] ? ', ' . e($a['line2']) : '' ?>,
                <?= e($a['city']) ?>, <?= e($a['postcode']) ?></div>
            </div>
            <span></span>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="form" value="address_delete">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button class="btn btn-ghost btn-sm">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
      <aside class="summary">
        <h2 style="font-size:1.2rem">Add an address</h2>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="address">
          <div class="field"><label>Label</label>
            <input name="label" value="Home"></div>
          <div class="field"><label>Address line 1</label>
            <input name="line1" required></div>
          <div class="field"><label>Address line 2</label>
            <input name="line2"></div>
          <div class="field"><label>Town/City</label>
            <input name="city" value="Thornaby" required></div>
          <div class="field"><label>Postcode</label>
            <input name="postcode" placeholder="TS17 0EJ" required></div>
          <button class="btn btn-primary btn-block">Save address</button>
        </form>
      </aside>
    </div>

  <?php elseif ($tab === 'favourites'): ?>
    <p class="muted">Tap the heart on any dish to save it here. (Demo: showing
      our recommended picks.)</p>
    <?php
    $favs = $pdo->query(
        'SELECT p.*, c.icon FROM products p JOIN categories c ON c.id=p.category_id
         WHERE p.is_active=1 ORDER BY RANDOM() LIMIT 3'
    )->fetchAll();
    require_once __DIR__ . '/includes/media.php'; ?>
    <div class="grid cols-3">
      <?php foreach ($favs as $f): ?>
        <article class="card">
          <a href="product.php?slug=<?= e($f['slug']) ?>"><?= food_art($f['icon'], '') ?></a>
          <div class="card-body">
            <h3><a href="product.php?slug=<?= e($f['slug']) ?>"><?= e($f['name']) ?></a></h3>
            <div class="card-foot">
              <span class="price"><?= money((float) $f['base_price']) ?></span>
              <a class="btn btn-primary btn-sm"
                 href="product.php?slug=<?= e($f['slug']) ?>">Order</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  <?php else: /* profile */ ?>
    <div class="cart-layout">
      <form method="post" class="form-card form-wide" style="max-width:none">
        <h2 style="font-size:1.2rem">Your details</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="profile">
        <div class="field"><label for="n">Name</label>
          <input id="n" name="name" value="<?= e($u['name']) ?>" required></div>
        <div class="field"><label for="ph">Phone</label>
          <input id="ph" name="phone" value="<?= e($u['phone']) ?>"></div>
        <div class="field"><label>Email</label>
          <input value="<?= e($u['email']) ?>" disabled></div>
        <button class="btn btn-primary">Save changes</button>
      </form>

      <form method="post" class="summary">
        <h2 style="font-size:1.2rem">Change password</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">
        <div class="field"><label for="cp">Current password</label>
          <input type="password" id="cp" name="current" required></div>
        <div class="field"><label for="np">New password</label>
          <input type="password" id="np" name="new" required minlength="6"></div>
        <button class="btn btn-dark btn-block">Update password</button>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
