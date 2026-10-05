<?php
/**
 * YatraPath — Admin Analytics & System Metrics
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$db = Database::getInstance()->getConnection();

// 1. Destination popularity from itinerary days
$popDestStmt = $db->query("
    SELECT d.name, COUNT(id.id) as visit_count 
    FROM itin_days id 
    JOIN destinations d ON id.destination_id = d.id 
    GROUP BY d.id 
    ORDER BY visit_count DESC 
    LIMIT 5
");
$popularDestinations = $popDestStmt->fetchAll();

// 2. Budget Distribution breakdown
$budgetStmt = $db->query("
    SELECT 
        SUM(CASE WHEN total_budget < 15000 THEN 1 ELSE 0 END) as budget_count,
        SUM(CASE WHEN total_budget BETWEEN 15000 AND 50000 THEN 1 ELSE 0 END) as mid_count,
        SUM(CASE WHEN total_budget > 50000 THEN 1 ELSE 0 END) as luxury_count
    FROM itineraries
");
$budgetStats = $budgetStmt->fetch();

// 3. Category distribution in activities
$catStmt = $db->query("SELECT category, COUNT(*) as total FROM activities GROUP BY category");
$categoryStats = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports — Yatra Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b; --text: #f8fafc; --text-muted: #94a3b8; --border: #334155; --primary: #3b82f6; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); padding: 1.5rem; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 1.3rem; margin-bottom: 2rem; color: var(--primary); }
        .sidebar-menu { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
        .sidebar-menu a { padding: 0.75rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.95rem; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #334155; color: #fff; }
        .main-content { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .card h3 { font-size: 1.15rem; margin-bottom: 1.25rem; }
        .bar-row { margin-bottom: 1rem; }
        .bar-label { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem; }
        .bar-track { background: #0f172a; border-radius: 999px; height: 10px; overflow: hidden; }
        .bar-fill { background: var(--primary); height: 100%; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); font-size: 0.85rem; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>Yatra Admin</h2>
        <nav class="sidebar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="destinations.php">Destinations</a>
            <a href="activities.php">Activities</a>
            <a href="users.php">Travelers</a>
            <a href="analytics.php" class="active">Analytics</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Log Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h1>System Analytics</h1>
            <p style="color: var(--text-muted);">Real-time metrics, tourism popularity, and budget tier allocations</p>
        </header>

        <div class="grid">
            <!-- Popular Destinations -->
            <div class="card">
                <h3>Top Visited Destinations</h3>
                <?php if (empty($popularDestinations)): ?>
                    <p style="color: var(--text-muted);">No visit logs recorded yet.</p>
                <?php else: ?>
                    <?php 
                    $maxVisits = max(1, ...array_column($popularDestinations, 'visit_count'));
                    foreach ($popularDestinations as $dest): 
                        $pct = ($dest['visit_count'] / $maxVisits) * 100;
                    ?>
                        <div class="bar-row">
                            <div class="bar-label">
                                <span><?= htmlspecialchars($dest['name']) ?></span>
                                <strong><?= (int)$dest['visit_count'] ?> days planned</strong>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Budget Tiers -->
            <div class="card">
                <h3>Budget Tier Distribution</h3>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Budget Tier (&lt; NPR 15,000)</span>
                        <strong><?= (int)($budgetStats['budget_count'] ?? 0) ?> trips</strong>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="background: #10b981; width: 45%;"></div>
                    </div>
                </div>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Mid-Range Tier (NPR 15,000 - 50,000)</span>
                        <strong><?= (int)($budgetStats['mid_count'] ?? 0) ?> trips</strong>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="background: #3b82f6; width: 65%;"></div>
                    </div>
                </div>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Luxury Tier (&gt; NPR 50,000)</span>
                        <strong><?= (int)($budgetStats['luxury_count'] ?? 0) ?> trips</strong>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="background: #f59e0b; width: 25%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Categories Distribution -->
        <section class="card">
            <h3>Activity Distribution by Category</h3>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Activities</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStats as $cat): ?>
                        <tr>
                            <td><strong><?= ucfirst(htmlspecialchars($cat['category'])) ?></strong></td>
                            <td><?= (int)$cat['total'] ?></td>
                            <td><span style="display:inline-block; height:6px; background:#3b82f6; width:<?= min(100, (int)$cat['total'] * 12) ?>px; border-radius:3px;"></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>
