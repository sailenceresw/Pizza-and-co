<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$done = false;

$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE reset_token = ? AND reset_expires > ?'
);
$stmt->execute([$token, time()]);
$user = $token !== '' ? $stmt->fetch() : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_check();
    $pw = (string) ($_POST['password'] ?? '');
    if (strlen($pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $pdo->prepare(
            'UPDATE users SET password_hash=?, reset_token=NULL,
             reset_expires=NULL WHERE id=?'
        )->execute([password_hash($pw, PASSWORD_DEFAULT), $user['id']]);
        $done = true;
    }
}

$pageTitle = 'Set New Password';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <div class="form-card">
    <h1 style="font-size:1.7rem">Set a new password</h1>
    <?php if (!$user): ?>
      <p class="error-text">This reset link is invalid or has expired.</p>
      <p><a href="forgot-password.php">Request a new one</a></p>
    <?php elseif ($done): ?>
      <p class="note">Password updated. <a href="login.php">Sign in now →</a></p>
    <?php else: ?>
      <?php if ($error): ?><p class="error-text"><?= e($error) ?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field">
          <label for="password">New password</label>
          <input type="password" id="password" name="password" required minlength="6">
        </div>
        <button class="btn btn-primary btn-block">Update password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
