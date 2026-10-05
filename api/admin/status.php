<?php
/**
 * YatraPath API - Toggle Traveler Status (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireAdmin(true);

$input = getJsonInput();
$userId = (int)($input['user_id'] ?? 0);
$status = $input['status'] ?? 'active';

if ($userId <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    jsonError('Valid user ID and status (active/inactive) are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$status, $userId]);

    jsonSuccess(['status' => $status], 'User status updated');

} catch (Throwable $e) {
    jsonError('Failed to update status: ' . $e->getMessage(), 500);
}
