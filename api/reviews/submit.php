<?php
/**
 * YatraPath API - Submit Review Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';

requireLogin(true);

$input = getJsonInput();
$userId = getCurrentUserId();
$destId = (int)($input['destination_id'] ?? 0);
$rating = (int)($input['rating'] ?? 5);
$comment = Validator::sanitizeString($input['comment'] ?? '');
$tripDate = Validator::sanitizeString($input['trip_month_year'] ?? date('F Y'));

if ($destId <= 0 || $rating < 1 || $rating > 5 || mb_strlen($comment) < 10) {
    jsonError('Destination ID, rating (1-5), and a review comment of at least 10 characters are required.', 422);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO reviews (user_id, destination_id, rating, comment, trip_month_year)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $destId, $rating, $comment, $tripDate]);

    // Update destination avg rating and count
    $recalc = $db->prepare("
        UPDATE destinations 
        SET avg_rating = (SELECT ROUND(AVG(rating), 2) FROM reviews WHERE destination_id = ?),
            total_reviews = (SELECT COUNT(*) FROM reviews WHERE destination_id = ?)
        WHERE id = ?
    ");
    $recalc->execute([$destId, $destId, $destId]);

    jsonSuccess(['id' => (int)$db->lastInsertId()], 'Review submitted successfully', 201);

} catch (Throwable $e) {
    jsonError('Failed to submit review: ' . $e->getMessage(), 500);
}
