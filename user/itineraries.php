<?php
/**
 * YatraPath — Saved Itineraries Management
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$message = '';

// Handle mark as completed or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itinId = (int)($_POST['itinerary_id'] ?? 0);

    if ($action === 'complete' && $itinId > 0) {
        $stmt = $db->prepare("UPDATE itineraries SET status = 'completed' WHERE id = ? AND user_id = ?");
        $stmt->execute([$itinId, $userId]);
        $message = "Trip marked as completed! You can now share a review.";
    } elseif ($action === 'delete' && $itinId > 0) {
        $stmt = $db->prepare("DELETE FROM itineraries WHERE id = ? AND user_id = ?");
        $stmt->execute([$itinId, $userId]);
        $message = "Itinerary removed.";
    }
}

// Check if viewing single itinerary details
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$singleItin = null;
$itinDays = [];

if ($viewId > 0) {
    $stmt = $db->prepare("SELECT * FROM itineraries WHERE id = ? AND user_id = ?");
    $stmt->execute([$viewId, $userId]);
    $singleItin = $stmt->fetch();

    if ($singleItin) {
        $daysStmt = $db->prepare("SELECT * FROM itin_days WHERE itinerary_id = ? ORDER BY day_number ASC");
        $daysStmt->execute([$viewId]);
        $days = $daysStmt->fetchAll();

        foreach ($days as $day) {
            $slotsStmt = $db->prepare("SELECT * FROM itin_slots WHERE itin_day_id = ? ORDER BY id ASC");
            $slotsStmt->execute([$day['id']]);
            $day['slots'] = $slotsStmt->fetchAll();
            $itinDays[] = $day;
        }
    }
}

// Fetch all itineraries for user
$allItinStmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC");
$allItinStmt->execute([$userId]);
$itineraries = $allItinStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Itineraries — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --card: #ffffff; --border: #e2e8f0; --text: #0f172a; --muted: #64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); padding-bottom: 3rem; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: #fff; border-bottom: 1px solid var(--border); }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.95rem; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .btn { padding: 0.45rem 0.85rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; border: none; }
        .btn-success { background: #059669; color: #fff; border: none; }
        .btn-danger { background: #ef4444; color: #fff; border: none; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; }
        .badge-planned { background: #dbeafe; color: #1d4ed8; }
        .badge-completed { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../homepage.php" class="logo">YatraPath</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="generator.php">Generator</a>
            <a href="itineraries.php" style="color: var(--primary); font-weight:600;">My Itineraries</a>
            <a href="reviews.php">My Reviews</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Sign Out</a>
        </nav>
    </header>

    <main class="container">
        <?php if ($message): ?>
            <div style="background: #059669; color: #fff; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($singleItin): ?>
            <!-- Single Itinerary Detailed View -->
            <div style="margin-bottom: 1.5rem;">
                <a href="itineraries.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">&larr; Back to all itineraries</a>
            </div>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h2><?= htmlspecialchars($singleItin['title']) ?></h2>
                        <div style="color: var(--muted); font-size: 0.9rem;">
                            <?= htmlspecialchars($singleItin['start_date']) ?> to <?= htmlspecialchars($singleItin['end_date']) ?> · <?= (int)$singleItin['total_days'] ?> Days · <?= htmlspecialchars($singleItin['season']) ?>
                        </div>
                    </div>
                    <div>
                        <span class="badge badge-<?= $singleItin['status'] ?>"><?= ucfirst($singleItin['status']) ?></span>
                        <button onclick="window.print()" class="btn btn-primary" style="margin-left: 0.5rem;">Print / Save PDF</button>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0;">

                <h3 style="margin-bottom: 1rem;">Day-by-Day Schedule</h3>
                <?php foreach ($itinDays as $d): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                        <h4 style="margin-bottom: 0.5rem;">Day <?= $d['day_number'] ?>: <?= htmlspecialchars($d['destination_name']) ?> (<?= htmlspecialchars($d['day_date']) ?>)</h4>
                        <div style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <strong>Stay:</strong> <?= htmlspecialchars($d['accommodation_name'] ?? 'Hotel') ?> (NPR <?= number_format((float)$d['accommodation_cost']) ?>)
                        </div>
                        <div style="border-top: 1px dashed var(--border); padding-top: 0.5rem;">
                            <?php foreach ($d['slots'] as $s): ?>
                                <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 0.25rem 0;">
                                    <span><strong><?= ucfirst($s['slot_name']) ?>:</strong> <?= htmlspecialchars($s['activity_name']) ?> (<?= (float)$s['duration_hours'] ?> hrs)</span>
                                    <span>NPR <?= number_format((float)$s['cost_npr']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($singleItin['status'] === 'completed'): ?>
                    <div style="margin-top: 1.5rem; text-align: center;">
                        <a href="reviews.php" class="btn btn-success">Write a Review for Destinations Visited</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- All Itineraries List -->
            <h1 style="font-size: 1.7rem; margin-bottom: 1.5rem;">My Saved Itineraries</h1>
            <?php if (empty($itineraries)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <p style="color: var(--muted); margin-bottom: 1rem;">You don't have any saved itineraries yet.</p>
                    <a href="generator.php" class="btn btn-primary">Generate a Plan Now</a>
                </div>
            <?php else: ?>
                <?php foreach ($itineraries as $it): ?>
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem;"><?= htmlspecialchars($it['title']) ?></h3>
                                <div style="color: var(--muted); font-size: 0.9rem; margin-bottom: 0.75rem;">
                                    <?= htmlspecialchars($it['start_date']) ?> to <?= htmlspecialchars($it['end_date']) ?> · <?= (int)$it['total_days'] ?> Days
                                </div>
                                <div style="font-size: 0.9rem;">
                                    <strong>Budget:</strong> NPR <?= number_format((float)$it['total_budget']) ?> · 
                                    <strong>Estimated:</strong> NPR <?= number_format((float)$it['estimated_cost']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-<?= $it['status'] ?>"><?= ucfirst($it['status']) ?></span>
                            </div>
                        </div>
                        <div style="margin-top: 1.25rem; display: flex; gap: 0.5rem;">
                            <a href="?view=<?= $it['id'] ?>" class="btn">View Plan</a>
                            <?php if ($it['status'] === 'planned'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="itinerary_id" value="<?= $it['id'] ?>">
                                    <button type="submit" class="btn btn-success">Mark Completed</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Delete this itinerary?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="itinerary_id" value="<?= $it['id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>

</body>
</html>
