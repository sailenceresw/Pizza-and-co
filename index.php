<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/media.php';

$pdo = db();
$deals = $pdo->query('SELECT * FROM deals ORDER BY id')->fetchAll();
$cats = $pdo->query('SELECT * FROM categories ORDER BY sort')->fetchAll();
$popular = $pdo->query(
    'SELECT p.*, c.icon FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 ORDER BY p.id LIMIT 6'
)->fetchAll();
$st = kitchen_status();

$pageTitle = 'Order Takeaway';
$pageClass = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div>
      <span class="kicker">Thornaby · TS17 · £0 platform fees</span>
      <h1>Proper takeaway,<br>ordered direct.</h1>
      <p class="lead">Stone-baked pizzas, legendary parmos, kebabs, burgers &amp;
        fusion pasta. Skip the apps — order straight from us and pay less.</p>

      <div class="hero-cta">
        <button class="btn btn-mustard" data-open-modal="orderModal">Start your order</button>
        <a class="btn btn-ghost" href="pizza-builder.php"
           style="color:#fff;border-color:rgba(255,255,255,.4)">Build a pizza →</a>
      </div>

      <div style="margin-top:1.8rem;max-width:380px">
        <label for="heroPostcode" style="color:#fff">Are we in your area?</label>
        <div class="pc-row">
          <input type="text" id="heroPostcode" placeholder="Enter postcode (e.g. TS17 0EJ)"
                 autocomplete="postal-code">
          <button class="btn btn-dark" id="heroPcCheck">Check</button>
        </div>
        <p class="pc-result" id="heroPcResult" role="status" aria-live="polite"
           style="color:#fff"></p>
      </div>
    </div>

    <div class="hero-art">
      <div class="steam-stack" aria-hidden="true"><i></i><i></i><i></i></div>
      <div class="hero-pizza" aria-hidden="true"></div>
    </div>
  </div>
</section>

<div class="trust-bar">
  <div class="wrap">
    <span>🛵 Delivery &amp; collection</span>
    <span>🕑 <?= e($st['label']) ?> · <?= e($st['next']) ?></span>
    <span>💷 Free delivery over <?= money(FREE_DELIVERY_THRESHOLD) ?></span>
    <span>⭐ Loved by Teesside</span>
  </div>
</div>

<section class="section" id="deals">
  <div class="wrap">
    <div class="section-head">
      <h2>This week’s deals</h2>
      <p class="muted">Bundles built to feed the crew for less.</p>
    </div>
    <div class="grid cols-4">
      <?php foreach ($deals as $d): ?>
        <article class="card deal-card">
          <span class="badge"><?= e($d['badge']) ?></span>
          <?= food_art('party', '', true) ?>
          <div class="card-body">
            <h3><?= e($d['title']) ?></h3>
            <p class="desc"><?= e($d['description']) ?></p>
            <div class="card-foot">
              <span class="price"><?= money((float) $d['price']) ?></span>
              <button class="btn btn-primary btn-sm" data-quick-add
                      data-name="<?= e($d['title']) ?>"
                      data-price="<?= e((string) $d['price']) ?>"
                      data-options='{"type":"Party deal"}'>Add +</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="background:var(--surface)">
  <div class="wrap">
    <div class="section-head">
      <h2>Explore the menu</h2>
      <p class="muted">11 categories. Something for every craving.</p>
    </div>
    <div class="category-tiles">
      <?php foreach ($cats as $c): ?>
        <a class="cat-tile" href="menu.php#cat-<?= e($c['slug']) ?>">
          <?= food_art($c['icon'], '') ?>
          <span><?= e($c['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2>People’s favourites</h2>
      <p class="muted">The orders we can’t make fast enough.</p>
    </div>
    <div class="grid cols-3">
      <?php foreach ($popular as $p): ?>
        <article class="card">
          <a href="product.php?slug=<?= e($p['slug']) ?>" aria-label="<?= e($p['name']) ?>">
            <?= food_art($p['icon'], '', true) ?>
          </a>
          <div class="card-body">
            <div class="tag-row">
              <?php if ($p['is_vegan']): ?><span class="tag vegan">Vegan</span>
              <?php elseif ($p['is_veggie']): ?><span class="tag veg">Veggie</span><?php endif; ?>
              <?php if ($p['spice_level'] >= 2): ?><span class="tag spice">🌶 Spicy</span><?php endif; ?>
            </div>
            <h3><a href="product.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
            <p class="desc"><?= e($p['description']) ?></p>
            <div class="card-foot">
              <span class="price"><?= money((float) $p['base_price']) ?></span>
              <a class="btn btn-primary btn-sm" href="product.php?slug=<?= e($p['slug']) ?>">Order</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="center" style="margin-top:2rem">
      <a class="btn btn-dark" href="menu.php">See the full menu</a>
    </p>
  </div>
</section>

<section class="section" style="background:var(--surface)">
  <div class="wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;align-items:center">
    <div>
      <h2>Why order direct?</h2>
      <p>Just Eat and Uber Eats charge restaurants up to 30%. Order straight
        from us and that saving stays in the food — bigger toppings, better
        deals, and a kitchen that knows your name.</p>
      <ul class="stack" style="padding-left:1.1rem">
        <li><strong>£0 platform fees</strong> — what you see is what you pay.</li>
        <li><strong>Faster</strong> — straight to our kitchen screen.</li>
        <li><strong>Rewarded</strong> — save favourites &amp; reorder in a tap.</li>
      </ul>
      <a class="btn btn-primary" href="register.php">Create a free account</a>
    </div>
    <div class="hero-art" style="background:none">
      <?= food_art('pizza', 'Stone-baked daily', true, 'food-art') ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
