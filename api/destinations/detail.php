<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(); $id = positiveId($input['id'] ?? 0, 'destination ID');
$q = db()->prepare('SELECT * FROM destinations WHERE id = ?'); $q->execute([$id]); $destination = $q->fetch();
if (!$destination) jsonError('Destination not found.', 404);
$q = db()->prepare('SELECT * FROM activities WHERE destination_id = ? ORDER BY category, name'); $q->execute([$id]); $destination['activities'] = $q->fetchAll();
$q = db()->prepare('SELECT r.id, r.rating, r.comment, r.trip_month_year, r.created_at, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.destination_id = ? ORDER BY r.created_at DESC, r.id DESC');
$q->execute([$id]); $destination['reviews'] = $q->fetchAll();
$user = sessionUser(); $destination['can_review'] = $user !== null && $user['role'] === 'traveler' && reviewEligibility($id, (int)$user['id']) !== null;
$destination['my_review'] = null;
if ($user) { $q = db()->prepare('SELECT id, rating, comment FROM reviews WHERE user_id = ? AND destination_id = ?'); $q->execute([$user['id'], $id]); $destination['my_review'] = $q->fetch() ?: null; }
jsonSuccess($destination);
