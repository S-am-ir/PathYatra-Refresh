<?php
/**
 * YatraPath — Traveler Login & Register Portal
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = $_GET['error'] ?? null;
$tab = $_GET['tab'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler Portal — YatraPath</title>
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
        body { background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .auth-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 440px; padding: 2.5rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 0.5rem; }
        .subtitle { text-align: center; color: var(--muted); font-size: 0.9rem; margin-bottom: 2rem; }
        
        .tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; }
        .tab-btn { flex: 1; text-align: center; padding: 0.75rem; text-decoration: none; color: var(--muted); font-weight: 600; font-size: 0.95rem; border-bottom: 2px solid transparent; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text); }
        input { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.95rem; outline: none; }
        input:focus { border-color: var(--primary); }
        .btn-submit { width: 100%; padding: 0.8rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; }
        .btn-submit:hover { background: var(--primary-dark); }
        .alert { padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1rem; background: #fee2e2; color: #dc2626; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--muted); text-decoration: none; font-size: 0.85rem; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="logo">YatraPath</div>
        <div class="subtitle">Plan, explore, and embark on your Nepal adventure</div>

        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=login" class="tab-btn <?= $tab === 'login' ? 'active' : '' ?>">Sign In</a>
            <a href="?tab=register" class="tab-btn <?= $tab === 'register' ? 'active' : '' ?>">Create Account</a>
        </div>

        <?php if ($tab === 'login'): ?>
            <!-- Login Form -->
            <form action="../api/auth/login.php" method="POST">
                <input type="hidden" name="redirect" value="/Yatra/user/dashboard.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="traveler@yatra.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Sign In</button>
            </form>
        <?php else: ?>
            <!-- Register Form -->
            <form action="../api/auth/register.php" method="POST">
                <input type="hidden" name="redirect" value="/Yatra/user/dashboard.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Suman Thapa" required>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email Address</label>
                    <input type="email" id="reg-email" name="email" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Password (min 8 chars)</label>
                    <input type="password" id="reg-password" name="password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" class="btn-submit">Register Account</button>
            </form>
        <?php endif; ?>

        <a href="../homepage.php" class="back-link">&larr; Return to YatraPath Home</a>
    </div>

</body>
</html>
