<?php
/**
 * YatraPath API - Mark Itinerary as Completed
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireLogin(true);

$input = getJsonInput();
$id = (int)($input['id'] ?? 0);
$userId = getCurrentUserId();

if ($id <= 0) {
    jsonError('Valid itinerary ID is required.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE itineraries SET status = 'completed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    jsonSuccess(null, 'Trip marked as completed');

} catch (Throwable $e) {
    jsonError('Failed to update trip status: ' . $e->getMessage(), 500);
}
