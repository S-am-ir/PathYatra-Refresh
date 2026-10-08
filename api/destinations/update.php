<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['POST', 'PUT'], 'admin');
$id = positiveId($input['id'] ?? 0); recordExists('destinations', $id);
$values = destinationFields($input); $values[] = $id;
$q = db()->prepare('UPDATE destinations SET name = ?, region = ?, description = ?, avg_cost_per_day = ?, suitable_seasons = ?, latitude = ?, longitude = ? WHERE id = ?');
$q->execute($values);
jsonSuccess(null, 'Record updated');
