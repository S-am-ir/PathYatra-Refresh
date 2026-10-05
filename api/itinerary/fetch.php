<?php
/**
 * YatraPath API - Fetch User Itineraries or Single Plan Details
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireLogin(true);

$userId = getCurrentUserId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$shareToken = $_GET['share_token'] ?? null;

try {
    $db = Database::getInstance()->getConnection();

    if ($id > 0 || !empty($shareToken)) {
        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM itineraries WHERE id = ? AND (user_id = ? OR 'admin' = ?)");
            $stmt->execute([$id, $userId, getCurrentUserRole()]);
        } else {
            $stmt = $db->prepare("SELECT * FROM itineraries WHERE share_token = ?");
            $stmt->execute([$shareToken]);
        }
        $itin = $stmt->fetch();

        if (!$itin) {
            jsonError('Itinerary not found or access denied.', 404);
        }

        // Fetch days
        $dayStmt = $db->prepare("SELECT d.*, dest.latitude, dest.longitude FROM itin_days d LEFT JOIN destinations dest ON dest.id = d.destination_id WHERE d.itinerary_id = ? ORDER BY d.day_number ASC");
        $dayStmt->execute([$itin['id']]);
        $days = $dayStmt->fetchAll();

        foreach ($days as &$day) {
            $slotStmt = $db->prepare("SELECT * FROM itin_slots WHERE itin_day_id = ? ORDER BY id ASC");
            $slotStmt->execute([$day['id']]);
            $day['slots'] = $slotStmt->fetchAll();
        }
        unset($day);

        $itin['days'] = $days;
        jsonSuccess($itin, 'Itinerary retrieved');
    }

    // List all user's itineraries
    $stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $list = $stmt->fetchAll();

    jsonSuccess($list, 'User itineraries retrieved');

} catch (Throwable $e) {
    jsonError('Failed to retrieve itineraries: ' . $e->getMessage(), 500);
}
