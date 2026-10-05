<?php
/**
 * YatraPath API - Destination Detail Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    jsonError('Invalid destination ID.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $destStmt = $db->prepare("SELECT * FROM destinations WHERE id = ?");
    $destStmt->execute([$id]);
    $destination = $destStmt->fetch();

    if (!$destination) {
        jsonError('Destination not found.', 404);
    }

    // Associated activities
    $actStmt = $db->prepare("SELECT * FROM activities WHERE destination_id = ? ORDER BY category ASC, name ASC");
    $actStmt->execute([$id]);
    $destination['activities'] = $actStmt->fetchAll();

    // Associated reviews
    $revStmt = $db->prepare("
        SELECT r.id, r.rating, r.comment, r.trip_month_year, r.created_at, u.name as reviewer_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.destination_id = ? 
        ORDER BY r.created_at DESC
    ");
    $revStmt->execute([$id]);
    $destination['reviews'] = $revStmt->fetchAll();

    jsonSuccess($destination, 'Destination details retrieved');

} catch (Throwable $e) {
    jsonError('Failed to fetch destination details: ' . $e->getMessage(), 500);
}
