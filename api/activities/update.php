<?php
/**
 * YatraPath API - Update Activity (Admin Only)
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
$category = $input['category'] ?? 'cultural';
$duration = (float)($input['duration_hours'] ?? 2.0);
$cost = (float)($input['cost_npr'] ?? 0.0);
$slot = $input['preferred_slot'] ?? 'Morning';
$seasons = is_array($input['seasons'] ?? null) ? implode(',', $input['seasons']) : ($input['suitable_seasons'] ?? 'Spring,Autumn');

if ($id <= 0 || empty($name)) {
    jsonError('Valid activity ID and name are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        UPDATE activities 
        SET name = ?, category = ?, duration_hours = ?, cost_npr = ?, preferred_slot = ?, suitable_seasons = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $category, $duration, $cost, $slot, $seasons, $id]);

    jsonSuccess(null, 'Activity updated successfully');

} catch (Throwable $e) {
    jsonError('Failed to update activity: ' . $e->getMessage(), 500);
}
