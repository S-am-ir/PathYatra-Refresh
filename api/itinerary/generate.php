<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/ItineraryPlanner.php';
$input = ItineraryPlanner::input(apiRequest(['POST'], 'traveler'));
$marks = implode(',', array_fill(0, count($input['destinations']), '?'));
$q = db()->prepare("SELECT * FROM destinations WHERE id IN ({$marks})"); $q->execute($input['destinations']); $destinations = $q->fetchAll();
$q = db()->prepare("SELECT * FROM activities WHERE destination_id IN ({$marks})"); $q->execute($input['destinations']);
$plan = ItineraryPlanner::build($input, $destinations, $q->fetchAll(), db()->query('SELECT * FROM accommodation_tiers')->fetchAll(), db()->query('SELECT season, advisory FROM season_advisories')->fetchAll(PDO::FETCH_KEY_PAIR));
$plan['plan_token'] = bin2hex(random_bytes(32));
$plans = array_filter($_SESSION['generated_plans'] ?? [], fn($p) => $p['expires'] > time());
$plans[$plan['plan_token']] = ['expires' => time() + 43200, 'plan' => $plan];
$_SESSION['generated_plans'] = array_slice($plans, -10, null, true);
jsonSuccess($plan, 'Itinerary generated');
