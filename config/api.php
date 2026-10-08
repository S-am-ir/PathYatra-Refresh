<?php
declare(strict_types=1);
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/cors.php';
date_default_timezone_set('Asia/Kathmandu');
set_exception_handler(function (Throwable $error): void {
    if ($error instanceof InvalidArgumentException || $error instanceof DomainException) {
        jsonError($error->getMessage(), 422);
    }
    error_log((string)$error);
    jsonError('The server could not complete this request. Please try again.', 500);
});
function apiRequest(array $methods = ['GET'], ?string $role = null): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        jsonError('Request method is not supported.', 405);
    }
    if ($role !== null) {
        if ($role === 'admin') requireAdmin(true); else requireLogin(true);
    }
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !hash_equals(csrfToken(), $token)) jsonError('Your session token has expired. Refresh and try again.', 403);
    }
    return $method === 'GET' ? $_GET : getJsonInput();
}
function db(): PDO { return Database::getInstance()->getConnection(); }
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function positiveId(mixed $value, string $name = 'ID'): int {
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value <= 0) throw new InvalidArgumentException("A valid {$name} is required.");
    return (int)$value;
}
function recordExists(string $table, int $id): void {
    if (!in_array($table, ['destinations', 'activities', 'itineraries', 'users'], true)) throw new LogicException('Unsupported table');
    $q = db()->prepare("SELECT id FROM {$table} WHERE id = ?"); $q->execute([$id]);
    if (!$q->fetchColumn()) jsonError('The requested record was not found.', 404);
}
