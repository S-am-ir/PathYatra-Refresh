<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['POST'], 'admin'); $id = positiveId($input['user_id'] ?? 0, 'user ID');
$status = Validator::choice($input['status'] ?? '', ['active', 'inactive'], 'status');
$q = db()->prepare("SELECT id FROM users WHERE id = ? AND role = 'traveler'"); $q->execute([$id]); if (!$q->fetchColumn()) jsonError('Traveler not found.', 404);
$q = db()->prepare('UPDATE users SET status = ? WHERE id = ?'); $q->execute([$status, $id]); jsonSuccess(['status' => $status]);
