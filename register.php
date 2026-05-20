<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('account.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'register') {
    csrf_check();
    [$ok, $msg] = register_user(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['phone'] ?? '')
    );
    if ($ok) {
        flash($msg);
        redirect('account.php');
    }
    $error = $msg;
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <div class="form-card">
    <h1 style="font-size:1.8rem">Create your account</h1>
    <p class="muted">Free, 30 seconds, no spam.</p>

    <?php if ($error): ?><p class="error-text"><?= e($error) ?></p><?php endif; ?>

    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="register">
      <div class="field">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" required
               value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="phone">Phone (optional)</label>
        <input type="tel" id="phone" name="phone"
               value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">Password (min 6 characters)</label>
        <input type="password" id="password" name="password" required minlength="6">
      </div>
      <button class="btn btn-primary btn-block">Create account</button>
    </form>
    <p style="margin-top:1rem">Already have one? <a href="login.php">Sign in</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
