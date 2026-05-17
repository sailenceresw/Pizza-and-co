<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media.php';

$pageTitle = $pageTitle ?? 'Order Takeaway';
$pageClass = $pageClass ?? '';
$status = kitchen_status();
$u = current_user();
$flash = flash();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e(BRAND_NAME . ' ' . BRAND_TOWN . ' — ' . BRAND_TAGLINE) ?>">
<title><?= e($pageTitle) ?> · <?= e(BRAND_NAME) ?> <?= e(BRAND_TOWN) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍕</text></svg>">
<script>
  // Set theme before paint to avoid flash of wrong theme.
  (function () {
    try {
      var t = localStorage.getItem('theme');
      if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) {}
  })();
</script>
</head>
<body class="<?= e($pageClass) ?>">
<a class="skip-link" href="#main">Skip to main content</a>

<header class="site-header" id="top">
  <div class="wrap header-inner">
    <a class="brand" href="index.php" aria-label="<?= e(BRAND_NAME) ?> home">
      <span class="brand-mark" aria-hidden="true">🍕</span>
      <span class="brand-text">
        <strong><?= e(BRAND_NAME) ?></strong>
        <small><?= e(BRAND_TOWN) ?></small>
      </span>
    </a>

    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav"
            aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <nav id="primary-nav" class="primary-nav" aria-label="Primary">
      <a href="menu.php">Menu</a>
      <a href="index.php#deals">Deals</a>
      <a href="pizza-builder.php">Pizza Builder</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <?php if (is_admin()): ?>
        <a href="admin.php" style="color:var(--tomato)">Admin</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <span class="status-pill <?= $status['open'] ? 'is-open' : 'is-closed' ?>"
            title="<?= e($status['next']) ?>">
        <span class="dot" aria-hidden="true"></span>
        <?= e($status['label']) ?>
      </span>

      <button id="themeToggle" class="icon-btn" aria-label="Toggle dark mode"
              title="Toggle dark mode">
        <span class="theme-sun" aria-hidden="true">☀</span>
        <span class="theme-moon" aria-hidden="true">☾</span>
      </button>

      <a class="icon-btn" href="<?= is_logged_in() ? 'account.php' : 'login.php' ?>"
         aria-label="<?= is_logged_in() ? 'My account' : 'Sign in' ?>"
         title="<?= is_logged_in() ? e($u['name']) : 'Sign in' ?>">
        <span aria-hidden="true">👤</span>
      </a>

      <a class="icon-btn cart-link" href="cart.php" aria-label="View cart">
        <span aria-hidden="true">🛒</span>
        <span class="cart-badge" id="cartBadge"
              data-count="<?= cart_count() ?>"
              <?= cart_count() ? '' : 'hidden' ?>><?= cart_count() ?></span>
      </a>

      <button class="btn btn-primary order-cta" data-open-modal="orderModal">
        Start Order
      </button>
    </div>
  </div>
</header>

<?php if ($flash): ?>
  <div class="flash" role="status"><div class="wrap"><?= e($flash) ?></div></div>
<?php endif; ?>

<!-- Order mode modal: delivery / collection + postcode checker -->
<div class="modal" id="orderModal" aria-hidden="true">
  <div class="modal-overlay" data-close-modal></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
    <button class="modal-x" data-close-modal aria-label="Close">×</button>
    <h2 id="orderModalTitle">How do you want it?</h2>
    <div class="toggle-group" role="tablist" aria-label="Order type">
      <button class="toggle is-active" data-order-type="delivery" role="tab" aria-selected="true">🛵 Delivery</button>
      <button class="toggle" data-order-type="collection" role="tab" aria-selected="false">🏬 Collection</button>
    </div>

    <div data-mode="delivery">
      <label for="modalPostcode">Enter your postcode</label>
      <div class="pc-row">
        <input type="text" id="modalPostcode" placeholder="e.g. TS17 0EJ"
               autocomplete="postal-code" inputmode="text">
        <button class="btn btn-dark" id="modalPcCheck">Check</button>
      </div>
      <p class="pc-result" id="modalPcResult" role="status" aria-live="polite"></p>
    </div>

    <div data-mode="collection" hidden>
      <p>Collect from <strong><?= e(BRAND_ADDRESS) ?></strong>.</p>
      <p class="muted"><?= e($status['label']) ?> · <?= e($status['next']) ?></p>
    </div>

    <a href="menu.php" class="btn btn-primary btn-block">Browse the menu →</a>
  </div>
</div>

<main id="main" class="page <?= e($pageClass) ?>">
