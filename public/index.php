<?php

declare(strict_types=1);

/**
 * Front controller. Apache rewrites every request that isn't a real file to here.
 */

use App\App;
use App\Core\Database;

// With `php -S` (composer serve), let the dev server handle real files like CSS itself.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/vendor/autoload.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'");

$router = App::routes(
    Database::fromEnv(),
    getenv('FACILITATOR_PIN') ?: 'change-me',
    dirname(__DIR__) . '/views'
);

$result = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

if ($result['status'] !== 200) {
    http_response_code($result['status']);
}
echo $result['body'];
