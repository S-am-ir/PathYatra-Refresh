<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['POST'], 'traveler');
$token = Validator::text($input['plan_token'] ?? '', 'Plan token', 64, 64);
$q = db()->prepare('SELECT id FROM itineraries WHERE plan_token = ? AND user_id = ?'); $q->execute([$token, getCurrentUserId()]);
if ($id = $q->fetchColumn()) jsonSuccess(['itinerary_id' => (int)$id], 'This plan is already saved');
$generated = $_SESSION['generated_plans'][$token] ?? null;
if (!$generated || $generated['expires'] < time()) jsonError('This generated plan has expired. Generate it again before saving.', 410);
$plan = $generated['plan'];
// Trust only the server-generated snapshot, never browser-edited costs or activities.
if (isset($input['title'])) $plan['title'] = Validator::text($input['title'], 'Title', 2, 200);
$database = db(); $database->beginTransaction();
try {
    $q = $database->prepare("INSERT INTO itineraries (user_id, title, start_date, end_date, total_days, total_budget, estimated_cost, season, status, plan_token, plan_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planned', ?, ?)");
    $q->execute([getCurrentUserId(), $plan['title'], $plan['start_date'], $plan['end_date'], $plan['total_days'], $plan['budget_summary']['total_budget'], $plan['budget_summary']['total_estimated'], $plan['season'], $token, json_encode($plan, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
    $id = (int)$database->lastInsertId();
    $dayQuery = $database->prepare('INSERT INTO itin_days (itinerary_id, day_number, day_date, destination_id, destination_name, accommodation_name, accommodation_cost, day_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $slotQuery = $database->prepare('INSERT INTO itin_slots (itin_day_id, slot_name, activity_id, activity_name, duration_hours, cost_npr) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($plan['days'] as $day) {
        if ($day['destination_id']) recordExists('destinations', $day['destination_id']);
        $dayQuery->execute([$id, $day['day_number'], $day['date'], $day['destination_id'], $day['destination'], $day['accommodation']['name'], $day['accommodation']['cost'], $day['day_total']]);
        $dayId = (int)$database->lastInsertId();
        foreach ($day['slots'] as $name => $slot) {
            if ($slot['activity_id']) recordExists('activities', $slot['activity_id']);
            $slotQuery->execute([$dayId, $name, $slot['activity_id'], $slot['activity'], $slot['duration'], $slot['cost']]);
        }
    }
    $database->commit();
    jsonSuccess(['itinerary_id' => $id], 'Itinerary saved', 201);
} catch (Throwable $e) { if ($database->inTransaction()) $database->rollBack(); throw $e; }
