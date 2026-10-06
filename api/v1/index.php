<?php
// TypeForge REST API v1 — front controller.
// Deployment: Apache serves /api/v1/* via .htaccess -> index.php.
// Fallback without mod_rewrite: /api/v1/index.php?r=texts&mode=words
// Backward compat: legacy api/*.php files remain untouched.

require_once __DIR__ . '/src/Response.php';
require_once __DIR__ . '/src/TextService.php';
require_once __DIR__ . '/src/SessionService.php';
require_once __DIR__ . '/src/SettingsService.php';
require_once __DIR__ . '/src/LeaderboardService.php';
require_once __DIR__ . '/src/StatsService.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Resolve resource path: prefer ?r=, else PATH_INFO, else /api/v1/ prefix in REQUEST_URI.
$resource = trim((string)($_GET['r'] ?? ''), '/');
if ($resource === '') {
    $path_info = trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
    if ($path_info !== '') $resource = $path_info;
}
if ($resource === '') {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $uri_path = explode('?', $uri)[0];
    $marker = '/api/v1/';
    $pos = strpos($uri_path, $marker);
    if ($pos !== false) {
        $resource = trim(substr($uri_path, $pos + strlen($marker)), '/');
        // Strip trailing index.php if accessed directly.
        if ($resource === 'index.php' || str_starts_with($resource, 'index.php/')) {
            $resource = trim(substr($resource, strlen('index.php')), '/');
        }
    }
}
// Normalize: allow "index.php" empty -> help.
if ($resource === '' || $resource === 'index.php') $resource = '__help__';

function v1_require_login(): int {
    if (!isLoggedIn()) v1_error('Unauthorized. Please log in.', 401);
    $uid = getCurrentUserId();
    if (!$uid) v1_error('Unauthorized. Please log in.', 401);
    return $uid;
}

function v1_require_csrf(array $body): void {
    $token = $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCsrfToken(is_string($token) ? $token : '')) {
        v1_error('Invalid or expired CSRF token.', 403);
    }
}

try {
    // Public: GET texts (curated DB rotation first, built-in fallback)
    if ($resource === 'texts' && $method === 'GET') {
        $mode = strtolower(trim((string)($_GET['mode'] ?? 'words')));
        $count = isset($_GET['count']) ? (int)$_GET['count'] : 25;
        $punct = !empty($_GET['punctuation']) && $_GET['punctuation'] !== 'false';
        $nums  = !empty($_GET['numbers']) && $_GET['numbers'] !== 'false';
        try {
            if (in_array($mode, ['quote', 'code'], true)) {
                $difficulty = strtolower(trim((string)($_GET['difficulty'] ?? 'intermediate')));
                if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';
                $curated = V1_TextService::getFromDb(Database::getConnection(), $mode, $difficulty);
                if ($curated !== null) {
                    v1_send(true, 'Text generated.', ['text' => $curated['text'], 'mode' => $curated['mode'], 'count' => $count, 'text_id' => $curated['text_id'], 'title' => $curated['title'], 'difficulty' => $curated['difficulty'], 'source' => 'db'], 200);
                }
            }
        } catch (Throwable $e) {
            // Fall through to built-in generator.
        }
        $result = V1_TextService::get($mode, $count, $punct, $nums, strtolower(trim((string)($_GET['difficulty'] ?? 'intermediate'))));
        v1_send(true, 'Text generated.', $result, 200);
    }

    // POST sessions (create result)
    if ($resource === 'sessions' && $method === 'POST') {
        $uid = v1_require_login();
        if (($_SESSION['user_role'] ?? 'user') === 'admin') {
            v1_error('Admin accounts cannot submit typing results.', 403);
        }
        $body = v1_input();
        v1_require_csrf($body);
        $now = time();
        if (isset($_SESSION['last_result_save']) && ($now - (int)$_SESSION['last_result_save']) < 3) {
            v1_error('Too many submissions. Please wait a moment.', 429);
        }
        $db = Database::getConnection();
        $out = V1_SessionService::create($db, $uid, $body);
        v1_send(true, 'Test result recorded successfully.', $out, 201);
    }

    // GET sessions (paged) / sessions/{id}
    if (str_starts_with($resource, 'sessions') && $method === 'GET') {
        $uid = v1_require_login();
        $db = Database::getConnection();
        $parts = explode('/', $resource);
        if (count($parts) === 2 && ctype_digit($parts[1])) {
            $row = V1_StatsService::getOne($db, $uid, (int)$parts[1]);
            if (!$row) v1_error('Session not found.', 404);
            v1_send(true, 'Session retrieved.', $row, 200);
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
        v1_send(true, 'Sessions retrieved.', V1_StatsService::paged($db, $uid, $page, $limit), 200);
    }

    // GET leaderboard
    if ($resource === 'leaderboard' && $method === 'GET') {
        v1_require_login();
        $db = Database::getConnection();
        $out = V1_LeaderboardService::get($db, $_GET);
        v1_send(true, 'Leaderboard retrieved.', $out, 200);
    }

    // GET stats/*
    if (str_starts_with($resource, 'stats/') && $method === 'GET') {
        $uid = v1_require_login();
        $db = Database::getConnection();
        $sub = substr($resource, strlen('stats/'));
        if ($sub === 'summary') {
            v1_send(true, 'Summary retrieved.', V1_StatsService::summary($db, $uid), 200);
        }
        if ($sub === 'history') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            v1_send(true, 'History retrieved.', ['items' => V1_StatsService::history($db, $uid, $limit)], 200);
        }
        if ($sub === 'recent') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            v1_send(true, 'Recent sessions retrieved.', ['items' => V1_StatsService::recent($db, $uid, $limit)], 200);
        }
        v1_error('Unknown stats resource.', 404);
    }

    // GET me
    if ($resource === 'me' && $method === 'GET') {
        $uid = v1_require_login();
        v1_send(true, 'Profile retrieved.', V1_SettingsService::me(Database::getConnection(), $uid), 200);
    }
    // PATCH me (profile) — JSON or multipart (device avatar upload)
    if ($resource === 'me' && ($method === 'PATCH' || ($method === 'POST' && ($_GET['_method'] ?? '') === 'PATCH'))) {
        $uid = v1_require_login();
        $body = v1_input();
        if (empty($body) && !empty($_POST)) $body = $_POST;
        $csrf = $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!verifyCsrfToken(is_string($csrf) ? $csrf : '')) {
            v1_error('Invalid or expired CSRF token.', 403);
        }
        $avatar_file = $_FILES['avatar'] ?? null;
        V1_SettingsService::updateProfile(Database::getConnection(), $uid, $body, $avatar_file);
        v1_send(true, 'Profile updated successfully.', [], 200);
    }
    // PATCH me/settings (preferences)
    if ($resource === 'me/settings' && ($method === 'PATCH' || ($method === 'POST' && ($_GET['_method'] ?? '') === 'PATCH'))) {
        $uid = v1_require_login();
        $body = v1_input();
        v1_require_csrf($body);
        V1_SettingsService::updatePreferences(Database::getConnection(), $uid, $body);
        v1_send(true, 'Preferences updated successfully.', [], 200);
    }
    // PUT me/password
    if ($resource === 'me/password' && ($method === 'PUT' || ($method === 'POST' && ($_GET['_method'] ?? '') === 'PUT'))) {
        $uid = v1_require_login();
        $body = v1_input();
        v1_require_csrf($body);
        V1_SettingsService::changePassword(Database::getConnection(), $uid, $body);
        v1_send(true, 'Password updated successfully.', [], 200);
    }

    if ($resource === '__help__') {
        v1_send(true, 'TypeForge API v1. See docs/openapi.yaml.', ['endpoints' => [
            'GET texts', 'POST sessions', 'GET sessions', 'GET sessions/{id}',
            'GET leaderboard', 'GET stats/summary', 'GET stats/history', 'GET stats/recent',
            'GET me', 'PATCH me', 'PATCH me/settings', 'PUT me/password',
        ]], 200);
    }

    v1_error('Not found. See api/v1/docs/openapi.yaml.', 404);
} catch (InvalidArgumentException $e) {
    v1_error($e->getMessage(), v1_status($e->getCode(), 422));
} catch (RuntimeException $e) {
    v1_error($e->getMessage(), v1_status($e->getCode(), 500));
} catch (Throwable $e) {
    // Never leak SQLSTATE/driver internals to clients; log full detail instead.
    error_log('V1 Router Exception: ' . $e->getMessage());
    v1_error('Internal server error.', 500);
}
