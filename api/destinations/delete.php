<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../lib/Catalog.php';
$input = apiRequest(['DELETE', 'POST'], 'admin');
$id = positiveId($input['id'] ?? $_GET['id'] ?? 0);
$q = db()->prepare('DELETE FROM destinations WHERE id = ?'); $q->execute([$id]);
if (!$q->rowCount()) jsonError('Record not found.', 404);
jsonSuccess(null, 'Record deleted');
