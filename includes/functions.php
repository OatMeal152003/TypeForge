<?php

if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function sanitize(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sendJsonResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => time()
    ]);
    exit();
}

function calculateNetWpm(int $totalChars, int $uncorrectedErrors, float $timeInSeconds): float {
    if ($timeInSeconds <= 0) return 0.0;
    $timeInMinutes = $timeInSeconds / 60.0;
    $grossWpm = ($totalChars / 5.0) / $timeInMinutes;
    $netWpm = $grossWpm - ($uncorrectedErrors / $timeInMinutes);
    return max(0.0, round($netWpm, 2));
}

function calculateAccuracy(int $correctChars, int $totalChars): float {
    if ($totalChars <= 0) return 100.0;
    $accuracy = ($correctChars / $totalChars) * 100.0;
    return min(100.0, max(0.0, round($accuracy, 2)));
}
