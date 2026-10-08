<?php
declare(strict_types=1);

// CLI-only helpers for the disposable Compose browser test database.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (getenv('TEST_ALLOW_WRITES') !== '1') {
    fwrite(STDERR, "Run only on a test database with TEST_ALLOW_WRITES=1.\n");
    exit(1);
}
require __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();
$action = $argv[1] ?? '';
$email = $argv[2] ?? '';
if (!preg_match('/^browser_[0-9]+@example\.com$/', $email)) {
    throw new InvalidArgumentException('Expected an isolated browser test account.');
}
if ($action === 'expire') {
    $id = filter_var($argv[3] ?? '', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) throw new InvalidArgumentException('Invalid itinerary ID.');
    $statement = $db->prepare('UPDATE itineraries i JOIN users u ON u.id=i.user_id SET i.end_date=? WHERE i.id=? AND u.email=?');
    $statement->execute([date('Y-m-d', strtotime('-2 days')), $id, $email]);
    if ($statement->rowCount() !== 1) throw new RuntimeException('Test itinerary not found.');
} elseif ($action === 'cleanup') {
    $name = $argv[3] ?? '';
    if (!preg_match('/^Browser catalog [0-9]+$/', $name)) {
        throw new InvalidArgumentException('Expected an isolated browser test destination.');
    }
    $db->prepare('DELETE FROM users WHERE email=?')->execute([$email]);
    $db->prepare('DELETE FROM destinations WHERE name=?')->execute([$name]);
    $db->exec('UPDATE destinations d SET avg_rating=COALESCE((SELECT ROUND(AVG(r.rating),2) FROM reviews r WHERE r.destination_id=d.id),0),total_reviews=(SELECT COUNT(*) FROM reviews r WHERE r.destination_id=d.id)');
} else {
    throw new InvalidArgumentException('Unknown browser fixture action.');
}
echo "Browser fixture {$action} completed.\n";
