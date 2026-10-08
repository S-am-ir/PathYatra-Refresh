<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['POST'], 'traveler');
if (getCurrentUserRole() !== 'traveler') jsonError('Reviews are available to travelers.', 403);
$id = positiveId($input['destination_id'] ?? 0, 'destination ID'); recordExists('destinations', $id);
$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
if ($rating === false || $rating < 1 || $rating > 5) jsonError('Rating must be an integer from 1 to 5.', 422);
$comment = Validator::text($input['comment'] ?? '', 'Review', 10, 2000);
$trip = reviewEligibility($id, getCurrentUserId());
if (!$trip) jsonError('Complete a saved trip containing this destination before reviewing it.', 403);
$db = db(); $db->beginTransaction();
try {
    $q = $db->prepare('SELECT id FROM destinations WHERE id = ? FOR UPDATE'); $q->execute([$id]);
    if (!$q->fetchColumn()) { $db->rollBack(); jsonError('Destination not found.', 404); }
    $q = $db->prepare('INSERT INTO reviews (user_id, destination_id, rating, comment, trip_month_year) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), trip_month_year = VALUES(trip_month_year), created_at = CURRENT_TIMESTAMP');
    $q->execute([getCurrentUserId(), $id, $rating, $comment, date('F Y', strtotime($trip['end_date']))]);
    $q = $db->prepare('UPDATE destinations SET avg_rating = (SELECT ROUND(AVG(rating), 2) FROM reviews WHERE destination_id = ?), total_reviews = (SELECT COUNT(*) FROM reviews WHERE destination_id = ?) WHERE id = ?'); $q->execute([$id, $id, $id]);
    $db->commit(); jsonSuccess(null, 'Review saved');
} catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
