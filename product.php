<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';

$pdo = db();
$slug = (string) ($_GET['slug'] ?? '');
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.icon
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1'
);
$stmt->execute([$slug]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap section"><h1>Dish not found</h1>'
        . '<p><a class="btn btn-primary" href="menu.php">Back to menu</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$sizes = $pdo->prepare('SELECT * FROM product_sizes WHERE product_id = ? ORDER BY id');
$sizes->execute([$p['id']]);
$sizes = $sizes->fetchAll();

$isPizza = $p['cat_slug'] === 'pizzas';
$crusts = $isPizza ? $pdo->query('SELECT * FROM crusts ORDER BY id')->fetchAll() : [];

// --- Review submit (logged-in) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'review') {
    csrf_check();
    require_login();
    $u = current_user();
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($body !== '') {
        $ins = $pdo->prepare(
            'INSERT INTO reviews (product_id,user_id,name,rating,body)
             VALUES (?,?,?,?,?)'
        );
        $ins->execute([$p['id'], $u['id'], $u['name'], $rating, $body]);
        flash('Thanks for the review!');
    }
    redirect('product.php?slug=' . urlencode($slug) . '#reviews');
}

$reviews = $pdo->prepare(
    'SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC'
);
$reviews->execute([$p['id']]);
$reviews = $reviews->fetchAll();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$related = $pdo->prepare(
    'SELECT p.*, c.icon FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 LIMIT 3'
);
$related->execute([$p['category_id'], $p['id']]);
$related = $related->fetchAll();

$pageTitle = $p['name'];
$pageClass = 'product';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap section">
  <p class="muted"><a href="menu.php">Menu</a> ›
    <a href="menu.php#cat-<?= e($p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a> ›
    <?= e($p['name']) ?></p>

  <div class="product-layout">
    <div class="product-media" data-lightbox>
      <?= food_art($p['icon'], $p['name'], true, 'food-art') ?>
    </div>

    <div>
      <div class="tag-row" style="margin-bottom:.6rem">
        <?php if ($p['is_vegan']): ?><span class="tag vegan">Vegan</span>
        <?php elseif ($p['is_veggie']): ?><span class="tag veg">Vegetarian</span><?php endif; ?>
        <?php if ($p['is_gluten_free']): ?><span class="tag gf">Gluten-free option</span><?php endif; ?>
        <?php if ($p['spice_level'] >= 1): ?>
          <span class="tag spice">Spice <?= str_repeat('🌶', (int) $p['spice_level']) ?></span>
        <?php endif; ?>
      </div>

      <h1><?= e($p['name']) ?></h1>
      <?php if ($reviews): ?>
        <p><span class="stars" aria-hidden="true">
          <?= str_repeat('★', (int) round($avg)) . str_repeat('☆', 5 - (int) round($avg)) ?>
        </span>
        <span class="muted"><?= number_format($avg, 1) ?> · <?= count($reviews) ?> reviews</span></p>
      <?php endif; ?>
      <p><?= e($p['description']) ?></p>

      <form data-add-cart class="stack" autocomplete="off">
        <input type="hidden" name="name" value="<?= e($p['name']) ?>">
        <input type="hidden" name="unit_price" id="unitPrice"
               value="<?= e((string) $p['base_price']) ?>">

        <?php if ($sizes): ?>
          <fieldset style="border:none;padding:0;margin:0">
            <legend><strong>Choose a size</strong></legend>
            <ul class="opt-list">
              <?php foreach ($sizes as $i => $s):
                  $sp = (float) $p['base_price'] + (float) $s['price_delta']; ?>
                <li><label>
                  <span><input type="radio" name="size" required
                    value="<?= e($s['label']) ?>" data-delta="<?= e((string) $s['price_delta']) ?>"
                    <?= $i === 0 ? 'checked' : '' ?>>
                    <?= e($s['label']) ?></span>
                  <strong><?= money($sp) ?></strong>
                </label></li>
              <?php endforeach; ?>
            </ul>
          </fieldset>
        <?php endif; ?>

        <?php if ($isPizza && $crusts): ?>
          <fieldset style="border:none;padding:0;margin:0">
            <legend><strong>Crust</strong></legend>
            <ul class="opt-list">
              <?php foreach ($crusts as $i => $cr): ?>
                <li><label>
                  <span><input type="radio" name="crust"
                    value="<?= e($cr['name']) ?>" data-delta="<?= e((string) $cr['price_delta']) ?>"
                    <?= $i === 0 ? 'checked' : '' ?>>
                    <?= e($cr['name']) ?></span>
                  <strong><?= $cr['price_delta'] > 0 ? '+' . money((float) $cr['price_delta']) : 'inc.' ?></strong>
                </label></li>
              <?php endforeach; ?>
            </ul>
          </fieldset>
        <?php endif; ?>

        <div class="field">
          <label for="dip">Add a dip (£0.60)</label>
          <select name="dip" id="dip" data-delta-select="0.60">
            <option value="">No dip</option>
            <option>Garlic &amp; Herb</option>
            <option>BBQ</option>
            <option>Sweet Chilli</option>
            <option>Blue Cheese</option>
            <option>Vegan Garlic</option>
          </select>
        </div>

        <div class="field">
          <label for="notes">Notes for the kitchen (optional)</label>
          <input type="text" name="notes" id="notes"
                 placeholder="e.g. light on the cheese, extra crispy">
        </div>

        <div class="addbar">
          <div class="qty-stepper" aria-label="Quantity">
            <button type="button" data-step="-1" aria-label="Decrease">−</button>
            <input type="text" name="qty" id="qty" value="1" readonly aria-live="polite">
            <button type="button" data-step="1" aria-label="Increase">+</button>
          </div>
          <button type="submit" class="btn btn-primary">
            Add to cart — <span id="livePrice"><?= money((float) $p['base_price']) ?></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Related -->
  <?php if ($related): ?>
    <hr class="soft">
    <h2>You might also like</h2>
    <div class="grid cols-3">
      <?php foreach ($related as $r): ?>
        <article class="card">
          <a href="product.php?slug=<?= e($r['slug']) ?>"><?= food_art($r['icon'], '') ?></a>
          <div class="card-body">
            <h3><a href="product.php?slug=<?= e($r['slug']) ?>"><?= e($r['name']) ?></a></h3>
            <div class="card-foot">
              <span class="price"><?= money((float) $r['base_price']) ?></span>
              <a class="btn btn-primary btn-sm" href="product.php?slug=<?= e($r['slug']) ?>">View</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Reviews -->
  <div class="reviews" id="reviews">
    <h2>Reviews</h2>
    <?php if (!$reviews): ?>
      <p class="muted">No reviews yet — be the first.</p>
    <?php endif; ?>
    <?php foreach ($reviews as $rv): ?>
      <div class="review">
        <strong><?= e($rv['name']) ?></strong>
        <span class="stars" aria-label="<?= (int) $rv['rating'] ?> out of 5">
          <?= str_repeat('★', (int) $rv['rating']) . str_repeat('☆', 5 - (int) $rv['rating']) ?>
        </span>
        <p style="margin:.4rem 0 0"><?= e($rv['body']) ?></p>
      </div>
    <?php endforeach; ?>

    <?php if (is_logged_in()): ?>
      <form method="post" class="form-card form-wide" style="margin:1.5rem 0 0">
        <h3>Leave a review</h3>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="review">
        <div class="field">
          <label for="rating">Rating</label>
          <select name="rating" id="rating">
            <option value="5">★★★★★ Loved it</option>
            <option value="4">★★★★ Great</option>
            <option value="3">★★★ Good</option>
            <option value="2">★★ Meh</option>
            <option value="1">★ Not for me</option>
          </select>
        </div>
        <div class="field">
          <label for="body">Your review</label>
          <textarea name="body" id="body" rows="3" required></textarea>
        </div>
        <button class="btn btn-primary">Post review</button>
      </form>
    <?php else: ?>
      <p class="note"><a href="login.php">Sign in</a> to leave a review.</p>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var base = <?= json_encode((float) $p['base_price']) ?>;
  var form = document.querySelector('form[data-add-cart]');
  if (!form) return;
  var qty = document.getElementById('qty');
  var live = document.getElementById('livePrice');
  var hidden = document.getElementById('unitPrice');

  function unit() {
    var u = base;
    var size = form.querySelector('input[name=size]:checked');
    if (size) u += parseFloat(size.dataset.delta || 0);
    var crust = form.querySelector('input[name=crust]:checked');
    if (crust) u += parseFloat(crust.dataset.delta || 0);
    var dip = form.querySelector('#dip');
    if (dip && dip.value) u += 0.60;
    return u;
  }
  function refresh() {
    var u = unit();
    hidden.value = u.toFixed(2);
    var total = u * parseInt(qty.value, 10);
    live.textContent = '£' + total.toFixed(2);
  }
  form.addEventListener('change', refresh);
  form.querySelectorAll('[data-step]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = parseInt(qty.value, 10) + parseInt(b.dataset.step, 10);
      qty.value = Math.max(1, Math.min(20, v));
      refresh();
    });
  });
  refresh();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
