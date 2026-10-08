<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();
$schema = preg_replace('/^(CREATE DATABASE.*|USE .*);$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
$db->exec($schema);
$database = getenv('DB_NAME') ?: 'yatra_db';
$column = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
foreach (['plan_token' => 'VARCHAR(64) DEFAULT NULL', 'plan_json' => 'JSON DEFAULT NULL'] as $name => $definition) {
    $column->execute([$database, 'itineraries', $name]);
    if (!(int)$column->fetchColumn()) $db->exec("ALTER TABLE itineraries ADD COLUMN {$name} {$definition}");
}
$index = $db->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?');
$index->execute([$database, 'itineraries', 'plan_token']); $hasOriginal = (int)$index->fetchColumn();
$index->execute([$database, 'itineraries', 'unique_plan_token']);
if (!$hasOriginal && !(int)$index->fetchColumn()) $db->exec('ALTER TABLE itineraries ADD UNIQUE KEY unique_plan_token (plan_token)');
$index->execute([$database, 'reviews', 'unique_review_author_destination']);
if (!(int)$index->fetchColumn()) {
    // Keep each traveler's newest review when upgrading the older schema.
    $db->exec('DELETE a FROM reviews a JOIN reviews b ON a.user_id = b.user_id AND a.destination_id = b.destination_id AND a.id < b.id');
    $db->exec('ALTER TABLE reviews ADD UNIQUE KEY unique_review_author_destination (user_id, destination_id)');
}
$version = '20261008_functional';
$q = $db->prepare('SELECT version FROM schema_migrations WHERE version = ?'); $q->execute([$version]);
if (!$q->fetchColumn()) {
    $db->beginTransaction();
    try { $db->exec(file_get_contents(__DIR__ . '/seed.sql')); $q = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)'); $q->execute([$version]); $db->commit(); }
    catch (Throwable $e) { $db->rollBack(); throw $e; }
}
$db->exec('UPDATE destinations d SET avg_rating = COALESCE((SELECT ROUND(AVG(r.rating),2) FROM reviews r WHERE r.destination_id = d.id),0), total_reviews = (SELECT COUNT(*) FROM reviews r WHERE r.destination_id = d.id)');
echo "PathYatra schema and seed are ready. Existing user data is preserved.\n";
