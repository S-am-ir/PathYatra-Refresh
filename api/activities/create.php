<?php
/**
 * YatraPath API - Create Activity (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';

requireAdmin(true);

$input = getJsonInput();
$destId = (int)($input['destination_id'] ?? 0);
$name = Validator::sanitizeString($input['name'] ?? '');
$category = $input['category'] ?? 'cultural';
$duration = (float)($input['duration_hours'] ?? 2.0);
$cost = (float)($input['cost_npr'] ?? 0.0);
$slot = $input['preferred_slot'] ?? 'Morning';
$seasons = is_array($input['seasons'] ?? null) ? implode(',', $input['seasons']) : ($input['suitable_seasons'] ?? 'Spring,Autumn');

if ($destId <= 0 || empty($name)) {
    jsonError('Destination ID and activity name are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO activities (destination_id, name, category, duration_hours, cost_npr, preferred_slot, suitable_seasons)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$destId, $name, $category, $duration, $cost, $slot, $seasons]);

    jsonSuccess(['id' => (int)$db->lastInsertId()], 'Activity created successfully', 201);

} catch (Throwable $e) {
    jsonError('Failed to create activity: ' . $e->getMessage(), 500);
}
