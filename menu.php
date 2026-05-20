<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/media.php';

$pdo = db();
$cats = $pdo->query('SELECT * FROM categories ORDER BY sort')->fetchAll();
$products = $pdo->query(
    'SELECT p.*, c.slug AS cat_slug, c.name AS cat_name, c.icon
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 ORDER BY c.sort, p.sort'
)->fetchAll();

$grouped = [];
foreach ($products as $p) {
    $grouped[$p['cat_slug']][] = $p;
}

$pageTitle = 'Full Menu';
$pageClass = 'menu';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap section">
  <div class="section-head" style="text-align:left;max-width:none">
    <h1>The Menu</h1>
    <p class="muted">Filter by diet or spice, or just search. Tap an item to
      customise sizes, crusts &amp; dips.</p>
  </div>

  <div class="menu-layout">
    <aside class="menu-side" aria-label="Menu filters">
      <h3>Categories</h3>
      <ul class="cat-nav">
        <?php foreach ($cats as $c): ?>
          <li><a href="#cat-<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>

      <h3>Dietary</h3>
      <div class="filter-group">
        <label><input type="checkbox" data-filter-diet="veggie"> Vegetarian</label>
        <label><input type="checkbox" data-filter-diet="vegan"> Vegan</label>
        <label><input type="checkbox" data-filter-diet="gf"> Gluten-free</label>
      </div>

      <h3>Max spice: <span id="spiceVal" class="pill-info">Any</span></h3>
      <div class="filter-group">
        <input type="range" id="filterSpice" min="0" max="3" value="3" step="1"
               aria-label="Maximum spice level">
      </div>
      <p class="muted" id="resultCount"><?= count($products) ?> items</p>
    </aside>

    <div>
      <div class="search-row">
        <label class="visually-hidden" for="menuSearch">Search the menu</label>
        <input type="search" id="menuSearch" placeholder="🔍 Search dishes…">
      </div>

      <p id="noResults" hidden class="note">No dishes match those filters —
        try loosening them.</p>

      <?php foreach ($cats as $c):
          if (empty($grouped[$c['slug']])) continue; ?>
        <section class="cat-block" id="cat-<?= e($c['slug']) ?>">
          <h2><?= e($c['name']) ?></h2>
          <p class="muted"><?= e($c['blurb']) ?></p>
          <div class="grid cols-3" style="margin-top:1.2rem">
            <?php foreach ($grouped[$c['slug']] as $p): ?>
              <article class="card" data-product
                       data-name="<?= e($p['name']) ?>"
                       data-veggie="<?= (int) $p['is_veggie'] ?>"
                       data-vegan="<?= (int) $p['is_vegan'] ?>"
                       data-gf="<?= (int) $p['is_gluten_free'] ?>"
                       data-spice="<?= (int) $p['spice_level'] ?>">
                <a href="product.php?slug=<?= e($p['slug']) ?>"
                   aria-label="View <?= e($p['name']) ?>">
                  <?= food_art($p['icon'], '', false) ?>
                </a>
                <div class="card-body">
                  <div class="tag-row">
                    <?php if ($p['is_vegan']): ?><span class="tag vegan">Vegan</span>
                    <?php elseif ($p['is_veggie']): ?><span class="tag veg">Veggie</span><?php endif; ?>
                    <?php if ($p['is_gluten_free']): ?><span class="tag gf">GF</span><?php endif; ?>
                    <?php if ($p['spice_level'] >= 1): ?>
                      <span class="tag spice"><?= str_repeat('🌶', (int) $p['spice_level']) ?></span>
                    <?php endif; ?>
                  </div>
                  <h3><a href="product.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
                  <p class="desc"><?= e($p['description']) ?></p>
                  <div class="card-foot">
                    <span class="price">
                      <?= $p['has_sizes'] ? 'from ' : '' ?><?= money((float) $p['base_price']) ?>
                    </span>
                    <?php if ($p['has_sizes']): ?>
                      <a class="btn btn-primary btn-sm"
                         href="product.php?slug=<?= e($p['slug']) ?>">Choose</a>
                    <?php else: ?>
                      <button class="btn btn-primary btn-sm" data-quick-add
                              data-name="<?= e($p['name']) ?>"
                              data-price="<?= e((string) $p['base_price']) ?>"
                              data-options='{}'>Add +</button>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="assets/js/filters.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
