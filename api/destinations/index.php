<?php
/**
 * YatraPath API - List Destinations with Filters
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

try {
    $db = Database::getInstance()->getConnection();

    $region = $_GET['region'] ?? null;
    $season = $_GET['season'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "SELECT * FROM destinations WHERE 1=1";
    $params = [];

    if (!empty($region)) {
        $sql .= " AND region = ?";
        $params[] = $region;
    }

    if (!empty($season)) {
        $sql .= " AND suitable_seasons LIKE ?";
        $params[] = "%{$season}%";
    }

    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $sql .= " ORDER BY avg_rating DESC, name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $destinations = $stmt->fetchAll();

    jsonSuccess($destinations, 'Destinations retrieved successfully');

} catch (Throwable $e) {
    jsonError('Failed to fetch destinations: ' . $e->getMessage(), 500);
}
