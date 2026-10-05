<?php
/**
 * YatraPath — Admin Activities Management
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $destId = (int)($_POST['destination_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'cultural';
        $duration = (float)($_POST['duration_hours'] ?? 2.0);
        $cost = (float)($_POST['cost_npr'] ?? 0.0);
        $slot = $_POST['preferred_slot'] ?? 'Morning';
        $seasons = implode(',', $_POST['seasons'] ?? ['Spring', 'Autumn']);

        if ($destId > 0 && !empty($name)) {
            $stmt = $db->prepare("
                INSERT INTO activities (destination_id, name, category, duration_hours, cost_npr, preferred_slot, suitable_seasons)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$destId, $name, $category, $duration, $cost, $slot, $seasons]);
            $message = "Activity added successfully.";
        } else {
            $error = "Destination and activity name are required.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM activities WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Activity deleted.";
        }
    }
}

$destinations = $db->query("SELECT id, name FROM destinations ORDER BY name ASC")->fetchAll();

$activitiesStmt = $db->query("
    SELECT a.*, d.name as destination_name 
    FROM activities a 
    JOIN destinations d ON a.destination_id = d.id 
    ORDER BY d.name ASC, a.name ASC
");
$activities = $activitiesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Activities — Yatra Admin</title>
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
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block; }
        input, select { width: 100%; padding: 0.65rem; border-radius: 6px; border: 1px solid var(--border); background: #0f172a; color: #fff; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; padding: 0.35rem 0.65rem; font-size: 0.8rem; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; background: #334155; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>Yatra Admin</h2>
        <nav class="sidebar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="destinations.php">Destinations</a>
            <a href="activities.php" class="active">Activities</a>
            <a href="users.php">Travelers</a>
            <a href="analytics.php">Analytics</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Log Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h1>Activities Catalog</h1>
        </header>

        <!-- Add Activity Form -->
        <section class="card">
            <h3 style="margin-bottom: 1rem;">Add Activity</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div>
                        <label>Destination</label>
                        <select name="destination_id" required>
                            <option value="">Select Destination</option>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Activity Name</label>
                        <input type="text" name="name" placeholder="e.g. Paragliding Flight" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category">
                            <option value="adventure">Adventure</option>
                            <option value="cultural">Cultural</option>
                            <option value="nature">Nature</option>
                            <option value="food">Food</option>
                            <option value="wellness">Wellness</option>
                            <option value="photography">Photography</option>
                        </select>
                    </div>
                    <div>
                        <label>Duration (Hours)</label>
                        <input type="number" step="0.5" name="duration_hours" value="2.0">
                    </div>
                    <div>
                        <label>Cost (NPR)</label>
                        <input type="number" name="cost_npr" value="500">
                    </div>
                    <div>
                        <label>Preferred Slot</label>
                        <select name="preferred_slot">
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Suitable Seasons</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Spring" checked> Spring</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Summer"> Summer</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Autumn" checked> Autumn</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Winter"> Winter</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Activity</button>
            </form>
        </section>

        <!-- Activities List -->
        <section class="card">
            <h3 style="margin-bottom: 1rem;">Catalog (<?= count($activities) ?> Activities)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Activity</th>
                        <th>Category</th>
                        <th>Slot</th>
                        <th>Duration</th>
                        <th>Cost (NPR)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['destination_name']) ?></td>
                            <td><strong><?= htmlspecialchars($act['name']) ?></strong></td>
                            <td><span class="badge"><?= htmlspecialchars($act['category']) ?></span></td>
                            <td><?= htmlspecialchars($act['preferred_slot']) ?></td>
                            <td><?= (float)$act['duration_hours'] ?> hrs</td>
                            <td>NPR <?= number_format((float)$act['cost_npr']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this activity?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $act['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
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
