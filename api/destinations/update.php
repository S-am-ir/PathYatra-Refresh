<?php
/**
 * YatraPath API - Update Destination (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';

requireAdmin(true);

$input = getJsonInput();
$id = (int)($input['id'] ?? 0);
$name = Validator::sanitizeString($input['name'] ?? '');
$region = $input['region'] ?? 'Hilly';
$description = Validator::sanitizeString($input['description'] ?? '');
$avgCost = (float)($input['avg_cost_per_day'] ?? 3000);
$seasons = is_array($input['seasons'] ?? null) ? implode(',', $input['seasons']) : ($input['suitable_seasons'] ?? 'Spring,Autumn');
$lat = (float)($input['latitude'] ?? 27.7172);
$lng = (float)($input['longitude'] ?? 85.3240);

if ($id <= 0 || empty($name)) {
    jsonError('Valid destination ID and name are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        UPDATE destinations 
        SET name = ?, region = ?, description = ?, avg_cost_per_day = ?, suitable_seasons = ?, latitude = ?, longitude = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $region, $description, $avgCost, $seasons, $lat, $lng, $id]);

    jsonSuccess(null, 'Destination updated successfully');

} catch (Throwable $e) {
    jsonError('Failed to update destination: ' . $e->getMessage(), 500);
}
