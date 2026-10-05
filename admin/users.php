<?php
/**
 * YatraPath — Admin User Management
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$db = Database::getInstance()->getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
    
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$newStatus, $userId]);
    $message = "User status updated to {$newStatus}.";
}

$usersStmt = $db->query("
    SELECT u.id, u.name, u.email, u.status, u.created_at, COUNT(i.id) as itinerary_count
    FROM users u
    LEFT JOIN itineraries i ON u.id = i.user_id
    WHERE u.role = 'traveler'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Travelers — Yatra Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); }
        .status-active { color: #10b981; font-weight: 600; }
        .status-inactive { color: #ef4444; font-weight: 600; }
        .btn-toggle { padding: 0.35rem 0.65rem; border-radius: 6px; border: 1px solid var(--border); background: #0f172a; color: #fff; cursor: pointer; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>Yatra Admin</h2>
        <nav class="sidebar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="destinations.php">Destinations</a>
            <a href="activities.php">Activities</a>
            <a href="users.php" class="active">Travelers</a>
            <a href="analytics.php">Analytics</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Log Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h1>Registered Travelers</h1>
            <p style="color: var(--text-muted);">Manage user accounts and view trip creation activity</p>
        </header>

        <?php if ($message): ?>
            <div style="background: #059669; color: #fff; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <table>
                <thead>
                    <tr>
                        <th>Traveler Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Itineraries</th>
                        <th>Join Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="<?= $user['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td><?= (int)$user['itinerary_count'] ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at']))) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $user['status'] ?>">
                                    <button type="submit" class="btn-toggle">
                                        <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>
