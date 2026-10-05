<?php
/**
 * YatraPath — Admin Entry & Login Portal
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

if (isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-box {
            background: #1e293b;
            padding: 2.5rem;
            border-radius: 16px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }
        .login-box h2 {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .login-box p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #cbd5e1;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #fff;
            box-sizing: border-box;
            font-size: 0.95rem;
        }
        input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .btn-submit {
            width: 100%;
            padding: 0.8rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            background: #ef4444;
            color: #fff;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>YatraPath Admin</h2>
        <p>Sign in to manage destinations, activities, and system analytics</p>

        <?php if ($error): ?>
            <div class="alert">
                <?= htmlspecialchars($error === 'invalid' ? 'Invalid email or password.' : ($error === 'forbidden' ? 'Admin access required.' : $error)) ?>
            </div>
        <?php endif; ?>

        <form action="../api/auth/login.php" method="POST">
            <input type="hidden" name="redirect" value="/Yatra/admin/dashboard.php">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" value="admin@yatra.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-submit">Sign In to Admin</button>
        </form>

        <a href="../homepage.php" class="back-link">&larr; Back to YatraPath Home</a>
    </div>

</body>
</html>
