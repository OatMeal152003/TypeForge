<?php
// TypeForge REST v1 — shared JSON response helper.
// Uniform envelope: {success, message|error, data, timestamp}

function v1_send(bool $success, string $message, array $data = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => time(),
    ]);
    exit();
}

function v1_error(string $error, int $status = 400, array $data = []): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => false,
        'error'     => $error,
        'data'      => $data,
        'timestamp' => time(),
    ]);
    exit();
}

function v1_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// Exception codes aren't always HTTP statuses (PDO gives SQLSTATE strings).
// Coerce safely so error handling itself can never fatal.
function v1_status(mixed $code, int $fallback): int {
    $status = is_numeric($code) ? (int)$code : $fallback;
    return ($status >= 400 && $status < 600) ? $status : $fallback;
}
