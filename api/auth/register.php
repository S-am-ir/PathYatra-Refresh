<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['POST']);
$name = Validator::text($input['name'] ?? '', 'Name', 2, 100);
$email = strtolower(Validator::text($input['email'] ?? '', 'Email', 3, 150));
if (!Validator::isValidEmail($email)) jsonError('Enter a valid email address.', 422);
$password = $input['password'] ?? null;
if (!is_string($password) || str_contains($password, "\0") || strlen($password) < 8 || strlen($password) > 72) jsonError('Password must contain 8–72 bytes.', 422);
if ($password !== ($input['confirm_password'] ?? null)) jsonError('Passwords do not match.', 422);
$q = db()->prepare('SELECT id FROM users WHERE email = ?'); $q->execute([$email]);
if ($q->fetchColumn()) jsonError('An account with this email already exists.', 409);
try {
    $q = db()->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'traveler', 'active')");
    $q->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
} catch (PDOException $e) { if ($e->getCode() === '23000') jsonError('An account with this email already exists.', 409); throw $e; }
$id = (int)db()->lastInsertId(); session_regenerate_id(true); $_SESSION = ['user_id' => $id, 'name' => $name, 'email' => $email, 'role' => 'traveler'];
jsonSuccess(['user' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'traveler'], 'csrf_token' => csrfToken()], 'Account created', 201);
