<?php
/**
 * YatraPath API - Save Itinerary
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireLogin(true);

$userId = getCurrentUserId();
$plan = getJsonInput();

if (empty($plan) || !isset($plan['days'])) {
    jsonError('Invalid itinerary data provided.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $title = $plan['title'] ?? 'My Nepal Itinerary';
    $startDate = $plan['start_date'] ?? date('Y-m-d');
    $endDate = $plan['end_date'] ?? date('Y-m-d');
    $totalDays = (int)($plan['total_days'] ?? count($plan['days']));
    $totalBudget = (float)($plan['budget_summary']['total_budget'] ?? 0);
    $estimatedCost = (float)($plan['budget_summary']['total_estimated'] ?? 0);
    $season = $plan['season'] ?? 'Autumn';
    $shareToken = bin2hex(random_bytes(16));

    // 1. Insert main itinerary
    $stmt = $db->prepare("
        INSERT INTO itineraries (user_id, title, start_date, end_date, total_days, total_budget, estimated_cost, season, status, share_token)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planned', ?)
    ");
    $stmt->execute([$userId, $title, $startDate, $endDate, $totalDays, $totalBudget, $estimatedCost, $season, $shareToken]);
    $itineraryId = (int)$db->lastInsertId();

    // 2. Insert days and slots
    $dayStmt = $db->prepare("
        INSERT INTO itin_days (itinerary_id, day_number, day_date, destination_id, destination_name, accommodation_name, accommodation_cost, day_total)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $slotStmt = $db->prepare("
        INSERT INTO itin_slots (itin_day_id, slot_name, activity_id, activity_name, duration_hours, cost_npr)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($plan['days'] as $day) {
        $dayNum = (int)$day['day_number'];
        $dayDate = $day['date'];
        $destId = isset($day['destination_id']) ? (int)$day['destination_id'] : null;
        $destName = $day['destination'] ?? 'Nepal';
        $accomName = $day['accommodation']['name'] ?? 'Hotel';
        $accomCost = (float)($day['accommodation']['cost'] ?? 0);
        $dayTotal = (float)($day['day_total'] ?? 0);

        $dayStmt->execute([$itineraryId, $dayNum, $dayDate, $destId, $destName, $accomName, $accomCost, $dayTotal]);
        $dayId = (int)$db->lastInsertId();

        if (isset($day['slots']) && is_array($day['slots'])) {
            foreach ($day['slots'] as $slotName => $slot) {
                $actId = isset($slot['activity_id']) ? (int)$slot['activity_id'] : null;
                $actName = $slot['activity'] ?? 'Free time';
                $duration = (float)($slot['duration'] ?? 1.5);
                $cost = (float)($slot['cost'] ?? 0);

                $slotStmt->execute([$dayId, strtolower((string)$slotName), $actId, $actName, $duration, $cost]);
            }
        }
    }

    $db->commit();
    jsonSuccess(['itinerary_id' => $itineraryId, 'share_token' => $shareToken], 'Itinerary saved successfully', 201);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonError('Failed to save itinerary: ' . $e->getMessage(), 500);
}
