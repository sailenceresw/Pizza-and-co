</main>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <h3><?= e(BRAND_NAME) ?> <span class="muted"><?= e(BRAND_TOWN) ?></span></h3>
      <p class="muted"><?= e(BRAND_TAGLINE) ?></p>
      <p class="muted">
        <?= e(BRAND_ADDRESS) ?><br>
        <?= e(BRAND_POSTCODE) ?> · <a href="tel:<?= e(BRAND_PHONE) ?>"><?= e(BRAND_PHONE) ?></a>
      </p>
    </div>

    <div>
      <h4>Opening Hours</h4>
      <ul class="hours-list">
        <?php foreach ($GLOBALS['OPENING_HOURS'] as $day => [$o, $c]):
            $fmt = static function (float $h): string {
                $h = $h >= 24 ? $h - 24 : $h;
                $hh = str_pad((string) (int) $h, 2, '0', STR_PAD_LEFT);
                $mm = str_pad((string) (int) round(($h - (int) $h) * 60), 2, '0', STR_PAD_LEFT);
                return "$hh:$mm";
            }; ?>
          <li><span><?= e($day) ?></span><span><?= $fmt($o) ?>–<?= $fmt($c) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div>
      <h4>Explore</h4>
      <ul class="foot-links">
        <li><a href="menu.php">Full Menu</a></li>
        <li><a href="pizza-builder.php">Build a Pizza</a></li>
        <li><a href="index.php#deals">Deals &amp; Bundles</a></li>
        <li><a href="about.php">Our Story</a></li>
        <li><a href="contact.php">Contact &amp; Find Us</a></li>
        <li><a href="accessibility.php">Accessibility Statement</a></li>
      </ul>
    </div>

    <div>
      <h4>Newsletter</h4>
      <p class="muted">Deals &amp; secret menu drops. No spam.</p>
      <form class="news-form" id="newsletterForm" autocomplete="on">
        <label class="visually-hidden" for="newsEmail">Email address</label>
        <input type="email" id="newsEmail" name="email" placeholder="you@email.com" required>
        <button class="btn btn-primary" type="submit">Join</button>
      </form>
      <p class="news-result" id="newsResult" role="status" aria-live="polite"></p>
    </div>
  </div>

  <div class="wrap footer-bottom">
    <p>© <?= date('Y') ?> <?= e(BRAND_NAME) ?> <?= e(BRAND_TOWN) ?> — student coursework
       project. Inspired by the real Pizza &amp; Co Thornaby brief; all code &amp;
       artwork original.</p>
    <p class="badges">
      <a href="https://validator.w3.org/" rel="noopener">HTML5 ✔</a>
      <a href="https://jigsaw.w3.org/css-validator/" rel="noopener">CSS3 ✔</a>
      <a href="https://wave.webaim.org/" rel="noopener">WAVE ♿</a>
    </p>
  </div>
</footer>

<script src="assets/js/main.js" defer></script>
<script src="assets/js/cart.js" defer></script>
</body>
</html>
