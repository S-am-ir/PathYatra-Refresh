<?php
/**
 * YatraPath API - Admin Analytics Aggregation Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

requireAdmin(true);

try {
    $db = Database::getInstance()->getConnection();

    // 1. Top generated destinations
    $destStmt = $db->query("
        SELECT d.name, COUNT(id.id) as count 
        FROM itin_days id 
        JOIN destinations d ON id.destination_id = d.id 
        GROUP BY d.id 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $topDestinations = $destStmt->fetchAll();

    // 2. Budget tier breakdown
    $budgetStmt = $db->query("
        SELECT 
            SUM(CASE WHEN total_budget < 15000 THEN 1 ELSE 0 END) as budget,
            SUM(CASE WHEN total_budget BETWEEN 15000 AND 50000 THEN 1 ELSE 0 END) as mid_range,
            SUM(CASE WHEN total_budget > 50000 THEN 1 ELSE 0 END) as luxury
        FROM itineraries
    ");
    $budgetTiers = $budgetStmt->fetch();

    // 3. Category distribution
    $catStmt = $db->query("
        SELECT category, COUNT(*) as count 
        FROM activities 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categoryDistribution = $catStmt->fetchAll();

    // 4. Key metrics totals
    $metrics = [
        'total_destinations'  => (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn(),
        'total_activities'    => (int)$db->query("SELECT COUNT(*) FROM activities")->fetchColumn(),
        'total_travelers'     => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'traveler'")->fetchColumn(),
        'total_itineraries'   => (int)$db->query("SELECT COUNT(*) FROM itineraries")->fetchColumn(),
        'total_reviews'       => (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn()
    ];

    jsonSuccess([
        'metrics'               => $metrics,
        'top_destinations'      => $topDestinations,
        'budget_tiers'          => [
            ['name' => 'Budget (< NPR 15k)', 'value' => (int)($budgetTiers['budget'] ?? 0)],
            ['name' => 'Mid-Range (15k-50k)', 'value' => (int)($budgetTiers['mid_range'] ?? 0)],
            ['name' => 'Luxury (> 50k)', 'value' => (int)($budgetTiers['luxury'] ?? 0)]
        ],
        'category_distribution' => $categoryDistribution
    ], 'Analytics data retrieved');

} catch (Throwable $e) {
    jsonError('Failed to fetch analytics: ' . $e->getMessage(), 500);
}
