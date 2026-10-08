<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['POST'], 'admin');
$values = destinationFields($input);
$q = db()->prepare('INSERT INTO destinations (name, region, description, avg_cost_per_day, suitable_seasons, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)');
$q->execute($values);
jsonSuccess(['id' => (int)db()->lastInsertId()], 'Record created', 201);
