<?php
/**
 * YatraPath — Traveler Reviews & Feedback
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destId = (int)($_POST['destination_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $tripDate = trim($_POST['trip_date'] ?? date('F Y'));

    if ($destId > 0 && mb_strlen($comment) >= 10) {
        $stmt = $db->prepare("
            INSERT INTO reviews (user_id, destination_id, rating, comment, trip_month_year)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $destId, $rating, $comment, $tripDate]);

        // Recalculate destination avg rating
        $calcStmt = $db->prepare("
            UPDATE destinations 
            SET avg_rating = (SELECT ROUND(AVG(rating), 2) FROM reviews WHERE destination_id = ?),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE destination_id = ?)
            WHERE id = ?
        ");
        $calcStmt->execute([$destId, $destId, $destId]);

        $message = "Thank you! Your review has been submitted.";
    } else {
        $error = "Please write a comment of at least 10 characters and choose a destination.";
    }
}

// Fetch destinations for review dropdown
$destinations = $db->query("SELECT id, name FROM destinations ORDER BY name ASC")->fetchAll();

// Fetch user's previous reviews
$myReviewsStmt = $db->prepare("
    SELECT r.*, d.name as destination_name 
    FROM reviews r 
    JOIN destinations d ON r.destination_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$myReviewsStmt->execute([$userId]);
$myReviews = $myReviewsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --card: #ffffff; --border: #e2e8f0; --text: #0f172a; --muted: #64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); padding-bottom: 3rem; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: #fff; border-bottom: 1px solid var(--border); }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.95rem; }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input, select, textarea { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.95rem; margin-bottom: 1rem; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 8px; background: var(--primary); color: #fff; border: none; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../homepage.php" class="logo">YatraPath</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="generator.php">Generator</a>
            <a href="itineraries.php">My Itineraries</a>
            <a href="reviews.php" style="color: var(--primary); font-weight:600;">My Reviews</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Sign Out</a>
        </nav>
    </header>

    <main class="container">
        <h1 style="font-size: 1.6rem; margin-bottom: 1.5rem;">Share Your Experience</h1>

        <?php if ($message): ?><div style="background: #059669; color: #fff; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background: #ef4444; color: #fff; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 1rem;">Write a Review</h3>
            <form method="POST">
                <div>
                    <label>Destination Visited</label>
                    <select name="destination_id" required>
                        <option value="">Select Destination</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Rating (1 - 5 Stars)</label>
                    <select name="rating">
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Exceptional</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Very Good</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="2">⭐⭐ 2 - Poor</option>
                        <option value="1">⭐ 1 - Terrible</option>
                    </select>
                </div>
                <div>
                    <label>Trip Month & Year</label>
                    <input type="text" name="trip_date" placeholder="e.g. October 2025" value="<?= date('F Y') ?>" required>
                </div>
                <div>
                    <label>Review Comment</label>
                    <textarea name="comment" rows="3" placeholder="Share your experience, highlights, tips for future travelers..." required></textarea>
                </div>
                <button type="submit" class="btn">Submit Review</button>
            </form>
        </div>

        <section class="card">
            <h3 style="margin-bottom: 1rem;">Your Past Reviews (<?= count($myReviews) ?>)</h3>
            <?php if (empty($myReviews)): ?>
                <p style="color: var(--muted);">You haven't posted any reviews yet.</p>
            <?php else: ?>
                <?php foreach ($myReviews as $r): ?>
                    <div style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong><?= htmlspecialchars($r['destination_name']) ?></strong>
                            <span><?= str_repeat('⭐', (int)$r['rating']) ?></span>
                        </div>
                        <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($r['trip_month_year']) ?></div>
                        <p style="font-size: 0.92rem;"><?= htmlspecialchars($r['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
