<?php
/** JSON cart API: add / update / remove / clear. */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $name = trim((string) ($_POST['name'] ?? ''));
            $price = (float) ($_POST['unit_price'] ?? 0);
            $qty = max(1, (int) ($_POST['qty'] ?? 1));
            $opts = [];
            if (!empty($_POST['options'])) {
                $decoded = json_decode((string) $_POST['options'], true);
                if (is_array($decoded)) {
                    $opts = $decoded;
                }
            } else {
                // Build options from individual posted fields (product page form).
                foreach (['size', 'crust', 'sauce', 'extras', 'dip', 'notes'] as $k) {
                    if (!empty($_POST[$k])) {
                        $opts[$k] = $_POST[$k];
                    }
                }
            }
            if ($name === '' || $price <= 0) {
                throw new RuntimeException('Invalid item.');
            }
            cart_add($name, $price, $opts, $qty);
            echo json_encode([
                'ok' => true, 'name' => $name,
                'count' => cart_count(), 'subtotal' => cart_subtotal(),
            ]);
            break;

        case 'update':
            cart_update((string) ($_POST['ref'] ?? ''), (int) ($_POST['qty'] ?? 1));
            echo json_encode(['ok' => true, 'count' => cart_count(),
                'subtotal' => cart_subtotal()]);
            break;

        case 'remove':
            cart_remove((string) ($_POST['ref'] ?? ''));
            echo json_encode(['ok' => true, 'count' => cart_count(),
                'subtotal' => cart_subtotal()]);
            break;

        case 'clear':
            cart_clear();
            echo json_encode(['ok' => true, 'count' => 0, 'subtotal' => 0]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    }
} catch (Throwable $ex) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $ex->getMessage()]);
}
