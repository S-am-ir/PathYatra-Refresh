<?php
/**
 * YatraPath API - Logout Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'text/html')) {
    header('Location: /Yatra/homepage.php');
    exit;
}

jsonSuccess(null, 'Logged out successfully');
