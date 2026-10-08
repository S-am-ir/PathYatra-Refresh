<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_name('pathyatra_session');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https']);
    session_start();
}
function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }
function getCurrentUserId(): ?int { return isLoggedIn() ? (int)$_SESSION['user_id'] : null; }
function getCurrentUserRole(): ?string { return $_SESSION['role'] ?? null; }
function getCurrentUserName(): ?string { return $_SESSION['name'] ?? null; }
function isAdmin(): bool { return isLoggedIn() && getCurrentUserRole() === 'admin'; }
function sessionUser(): ?array {
    if (!isLoggedIn()) return null;
    require_once __DIR__ . '/database.php';
    $q = Database::getInstance()->getConnection()->prepare('SELECT id, name, email, role, status FROM users WHERE id = ?');
    $q->execute([getCurrentUserId()]); $user = $q->fetch();
    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['name'], $_SESSION['email'], $_SESSION['generated_plans']);
        return null;
    }
    $_SESSION['name'] = $user['name']; $_SESSION['role'] = $user['role']; $_SESSION['email'] = $user['email'];
    unset($user['status']); $user['id'] = (int)$user['id']; return $user;
}
function requireLogin(bool $isApi = false): void {
    if (!sessionUser()) {
        if ($isApi) { require_once __DIR__ . '/response.php'; jsonError('Please sign in to continue.', 401); }
        header('Location: /login'); exit;
    }
}
function requireAdmin(bool $isApi = false): void {
    requireLogin($isApi);
    if (!isAdmin()) {
        if ($isApi) { require_once __DIR__ . '/response.php'; jsonError('Administrative access is required.', 403); }
        http_response_code(403); exit('Administrative access is required.');
    }
}
