<?php
/** Authentication helpers. */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(): ?array
{
    static $cached = false;
    static $user = null;
    if ($cached) {
        return $user;
    }
    $cached = true;
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && (int) $u['is_admin'] === 1;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('Please sign in to continue.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'account.php'));
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('403 — Admins only.');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    cart_restore((int) $user['id']);
}

function logout_user(): void
{
    cart_persist();
    unset($_SESSION['uid']);
    session_regenerate_id(true);
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        login_user($u);
        return true;
    }
    return false;
}

function register_user(string $name, string $email, string $password, string $phone = ''): array
{
    $email = strtolower(trim($email));
    if (strlen($name) < 2) {
        return [false, 'Please enter your name.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Please enter a valid email address.'];
    }
    if (strlen($password) < 6) {
        return [false, 'Password must be at least 6 characters.'];
    }
    $exists = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetchColumn()) {
        return [false, 'An account with that email already exists.'];
    }
    $stmt = db()->prepare(
        'INSERT INTO users (name,email,password_hash,phone) VALUES (?,?,?,?)'
    );
    $stmt->execute([
        trim($name), $email, password_hash($password, PASSWORD_DEFAULT), trim($phone),
    ]);
    $id = (int) db()->lastInsertId();
    $u = db()->prepare('SELECT * FROM users WHERE id = ?');
    $u->execute([$id]);
    login_user($u->fetch());
    return [true, 'Welcome aboard!'];
}
