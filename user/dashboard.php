<?php
/**
 * YatraPath — Traveler Personal Dashboard
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$userName = getCurrentUserName();

// Traveler Stats
$itinStmt = $db->prepare("SELECT COUNT(*) FROM itineraries WHERE user_id = ?");
$itinStmt->execute([$userId]);
$totalItineraries = (int)$itinStmt->fetchColumn();

$completedStmt = $db->prepare("SELECT COUNT(*) FROM itineraries WHERE user_id = ? AND status = 'completed'");
$completedStmt->execute([$userId]);
$completedTrips = (int)$completedStmt->fetchColumn();

$reviewStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$reviewStmt->execute([$userId]);
$totalReviews = (int)$reviewStmt->fetchColumn();

// Recent 3 itineraries
$recentStmt = $db->prepare("
    SELECT * FROM itineraries 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 3
");
$recentStmt->execute([$userId]);
$recentItineraries = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler Dashboard — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: #fff; border-bottom: 1px solid var(--border); }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.95rem; }
        .nav-links a.active { color: var(--primary); font-weight: 600; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        
        /* Banner */
        .welcome-banner { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: #fff; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; }
        .btn-gen { background: #fff; color: var(--primary); padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-block; transition: transform 0.2s; }
        .btn-gen:hover { transform: translateY(-2px); }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-box { background: #fff; border: 1px solid var(--border); padding: 1.5rem; border-radius: 12px; }
        .stat-num { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { color: var(--muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }

        /* Section */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .section-header h2 { font-size: 1.3rem; }
        .itin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .itin-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .itin-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; }
        .itin-meta { font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem; }
        .itin-actions { display: flex; gap: 0.5rem; }
        .btn-view { padding: 0.45rem 0.85rem; border-radius: 6px; background: var(--bg); border: 1px solid var(--border); text-decoration: none; color: var(--text); font-size: 0.85rem; font-weight: 600; }
        .btn-view:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../homepage.php" class="logo">YatraPath</a>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="generator.php">Generate Plan</a>
            <a href="itineraries.php">My Itineraries</a>
            <a href="reviews.php">My Reviews</a>
            <a href="profile.php">Profile</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Sign Out</a>
        </nav>
    </header>

    <main class="container">
        <div class="welcome-banner">
            <div>
                <h1 style="font-size: 1.8rem; margin-bottom: 0.35rem;">Welcome back, <?= htmlspecialchars($userName ?? 'Traveler') ?>!</h1>
                <p style="opacity: 0.9;">Ready for your next adventure across the Himalayas or historic valleys?</p>
            </div>
            <a href="generator.php" class="btn-gen">+ Plan New Journey</a>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-num"><?= $totalItineraries ?></div>
                <div class="stat-label">Total Itineraries</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?= $completedTrips ?></div>
                <div class="stat-label">Trips Completed</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?= $totalReviews ?></div>
                <div class="stat-label">Reviews Shared</div>
            </div>
        </div>

        <section>
            <div class="section-header">
                <h2>Recent Saved Itineraries</h2>
                <a href="itineraries.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">View All &rarr;</a>
            </div>

            <?php if (empty($recentItineraries)): ?>
                <div style="background: #fff; border: 1px solid var(--border); padding: 3rem; border-radius: 12px; text-align: center;">
                    <p style="color: var(--muted); margin-bottom: 1rem;">You haven't generated any travel itineraries yet.</p>
                    <a href="generator.php" class="btn-gen" style="background: var(--primary); color: #fff;">Generate Your First Itinerary</a>
                </div>
            <?php else: ?>
                <div class="itin-grid">
                    <?php foreach ($recentItineraries as $it): ?>
                        <div class="itin-card">
                            <div class="itin-title"><?= htmlspecialchars($it['title']) ?></div>
                            <div class="itin-meta">
                                <div><strong>Duration:</strong> <?= (int)$it['total_days'] ?> Days (<?= htmlspecialchars($it['season']) ?>)</div>
                                <div><strong>Est. Cost:</strong> NPR <?= number_format((float)$it['estimated_cost']) ?> / <?= number_format((float)$it['total_budget']) ?></div>
                                <div><strong>Status:</strong> <?= ucfirst($it['status']) ?></div>
                            </div>
                            <div class="itin-actions">
                                <a href="itineraries.php?view=<?= $it['id'] ?>" class="btn-view">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
