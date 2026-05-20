<?php
/**
 * Pizza & Co Thornaby — global configuration.
 * Coursework project (HTML/CSS/JS/PHP). Inspired by the real Pizza & Co
 * Thornaby brief — all assets here are original / placeholder.
 */

declare(strict_types=1);

// --- Error visibility (dev) ------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');

// --- Paths -----------------------------------------------------------------
define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('DB_PATH', DATA_DIR . '/pizzaco.sqlite');

// --- Brand -----------------------------------------------------------------
define('BRAND_NAME', 'Pizza & Co');
define('BRAND_TOWN', 'Thornaby');
define('BRAND_TAGLINE', 'Stone-baked, locally loved — £0 platform fees.');
define('BRAND_ADDRESS', 'Unit 4, Thornaby Road, Thornaby, Stockton-on-Tees');
define('BRAND_POSTCODE', 'TS17 0EJ');
define('BRAND_PHONE', '01642 000000');
define('BRAND_EMAIL', 'hello@pizzaandco-thornaby.test');

// --- Delivery rules --------------------------------------------------------
// Outward postcode prefixes we deliver to (case-insensitive).
define('DELIVERY_ZONES', ['TS16', 'TS17', 'TS18', 'TS19', 'TS20', 'TS21']);
define('DELIVERY_FEE', 2.49);
define('FREE_DELIVERY_THRESHOLD', 25.00);
define('MIN_ORDER', 10.00);

// --- Opening hours (24h). [open, close]; close past midnight => +24. -------
$GLOBALS['OPENING_HOURS'] = [
    'Mon' => [16.0, 23.0],
    'Tue' => [16.0, 23.0],
    'Wed' => [16.0, 23.0],
    'Thu' => [16.0, 23.0],
    'Fri' => [16.0, 24.5], // until 00:30
    'Sat' => [15.0, 24.5],
    'Sun' => [15.0, 22.5],
];

// --- Session ---------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- CSRF token ------------------------------------------------------------
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Europe/London');
