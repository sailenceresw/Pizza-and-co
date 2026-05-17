<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        $token = bin2hex(random_bytes(20));
        $exp = time() + 3600;
        $pdo->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?')
            ->execute([$token, $exp, $row['id']]);
        // No real mail server in this coursework build — show the link (demo).
        $notice = 'reset-password.php?token=' . $token;
    } else {
        $notice = false; // don't reveal whether the email exists
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap section">
  <div class="form-card">
    <h1 style="font-size:1.7rem">Forgot your password?</h1>

    <?php if ($notice === false): ?>
      <p class="note">If that email is registered, a reset link has been sent.</p>
    <?php elseif ($notice): ?>
      <p class="note"><strong>Demo mode:</strong> no mail server in this build, so
        here’s your reset link:<br>
        <a href="<?= e($notice) ?>"><?= e($notice) ?></a></p>
    <?php else: ?>
      <p class="muted">Enter your email and we’ll send a reset link.</p>
      <form method="post">
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <button class="btn btn-primary btn-block">Send reset link</button>
      </form>
    <?php endif; ?>
    <p style="margin-top:1rem"><a href="login.php">← Back to sign in</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
