<?php
/**
 * YatraPath — Admin Overview Dashboard
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$db = Database::getInstance()->getConnection();

// Fetch summary metrics
$destCount = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$actCount = (int)$db->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$userCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'traveler'")->fetchColumn();
$itinCount = (int)$db->query("SELECT COUNT(*) FROM itineraries")->fetchColumn();

// Fetch recent itineraries
$recentItinsStmt = $db->query("
    SELECT i.id, i.title, i.total_days, i.total_budget, i.created_at, u.name as traveler_name 
    FROM itineraries i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC LIMIT 5
");
$recentItineraries = $recentItinsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --sidebar: #1e293b;
            --card: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); padding: 1.5rem; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 1.3rem; margin-bottom: 2rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
        .sidebar-menu a { padding: 0.75rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.95rem; transition: all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #334155; color: #fff; }
        .logout-btn { color: #ef4444 !important; }

        /* Main Content */
        .main-content { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 800; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .stat-title { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2.2rem; font-weight: 800; color: #fff; }

        /* Recent Activity Table */
        .section-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .section-card h3 { font-size: 1.15rem; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); font-weight: 600; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polygon points="3 11 22 2 13 21 11 13 3 11"/>
            </svg>
            Yatra Admin
        </h2>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="destinations.php">Destinations</a>
            <a href="activities.php">Activities</a>
            <a href="users.php">Travelers</a>
            <a href="analytics.php">Analytics</a>
            <a href="../homepage.php">Public Home</a>
            <a href="../api/auth/logout.php" class="logout-btn">Log Out</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h1>System Overview</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Welcome back, <?= htmlspecialchars(getCurrentUserName() ?? 'Admin') ?></p>
            </div>
        </header>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Destinations</div>
                <div class="stat-value"><?= $destCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Activities Catalog</div>
                <div class="stat-value"><?= $actCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Registered Travelers</div>
                <div class="stat-value"><?= $userCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Generated Itineraries</div>
                <div class="stat-value"><?= $itinCount ?></div>
            </div>
        </section>

        <!-- Recent Itineraries -->
        <section class="section-card">
            <h3>Recent Itineraries Generated</h3>
            <?php if (empty($recentItineraries)): ?>
                <p style="color: var(--text-muted);">No itineraries generated yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Trip Title</th>
                            <th>Traveler</th>
                            <th>Duration</th>
                            <th>Budget (NPR)</th>
                            <th>Date Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentItineraries as $itin): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($itin['title']) ?></strong></td>
                                <td><?= htmlspecialchars($itin['traveler_name']) ?></td>
                                <td><?= (int)$itin['total_days'] ?> Days</td>
                                <td>NPR <?= number_format((float)$itin['total_budget']) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($itin['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
