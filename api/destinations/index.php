<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
$input = apiRequest(); $where = []; $params = [];
foreach (['region' => Validator::REGIONS, 'season' => Validator::SEASONS, 'category' => Validator::CATEGORIES] as $key => $choices) {
    if (isset($input[$key]) && $input[$key] !== '') {
        $value = Validator::choice($input[$key], $choices, $key);
        $where[] = match ($key) { 'region' => 'd.region = ?', 'season' => 'FIND_IN_SET(?, d.suitable_seasons) > 0',
            'category' => 'EXISTS (SELECT 1 FROM activities a WHERE a.destination_id = d.id AND a.category = ?)' };
        $params[] = $value;
    }
}
if (!empty($input['search'])) { $where[] = '(d.name LIKE ? OR d.description LIKE ?)'; $search = Validator::text($input['search'], 'Search', 1, 150); $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%'; }
foreach (['min_budget' => '>=', 'max_budget' => '<='] as $key => $operator) {
    if (isset($input[$key]) && $input[$key] !== '') { $where[] = "d.avg_cost_per_day {$operator} ?"; $params[] = Validator::number($input[$key], 'Daily budget', 0, 1000000); }
}
if (isset($input['min_budget'], $input['max_budget']) && $input['min_budget'] !== '' && $input['max_budget'] !== '' && (float)$input['min_budget'] > (float)$input['max_budget']) jsonError('Minimum budget cannot exceed maximum budget.', 422);
$sql = 'SELECT d.* FROM destinations d' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.avg_rating DESC, d.name ASC';
$q = db()->prepare($sql); $q->execute($params); jsonSuccess($q->fetchAll());
