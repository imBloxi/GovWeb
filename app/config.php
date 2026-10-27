<?php
// Default settings work with a stock WampServer install.
// To override them, copy config.local.example.php to config.local.php
// (it's git-ignored) or set environment variables.

$config = [
    'app_name' => 'GovWeb',
    'debug'    => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    // Lists the demo usernames on the sign-in page. Turn off once real accounts exist.
    'show_demo_accounts' => true,
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'name'     => getenv('DB_NAME') ?: 'government_registration',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}

return $config;
