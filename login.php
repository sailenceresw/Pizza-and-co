<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('account.php');
}

$next = $_GET['next'] ?? $_POST['next'] ?? 'account.php';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    csrf_check();
    if (attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
        flash('Welcome back!');
        redirect(filter_var($next, FILTER_VALIDATE_URL) ? 'account.php' : $next);
    }
    $error = 'Email or password is incorrect.';
}

$pageTitle = 'Sign In';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <div class="form-card">
    <h1 style="font-size:1.8rem">Sign in</h1>
    <p class="muted">Order faster, track status &amp; reorder favourites.</p>

    <?php if ($error): ?><p class="error-text"><?= e($error) ?></p><?php endif; ?>

    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <input type="hidden" name="form" value="login">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary btn-block">Sign in</button>
    </form>

    <p class="note" style="margin-top:1.2rem">
      <strong>Demo logins</strong><br>
      Customer — <code>sam@example.test</code> / <code>password</code><br>
      Admin — <code>admin@pizzaandco.test</code> / <code>admin123</code>
    </p>
    <p style="margin-top:1rem">New here?
      <a href="register.php">Create an account</a> ·
      <a href="forgot-password.php">Forgot password?</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
