<?php
/**
 * YatraPath API - Create Destination (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';

requireAdmin(true);

$input = getJsonInput();
$name = Validator::sanitizeString($input['name'] ?? '');
$region = $input['region'] ?? 'Hilly';
$description = Validator::sanitizeString($input['description'] ?? '');
$avgCost = (float)($input['avg_cost_per_day'] ?? 3000);
$seasons = is_array($input['seasons'] ?? null) ? implode(',', $input['seasons']) : ($input['suitable_seasons'] ?? 'Spring,Autumn');
$lat = (float)($input['latitude'] ?? 27.7172);
$lng = (float)($input['longitude'] ?? 85.3240);
$imageUrl = $input['image_url'] ?? null;

if (empty($name) || empty($description)) {
    jsonError('Name and description are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO destinations (name, region, description, avg_cost_per_day, suitable_seasons, latitude, longitude, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $region, $description, $avgCost, $seasons, $lat, $lng, $imageUrl]);

    $newId = (int)$db->lastInsertId();
    jsonSuccess(['id' => $newId], 'Destination created successfully', 201);

} catch (Throwable $e) {
    jsonError('Failed to create destination: ' . $e->getMessage(), 500);
}
