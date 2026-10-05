<?php
/**
 * YatraPath API - Delete Destination (Admin Only)
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
    jsonError('Invalid destination ID.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM destinations WHERE id = ?");
    $stmt->execute([$id]);

    jsonSuccess(null, 'Destination deleted successfully');

} catch (Throwable $e) {
    jsonError('Failed to delete destination: ' . $e->getMessage(), 500);
}
