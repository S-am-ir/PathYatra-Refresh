<?php
/**
 * YatraPath API - List Activities
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

$destId = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : 0;
$category = $_GET['category'] ?? null;

try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT a.*, d.name as destination_name FROM activities a JOIN destinations d ON a.destination_id = d.id WHERE 1=1";
    $params = [];

    if ($destId > 0) {
        $sql .= " AND a.destination_id = ?";
        $params[] = $destId;
    }

    if (!empty($category)) {
        $sql .= " AND a.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY a.name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();

    jsonSuccess($activities, 'Activities retrieved');

} catch (Throwable $e) {
    jsonError('Failed to fetch activities: ' . $e->getMessage(), 500);
}
