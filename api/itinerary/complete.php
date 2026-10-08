<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['POST'], 'traveler'); $id = positiveId($input['id'] ?? null);
$q = db()->prepare('SELECT id, end_date FROM itineraries WHERE id = ? AND user_id = ?'); $q->execute([$id, getCurrentUserId()]);
$trip = $q->fetch(); if (!$trip) jsonError('This itinerary was not found.', 404);
if ($trip['end_date'] > date('Y-m-d')) jsonError('You can mark a trip complete once its final travel date has arrived.', 422);
$q = db()->prepare("UPDATE itineraries SET status = 'completed' WHERE id = ? AND user_id = ?"); $q->execute([$id, getCurrentUserId()]);
jsonSuccess(null, 'Trip marked complete. You can now review the destinations you visited.');
