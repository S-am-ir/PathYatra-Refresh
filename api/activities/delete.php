<?php
/**
 * YatraPath API - Delete Activity (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireAdmin(true);

$input = getJsonInput();
$id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

if ($id <= 0) {
    jsonError('Valid activity ID is required.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM activities WHERE id = ?");
    $stmt->execute([$id]);

    jsonSuccess(null, 'Activity deleted successfully');

} catch (Throwable $e) {
    jsonError('Failed to delete activity: ' . $e->getMessage(), 500);
}
