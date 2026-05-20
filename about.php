<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/media.php';
$pageTitle = 'Our Story';
$pageClass = 'about';
require __DIR__ . '/includes/header.php';
?>
<section class="hero" style="padding:0">
  <div class="wrap" style="grid-template-columns:1fr 1fr">
    <div>
      <span class="kicker">Since the brief · Thornaby born</span>
      <h1>Made for Teesside,<br>not the apps.</h1>
      <p class="lead">We started with one belief: great takeaway shouldn’t cost
        you a 30% delivery-app tax. So we built our own.</p>
    </div>
    <div class="hero-art" style="background:none">
      <?= food_art('parmo', 'The Teesside parmo', true) ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:760px">
    <h2>Our story</h2>
    <p>Pizza &amp; Co Thornaby is a takeaway-led kitchen on Thornaby Road. We do
      stone-baked pizzas with 24-hour proved dough, the proper Teesside parmo,
      kebabs off the spit, smashed burgers, fusion pasta and loaded chips —
      plus party deals built to feed a full house.</p>
    <p>This site is a <strong>student coursework rebuild</strong>. The real
      Pizza &amp; Co Thornaby is the inspiration and brief — same business, same
      audience — but every line of code, every illustration and all the copy
      here is original work, built to demonstrate the five planes of user
      experience design.</p>

    <hr class="soft">

    <h2>The five planes, on a plate</h2>
    <div class="grid cols-2" style="margin-top:1.2rem">
      <div class="card"><div class="card-body">
        <h3>1 · Strategy</h3>
        <p class="desc">Drive direct orders (£0 platform fees), beat Just Eat
          on speed &amp; price, build repeat custom through accounts and deals.</p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>2 · Scope</h3>
        <p class="desc">Auth, persistent cart, pizza builder, diet filters,
          postcode checker, checkout, order history, admin dashboard.</p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>3 · Structure</h3>
        <p class="desc">Nine clear page types with a single PHP product
          template driving every dish from the database.</p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>4 · Skeleton</h3>
        <p class="desc">Sticky header, live cart badge, order modal, sidebar
          menu nav, sticky checkout summary.</p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>5 · Surface</h3>
        <p class="desc">Tomato &amp; cream palette, Fraunces + Inter type,
          original SVG food art with CSS steam animation, full dark mode.</p>
      </div></div>
      <div class="card"><div class="card-body">
        <h3>The result</h3>
        <p class="desc">A genuinely usable takeaway site you can run, order
          through, and administer end to end.</p>
      </div></div>
    </div>

    <hr class="soft">
    <h2>Find us</h2>
    <p><?= e(BRAND_ADDRESS) ?> · <?= e(BRAND_POSTCODE) ?><br>
      <a href="tel:<?= e(BRAND_PHONE) ?>"><?= e(BRAND_PHONE) ?></a> ·
      <a href="contact.php">Get in touch →</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
