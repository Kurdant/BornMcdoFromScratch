<?php

namespace WCDO\Config;

class Database
{
    private static ?\PDO $instance = null;

    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'wcdo';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'wcdo_user';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'wcdo_pass';

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$instance = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    /** Reset pour les tests */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
