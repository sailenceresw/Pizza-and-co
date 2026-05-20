<?php
/** JSON delivery-zone checker. */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$pc = (string) ($_GET['postcode'] ?? $_POST['postcode'] ?? '');
echo json_encode(check_delivery($pc));
