<?php
declare(strict_types=1);
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$allowed = array_filter(array_map('trim', explode(',', getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5180,http://127.0.0.1:5180')));
$host = $_SERVER['HTTP_HOST'] ?? '';
$scheme = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'));
if ($origin && !in_array($origin, $allowed, true) && $origin !== $scheme . '://' . $host) {
    require_once __DIR__ . '/response.php'; jsonError('This origin is not allowed.', 403);
}
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); header('Access-Control-Allow-Credentials: true'); }
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
