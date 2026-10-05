<?php
/**
 * YatraPath API - List Registered Travelers (Admin Only)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireAdmin(true);

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT u.id, u.name, u.email, u.status, u.created_at, COUNT(i.id) as itinerary_count
        FROM users u
        LEFT JOIN itineraries i ON u.id = i.user_id
        WHERE u.role = 'traveler'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    jsonSuccess($users, 'Travelers retrieved');

} catch (Throwable $e) {
    jsonError('Failed to fetch travelers: ' . $e->getMessage(), 500);
}
