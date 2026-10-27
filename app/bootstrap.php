<?php
// Every page starts with: require __DIR__ . '/app/bootstrap.php';
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$GLOBALS['config'] = require __DIR__ . '/config.php';

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/validation.php';
require __DIR__ . '/view.php';

date_default_timezone_set('Europe/Riga');
error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');

set_exception_handler('handle_exception');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
