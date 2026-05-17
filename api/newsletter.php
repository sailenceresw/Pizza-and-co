<?php
/** JSON newsletter signup. */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email.']);
    exit;
}

try {
    $stmt = db()->prepare('INSERT INTO newsletter (email) VALUES (?)');
    $stmt->execute([$email]);
    echo json_encode(['ok' => true, 'message' => 'You’re on the list — cheers!']);
} catch (PDOException $e) {
    // UNIQUE constraint => already subscribed.
    echo json_encode(['ok' => true, 'message' => 'You’re already subscribed 👍']);
}
