<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Builds a configured PDO connection from environment variables.
 */
final class Database
{
    public static function fromEnv(): PDO
    {
        // DB_DSN lets you skip MySQL for quick local runs, e.g. DB_DSN=sqlite:/tmp/dropin.sqlite
        if ($dsn = getenv('DB_DSN')) {
            return self::connect($dsn);
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: '127.0.0.1',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: 'dropin'
        );

        return self::connect($dsn, getenv('DB_USER') ?: 'dropin', getenv('DB_PASS') ?: 'secret');
    }

    public static function connect(string $dsn, ?string $user = null, ?string $pass = null): PDO
    {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
