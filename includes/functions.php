<?php
/**
 * Shared helpers: output escaping, money, opening status, delivery zone,
 * and the session cart (with DB persistence for logged-in users).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/* ---------- Output / format ------------------------------------------- */

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money(float $v): string
{
    return '£' . number_format($v, 2);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Security token mismatch. Please go back and try again.');
    }
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/* ---------- Opening hours --------------------------------------------- */

/**
 * @return array{open:bool,label:string,next:string}
 */
function kitchen_status(): array
{
    $hours = $GLOBALS['OPENING_HOURS'];
    $now = new DateTime('now');
    $dow = $now->format('D');
    $h = (float) $now->format('G') + ((int) $now->format('i')) / 60.0;

    $todayClosesPastMidnight = false;
    if (isset($hours[$dow])) {
        [$o, $c] = $hours[$dow];
        if ($h >= $o && $h < $c) {
            $closeH = $c >= 24 ? $c - 24 : $c;
            $hh = str_pad((string) (int) $closeH, 2, '0', STR_PAD_LEFT);
            $mm = str_pad((string) (int) (round(($closeH - (int) $closeH) * 60)), 2, '0', STR_PAD_LEFT);
            return ['open' => true, 'label' => 'Open now', 'next' => "Closes {$hh}:{$mm}"];
        }
    }

    // Yesterday's late-night spill (close past midnight).
    $yest = (clone $now)->modify('-1 day')->format('D');
    if (isset($hours[$yest]) && $hours[$yest][1] > 24) {
        $spill = $hours[$yest][1] - 24;
        if ($h < $spill) {
            return ['open' => true, 'label' => 'Open now', 'next' => 'Closing soon'];
        }
    }

    // Find next opening time within the next 7 days.
    for ($i = 0; $i < 8; $i++) {
        $d = (clone $now)->modify("+{$i} day");
        $k = $d->format('D');
        if (!isset($hours[$k])) {
            continue;
        }
        [$o] = $hours[$k];
        if ($i === 0 && $h >= $o) {
            continue;
        }
        $oh = str_pad((string) (int) $o, 2, '0', STR_PAD_LEFT);
        $when = $i === 0 ? 'today' : ($i === 1 ? 'tomorrow' : $d->format('l'));
        return ['open' => false, 'label' => 'Closed', 'next' => "Opens {$when} {$oh}:00"];
    }
    return ['open' => false, 'label' => 'Closed', 'next' => 'See hours'];
}

/* ---------- Delivery zone --------------------------------------------- */

function normalise_postcode(string $pc): string
{
    return strtoupper(preg_replace('/\s+/', '', $pc) ?? '');
}

/**
 * @return array{ok:bool,zone:?string,fee:float,message:string}
 */
function check_delivery(string $postcode): array
{
    $pc = normalise_postcode($postcode);
    if ($pc === '' || !preg_match('/^[A-Z]{1,2}\d/', $pc)) {
        return ['ok' => false, 'zone' => null, 'fee' => 0,
            'message' => 'That doesn’t look like a UK postcode.'];
    }
    foreach (DELIVERY_ZONES as $zone) {
        if (str_starts_with($pc, $zone)) {
            return ['ok' => true, 'zone' => $zone, 'fee' => DELIVERY_FEE,
                'message' => "Great news — we deliver to {$zone}! "
                    . 'Delivery ' . money(DELIVERY_FEE)
                    . ' (free over ' . money(FREE_DELIVERY_THRESHOLD) . ').'];
        }
    }
    return ['ok' => false, 'zone' => null, 'fee' => 0,
        'message' => 'Sorry, you’re outside our delivery zone — '
            . 'collection is still available!'];
}

/* ---------- Cart ------------------------------------------------------- */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart(), 'qty'));
}

function cart_subtotal(): float
{
    $t = 0.0;
    foreach (cart() as $line) {
        $t += $line['unit_price'] * $line['qty'];
    }
    return round($t, 2);
}

function cart_add(string $name, float $unitPrice, array $options = [], int $qty = 1): void
{
    $ref = substr(sha1($name . json_encode($options)), 0, 12);
    $c = cart();
    if (isset($c[$ref])) {
        $c[$ref]['qty'] += $qty;
    } else {
        $c[$ref] = [
            'ref' => $ref,
            'name' => $name,
            'options' => $options,
            'unit_price' => round($unitPrice, 2),
            'qty' => max(1, $qty),
        ];
    }
    $_SESSION['cart'] = $c;
    cart_persist();
}

function cart_update(string $ref, int $qty): void
{
    $c = cart();
    if (isset($c[$ref])) {
        if ($qty <= 0) {
            unset($c[$ref]);
        } else {
            $c[$ref]['qty'] = $qty;
        }
        $_SESSION['cart'] = $c;
        cart_persist();
    }
}

function cart_remove(string $ref): void
{
    $c = cart();
    unset($c[$ref]);
    $_SESSION['cart'] = $c;
    cart_persist();
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
    cart_persist();
}

/** Mirror the session cart into the DB so it survives across sessions. */
function cart_persist(): void
{
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) {
        return;
    }
    $pdo = db();
    $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$uid]);
    $ins = $pdo->prepare(
        'INSERT INTO cart_items (user_id,ref,name,options_json,qty,unit_price)
         VALUES (?,?,?,?,?,?)'
    );
    foreach (cart() as $line) {
        $ins->execute([
            $uid, $line['ref'], $line['name'],
            json_encode($line['options']), $line['qty'], $line['unit_price'],
        ]);
    }
}

/** Load a logged-in user's saved cart back into the session. */
function cart_restore(int $uid): void
{
    if (!empty($_SESSION['cart'])) {
        cart_persist();
        return;
    }
    $rows = db()->prepare('SELECT * FROM cart_items WHERE user_id = ?');
    $rows->execute([$uid]);
    $c = [];
    foreach ($rows->fetchAll() as $r) {
        $c[$r['ref']] = [
            'ref' => $r['ref'],
            'name' => $r['name'],
            'options' => json_decode($r['options_json'], true) ?: [],
            'unit_price' => (float) $r['unit_price'],
            'qty' => (int) $r['qty'],
        ];
    }
    $_SESSION['cart'] = $c;
}

function options_summary(array $opts): string
{
    $bits = [];
    foreach ($opts as $k => $v) {
        if (is_array($v)) {
            $v = implode(', ', $v);
        }
        if ($v === '' || $v === null) {
            continue;
        }
        $bits[] = ucfirst((string) $k) . ': ' . $v;
    }
    return implode(' · ', $bits);
}
