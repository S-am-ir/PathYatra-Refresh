<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(); $where = []; $params = [];
if (!empty($input['destination_id'])) { $where[] = 'a.destination_id = ?'; $params[] = positiveId($input['destination_id']); }
if (!empty($input['category'])) { $where[] = 'a.category = ?'; $params[] = Validator::choice($input['category'], Validator::CATEGORIES, 'category'); }
$q = db()->prepare('SELECT a.*, d.name AS destination_name FROM activities a JOIN destinations d ON d.id = a.destination_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY a.name, a.id');
$q->execute($params); jsonSuccess($q->fetchAll());
