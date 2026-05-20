<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));

    if (strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
    if (strlen($body) < 5) {
        $errors[] = 'Please enter a message.';
    }

    if (!$errors) {
        $pdo->prepare(
            'INSERT INTO messages (name,email,subject,body) VALUES (?,?,?,?)'
        )->execute([$name, $email, $subject ?: 'General enquiry', $body]);
        // A real deployment would mail this; here we store it for the admin.
        @mail(BRAND_EMAIL, 'Website enquiry: ' . $subject,
            $body, 'From: ' . $email);
        $sent = true;
    }
}

$st = kitchen_status();
$pageTitle = 'Contact & Find Us';
$pageClass = 'contact';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <h1>Get in touch</h1>
  <p class="muted">Questions, big orders, feedback — we’re listening.</p>

  <div class="cart-layout" style="margin-top:2rem">
    <div>
      <?php if ($sent): ?>
        <div class="note" style="border-color:var(--green)">
          <strong>Thanks <?= e($_POST['name']) ?>!</strong> Your message is with
          the kitchen — we’ll reply by email soon.</div>
      <?php else: ?>
        <?php if ($errors): ?>
          <p class="error-text"><?= e(implode(' ', $errors)) ?></p>
        <?php endif; ?>
        <form method="post" class="form-card form-wide" style="max-width:none;margin:0">
          <?= csrf_field() ?>
          <div class="grid cols-2">
            <div class="field"><label for="name">Name</label>
              <input id="name" name="name" required
                     value="<?= e($_POST['name'] ?? '') ?>"></div>
            <div class="field"><label for="email">Email</label>
              <input type="email" id="email" name="email" required
                     value="<?= e($_POST['email'] ?? '') ?>"></div>
          </div>
          <div class="field"><label for="subject">Subject</label>
            <input id="subject" name="subject"
                   value="<?= e($_POST['subject'] ?? '') ?>"></div>
          <div class="field"><label for="body">Message</label>
            <textarea id="body" name="body" rows="5" required><?= e($_POST['body'] ?? '') ?></textarea></div>
          <button class="btn btn-primary">Send message</button>
        </form>
      <?php endif; ?>
    </div>

    <aside class="summary">
      <h2 style="font-size:1.2rem">Visit / call</h2>
      <p><strong><?= e(BRAND_NAME) ?> <?= e(BRAND_TOWN) ?></strong><br>
        <?= e(BRAND_ADDRESS) ?><br><?= e(BRAND_POSTCODE) ?></p>
      <p><a href="tel:<?= e(BRAND_PHONE) ?>">📞 <?= e(BRAND_PHONE) ?></a><br>
        <a href="mailto:<?= e(BRAND_EMAIL) ?>">✉ <?= e(BRAND_EMAIL) ?></a></p>
      <p class="pill-info"><?= e($st['label']) ?> · <?= e($st['next']) ?></p>

      <div style="margin-top:1rem;border-radius:var(--radius);overflow:hidden;
                  border:1px solid var(--line)">
        <iframe title="Map to Pizza &amp; Co Thornaby" loading="lazy"
          style="width:100%;height:240px;border:0"
          src="https://www.openstreetmap.org/export/embed.html?bbox=-1.31%2C54.54%2C-1.27%2C54.56&amp;layer=mapnik">
        </iframe>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
