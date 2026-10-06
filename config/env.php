<?php
// TypeForge env bootstrap: real environment wins; `.env` file fills the gaps.
// For shared hosts (no env-var support): copy `.env.example` to `.env`.
// Idempotent: safe to require from every entry point.
if (!defined('TF_ENV_LOADED')) {
    define('TF_ENV_LOADED', true);

    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (is_readable($envFile)) {
        foreach (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [] as $k => $v) {
            $k = trim((string)$k);
            if ($k === '' || getenv($k) !== false || array_key_exists($k, $_ENV)) continue;
            $v = trim((string)$v);
            if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;the
            $_SERVER[$k] = $v;
        }
    }

    if ((getenv('APP_ENV') ?: 'development') === 'production') {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL & ~E_DEPRECATED);
    }
}
