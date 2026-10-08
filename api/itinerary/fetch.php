<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
apiRequest(['GET'], 'traveler');
if (isset($_GET['id'])) {
    $id = positiveId($_GET['id']);
    $q = db()->prepare('SELECT * FROM itineraries WHERE id = ? AND user_id = ?'); $q->execute([$id, getCurrentUserId()]);
    $itinerary = $q->fetch(); if (!$itinerary) jsonError('This itinerary was not found.', 404);
    if ($itinerary['plan_json']) {
        $plan = json_decode($itinerary['plan_json'], true, 512, JSON_THROW_ON_ERROR);
        $plan['id'] = (int)$itinerary['id']; $plan['status'] = $itinerary['status']; $plan['title'] = $itinerary['title'];
        jsonSuccess($plan);
    }
    // Compatibility for plans saved by the original project.
    $q = db()->prepare('SELECT d.*, dest.latitude, dest.longitude FROM itin_days d LEFT JOIN destinations dest ON dest.id = d.destination_id WHERE d.itinerary_id = ? ORDER BY d.day_number'); $q->execute([$id]);
    $days = $q->fetchAll();
    $q = db()->prepare("SELECT * FROM itin_slots WHERE itin_day_id = ? ORDER BY FIELD(slot_name, 'morning', 'afternoon', 'evening')");
    foreach ($days as &$day) { $q->execute([$day['id']]); $day['slots'] = $q->fetchAll(); } unset($day);
    $itinerary['days'] = $days; unset($itinerary['plan_json'], $itinerary['share_token'], $itinerary['plan_token']);
    jsonSuccess($itinerary);
}
$q = db()->prepare('SELECT id, title, start_date, end_date, total_days, total_budget, estimated_cost, season, status, created_at FROM itineraries WHERE user_id = ? ORDER BY created_at DESC, id DESC'); $q->execute([getCurrentUserId()]);
jsonSuccess($q->fetchAll());
