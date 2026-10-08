<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['POST', 'PUT'], 'admin');
$id = positiveId($input['id'] ?? 0); recordExists('activities', $id);
$values = activityFields($input); $values[] = $id;
$q = db()->prepare('UPDATE activities SET destination_id = ?, name = ?, category = ?, duration_hours = ?, cost_npr = ?, preferred_slot = ?, suitable_seasons = ? WHERE id = ?');
$q->execute($values);
jsonSuccess(null, 'Record updated');
