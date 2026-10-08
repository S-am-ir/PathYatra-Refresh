<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['POST'], 'admin');
$values = activityFields($input);
$q = db()->prepare('INSERT INTO activities (destination_id, name, category, duration_hours, cost_npr, preferred_slot, suitable_seasons) VALUES (?, ?, ?, ?, ?, ?, ?)');
$q->execute($values);
jsonSuccess(['id' => (int)db()->lastInsertId()], 'Record created', 201);
