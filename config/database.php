<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'typeforge');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

final class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $isProd = (getenv('APP_ENV') ?: 'development') === 'production';
            if ($isProd && DB_USER === 'root' && DB_PASS === '') {
                error_log('Database misconfiguration: default root/empty password blocked in production.');
                http_response_code(500);
                self::failClosed('Database configuration error.');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Failure: " . $e->getMessage());
                http_response_code(500);
                self::failClosed('Database connection failed. Please check system configurations.');
            }
        }

        return self::$instance;
    }

    private static function isApiRequest(): bool {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if (str_contains($uri, '/api/')) {
            return true;
        }
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json');
    }

    private static function failClosed(string $message): never {
        if (self::isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                'success' => false,
                'message' => $message
            ]));
        }
        die('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Service unavailable</title></head><body><h1>Service temporarily unavailable</h1><p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p></body></html>');
    }
}