<?php
/**
 * YatraPath - Session Management & Auth Verification
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getCurrentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function getCurrentUserName(): ?string {
    return $_SESSION['name'] ?? null;
}

function isAdmin(): bool {
    return isLoggedIn() && (getCurrentUserRole() === 'admin');
}

function requireLogin(bool $isApi = false): void {
    if (!isLoggedIn()) {
        if ($isApi) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to proceed.']);
            exit;
        } else {
            header('Location: /Yatra/user/index.php?error=unauthorized');
            exit;
        }
    }
}

function requireAdmin(bool $isApi = false): void {
    if (!isAdmin()) {
        if ($isApi) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: Administrative privilege required.']);
            exit;
        } else {
            header('Location: /Yatra/admin/index.php?error=forbidden');
            exit;
        }
    }
}
