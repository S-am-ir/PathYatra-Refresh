<?php
/**
 * YatraPath API - Traveler Registration Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';

$input = getJsonInput();
$name = Validator::sanitizeString($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';
$redirect = $input['redirect'] ?? null;

if (empty($name) || empty($email) || empty($password)) {
    if ($redirect) {
        header("Location: /Yatra/user/index.php?tab=register&error=All fields are required.");
        exit;
    }
    jsonError('All fields are required.', 422);
}

if (!Validator::isValidEmail($email)) {
    if ($redirect) {
        header("Location: /Yatra/user/index.php?tab=register&error=Invalid email format.");
        exit;
    }
    jsonError('Invalid email format.', 422);
}

if (!Validator::isMinLength($password, 8)) {
    if ($redirect) {
        header("Location: /Yatra/user/index.php?tab=register&error=Password must be at least 8 characters.");
        exit;
    }
    jsonError('Password must be at least 8 characters.', 422);
}

if ($password !== $confirmPassword) {
    if ($redirect) {
        header("Location: /Yatra/user/index.php?tab=register&error=Passwords do not match.");
        exit;
    }
    jsonError('Passwords do not match.', 422);
}

try {
    $db = Database::getInstance()->getConnection();

    // Check duplicate email
    $dupCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $dupCheck->execute([$email]);
    if ($dupCheck->fetch()) {
        if ($redirect) {
            header("Location: /Yatra/user/index.php?tab=register&error=An account with this email already exists.");
            exit;
        }
        jsonError('An account with this email already exists.', 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, role, status) 
        VALUES (?, ?, ?, 'traveler', 'active')
    ");
    $stmt->execute([$name, $email, $hashedPassword]);
    $newUserId = (int)$db->lastInsertId();

    // Auto-login session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'traveler';

    if ($redirect) {
        header('Location: /Yatra/user/dashboard.php');
        exit;
    }

    jsonSuccess([
        'user' => [
            'id' => $newUserId,
            'name' => $name,
            'email' => $email,
            'role' => 'traveler'
        ]
    ], 'Registration successful', 201);

} catch (Throwable $e) {
    jsonError('Server error during registration: ' . $e->getMessage(), 500);
}
