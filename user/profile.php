<?php
/**
 * YatraPath — Traveler Profile Settings
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
    $name = trim($_POST['name'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (!empty($name)) {
        if (!empty($newPassword)) {
            if (mb_strlen($newPassword) >= 8) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $hash, $userId]);
                $_SESSION['name'] = $name;
                $message = "Profile and password updated successfully.";
            } else {
                $error = "Password must be at least 8 characters.";
            }
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $userId]);
            $_SESSION['name'] = $name;
            $message = "Profile name updated successfully.";
        }
    } else {
        $error = "Name cannot be empty.";
    }
}

$userStmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --card: #ffffff; --border: #e2e8f0; --text: #0f172a; --muted: #64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: #fff; border-bottom: 1px solid var(--border); }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.95rem; }
        .container { max-width: 600px; margin: 2.5rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 2rem; }
        label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.95rem; margin-bottom: 1.25rem; }
        .btn { width: 100%; padding: 0.8rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../homepage.php" class="logo">YatraPath</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="generator.php">Generator</a>
            <a href="itineraries.php">My Itineraries</a>
            <a href="profile.php" style="color: var(--primary); font-weight:600;">Profile</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Sign Out</a>
        </nav>
    </header>

    <main class="container">
        <h1 style="font-size: 1.6rem; margin-bottom: 1.5rem;">Account Settings</h1>

        <?php if ($message): ?><div style="background: #059669; color: #fff; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div style="background: #ef4444; color: #fff; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card">
            <form method="POST">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div>
                    <label>Email Address (Cannot be changed)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f1f5f9;">
                </div>
                <div>
                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" placeholder="••••••••" minlength="8">
                </div>
                <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.25rem;">
                    Account created on: <?= htmlspecialchars(date('M d, Y', strtotime($user['created_at']))) ?>
                </div>
                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>
    </main>

</body>
</html>
