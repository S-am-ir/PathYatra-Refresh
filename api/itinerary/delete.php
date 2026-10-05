<?php
/**
 * YatraPath API - Delete Itinerary
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireLogin(true);

$input = getJsonInput();
$id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
$userId = getCurrentUserId();

if ($id <= 0) {
    jsonError('Valid itinerary ID is required.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM itineraries WHERE id = ? AND (user_id = ? OR 'admin' = ?)");
    $stmt->execute([$id, $userId, getCurrentUserRole()]);

    jsonSuccess(null, 'Itinerary deleted successfully');

} catch (Throwable $e) {
    jsonError('Failed to delete itinerary: ' . $e->getMessage(), 500);
}
