<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(); $id = positiveId($input['destination_id'] ?? 0, 'destination ID'); recordExists('destinations', $id);
$q = db()->prepare('SELECT r.id, r.rating, r.comment, r.trip_month_year, r.created_at, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.destination_id = ? ORDER BY r.created_at DESC, r.id DESC'); $q->execute([$id]); jsonSuccess($q->fetchAll());
