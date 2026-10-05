<?php
/**
 * YatraPath — Admin Destination Management
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Handle Destination Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $region = $_POST['region'] ?? 'Hilly';
        $description = trim($_POST['description'] ?? '');
        $avgCost = (float)($_POST['avg_cost_per_day'] ?? 3000);
        $seasons = implode(',', $_POST['seasons'] ?? ['Spring', 'Autumn']);
        $lat = (float)($_POST['latitude'] ?? 27.7172);
        $lng = (float)($_POST['longitude'] ?? 85.3240);

        if (!empty($name) && !empty($description)) {
            $stmt = $db->prepare("
                INSERT INTO destinations (name, region, description, avg_cost_per_day, suitable_seasons, latitude, longitude)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $region, $description, $avgCost, $seasons, $lat, $lng]);
            $message = "Destination '{$name}' added successfully.";
        } else {
            $error = "Name and description are required.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM destinations WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Destination deleted successfully.";
        }
    }
}

$destinations = $db->query("SELECT * FROM destinations ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Destinations — Yatra Admin</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block; }
        input, select, textarea { width: 100%; padding: 0.65rem; border-radius: 6px; border: 1px solid var(--border); background: #0f172a; color: #fff; }
        .btn { padding: 0.65rem 1.25rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; padding: 0.35rem 0.65rem; font-size: 0.8rem; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); }
        .alert-success { background: #059669; color: #fff; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-error { background: #ef4444; color: #fff; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>Yatra Admin</h2>
        <nav class="sidebar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="destinations.php" class="active">Destinations</a>
            <a href="activities.php">Activities</a>
            <a href="users.php">Travelers</a>
            <a href="analytics.php">Analytics</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Log Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Manage Destinations</h1>
        </header>

        <?php if ($message): ?><div class="alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Add Destination Form -->
        <section class="card">
            <h3 style="margin-bottom: 1rem;">Add New Destination</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div>
                        <label>Destination Name</label>
                        <input type="text" name="name" placeholder="e.g. Bandipur" required>
                    </div>
                    <div>
                        <label>Region</label>
                        <select name="region">
                            <option value="Himalayan">Himalayan</option>
                            <option value="Hilly" selected>Hilly</option>
                            <option value="Terai">Terai</option>
                        </select>
                    </div>
                    <div>
                        <label>Avg Cost / Day (NPR)</label>
                        <input type="number" name="avg_cost_per_day" value="3500" required>
                    </div>
                    <div>
                        <label>Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" value="27.9300">
                    </div>
                    <div>
                        <label>Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" value="84.4100">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Brief overview of the destination..." required></textarea>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Suitable Seasons</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Spring" checked> Spring</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Summer"> Summer</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Autumn" checked> Autumn</label>
                    <label style="display:inline; margin-right:1rem;"><input type="checkbox" name="seasons[]" value="Winter"> Winter</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Destination</button>
            </form>
        </section>

        <!-- Destination List -->
        <section class="card">
            <h3 style="margin-bottom: 1rem;">Current Destinations (<?= count($destinations) ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Region</th>
                        <th>Cost / Day</th>
                        <th>Best Seasons</th>
                        <th>Coordinates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($destinations as $dest): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dest['name']) ?></strong></td>
                            <td><?= htmlspecialchars($dest['region']) ?></td>
                            <td>NPR <?= number_format((float)$dest['avg_cost_per_day']) ?></td>
                            <td><?= htmlspecialchars($dest['suitable_seasons']) ?></td>
                            <td><?= (float)$dest['latitude'] ?>, <?= (float)$dest['longitude'] ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this destination and associated activities?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $dest['id'] ?>">
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
