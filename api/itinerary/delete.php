<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(['DELETE', 'POST'], 'traveler'); $id = positiveId($input['id'] ?? $_GET['id'] ?? null);
$q = db()->prepare('DELETE FROM itineraries WHERE id = ? AND user_id = ?'); $q->execute([$id, getCurrentUserId()]);
if (!$q->rowCount()) jsonError('This itinerary was not found.', 404);
jsonSuccess(null, 'Itinerary deleted');
