<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$crusts = $pdo->query('SELECT * FROM crusts ORDER BY id')->fetchAll();
$toppings = $pdo->query('SELECT * FROM toppings ORDER BY kind, name')->fetchAll();

$grouped = [];
foreach ($toppings as $t) {
    $grouped[$t['kind']][] = $t;
}

$BUILDER_BASE = 6.50;
$sizes = [
    ['9" Personal', 0.0],
    ['12" Medium', 3.0],
    ['15" Large', 6.0],
    ['18" Sharer', 9.5],
];
$colours = ['meat' => '#9B2C2C', 'veg' => '#2E7D32', 'cheese' => '#E9B872'];

$pageTitle = 'Pizza Builder';
$pageClass = 'builder-page';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <div class="section-head" style="text-align:left;max-width:none">
    <h1>Build your own pizza 🍕</h1>
    <p class="muted">Pick a size, base, sauce and up to 8 toppings — watch it
      come together and the price update live.</p>
  </div>

  <div class="builder" id="builder" data-base="<?= $BUILDER_BASE ?>">
    <div>
      <div class="builder-stage">
        <div class="builder-base sauce-tomato" id="builderBase"></div>
      </div>
      <p class="center muted" id="builderSummary" style="margin-top:1.2rem"></p>
    </div>

    <div class="builder-panel stack">
      <div>
        <h3>Size</h3>
        <div class="opt-pills">
          <?php foreach ($sizes as $i => $s): ?>
            <label class="opt-pill <?= $i === 0 ? 'on' : '' ?>">
              <input type="radio" name="b_size" value="<?= e($s[0]) ?>"
                     data-price="<?= $s[1] ?>" data-label="<?= e($s[0]) ?>"
                     <?= $i === 0 ? 'checked' : '' ?>
                     style="position:absolute;opacity:0">
              <?= e($s[0]) ?>
              <?= $s[1] > 0 ? '+' . money($s[1]) : '' ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <h3>Base / crust</h3>
        <div class="opt-pills">
          <?php foreach ($crusts as $i => $c): ?>
            <label class="opt-pill <?= $i === 0 ? 'on' : '' ?>">
              <input type="radio" name="b_crust" value="<?= e($c['name']) ?>"
                     data-price="<?= e((string) $c['price_delta']) ?>"
                     data-label="<?= e($c['name']) ?>"
                     <?= $i === 0 ? 'checked' : '' ?>
                     style="position:absolute;opacity:0">
              <?= e($c['name']) ?>
              <?= $c['price_delta'] > 0 ? '+' . money((float) $c['price_delta']) : '' ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <h3>Sauce</h3>
        <div class="opt-pills">
          <button type="button" class="opt-pill on" data-sauce="tomato">Tomato</button>
          <button type="button" class="opt-pill" data-sauce="bbq">BBQ</button>
          <button type="button" class="opt-pill" data-sauce="white">Garlic White</button>
        </div>
      </div>

      <?php foreach (['cheese' => 'Cheeses', 'meat' => 'Meats', 'veg' => 'Veg'] as $k => $title):
          if (empty($grouped[$k])) continue; ?>
        <div>
          <h3><?= e($title) ?></h3>
          <div class="opt-pills">
            <?php foreach ($grouped[$k] as $t): ?>
              <span class="opt-pill" data-topping="<?= e($t['name']) ?>"
                    data-price="<?= e((string) $t['price']) ?>"
                    data-color="<?= e($colours[$k] ?? '#9B2C2C') ?>">
                <?= e($t['name']) ?> +<?= money((float) $t['price']) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <hr class="soft" style="margin:.5rem 0">
      <div class="card-foot">
        <span class="builder-total" id="builderTotal">£<?= number_format($BUILDER_BASE, 2) ?></span>
        <button class="btn btn-primary" id="builderAdd">Add custom pizza to cart</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/builder.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
