<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['POST']);
$email = strtolower(Validator::text($input['email'] ?? '', 'Email', 3, 150));
$password = $input['password'] ?? null;
if (!is_string($password) || str_contains($password, "\0") || strlen($password) < 1 || strlen($password) > 72) jsonError('Enter a valid password.', 422);
$q = db()->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ?'); $q->execute([$email]); $user = $q->fetch();
if (!$user || !password_verify($password, $user['password'])) jsonError('Invalid email or password.', 401);
if ($user['status'] !== 'active') jsonError('This account is inactive. Contact an administrator.', 403);
session_regenerate_id(true); $_SESSION = ['user_id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
unset($user['password'], $user['status']); $user['id'] = (int)$user['id'];
jsonSuccess(['user' => $user, 'csrf_token' => csrfToken()], 'Signed in');
