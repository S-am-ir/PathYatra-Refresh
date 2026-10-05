<?php
/**
 * YatraPath API - User & Admin Login Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$redirect = $input['redirect'] ?? null;

if (empty($email) || empty($password)) {
    if ($redirect) {
        header("Location: {$redirect}?error=Please provide email and password.");
        exit;
    }
    jsonError('Please provide both email and password.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        if ($redirect) {
            header("Location: {$redirect}?error=Invalid credentials");
            exit;
        }
        jsonError('Invalid email or password.', 401);
    }

    if ($user['status'] === 'inactive') {
        if ($redirect) {
            header("Location: {$redirect}?error=Your account is deactivated. Contact admin.");
            exit;
        }
        jsonError('Account is inactive.', 403);
    }

    // Security: regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    if ($redirect) {
        // If logging in via traditional HTML form, redirect to appropriate portal
        if ($user['role'] === 'admin') {
            header('Location: /Yatra/admin/dashboard.php');
        } else {
            header('Location: /Yatra/user/dashboard.php');
        }
        exit;
    }

    jsonSuccess([
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ], 'Login successful');

} catch (Throwable $e) {
    jsonError('Server error during authentication: ' . $e->getMessage(), 500);
}
