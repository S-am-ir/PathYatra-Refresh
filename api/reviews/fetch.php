<?php
/**
 * YatraPath API - Fetch Reviews for Destination
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

$destId = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : 0;

if ($destId <= 0) {
    jsonError('Valid destination ID is required.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT r.id, r.rating, r.comment, r.trip_month_year, r.created_at, u.name as reviewer_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.destination_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$destId]);
    $reviews = $stmt->fetchAll();

    jsonSuccess($reviews, 'Reviews retrieved');

} catch (Throwable $e) {
    jsonError('Failed to fetch reviews: ' . $e->getMessage(), 500);
}
