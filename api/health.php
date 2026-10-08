<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/api.php';
apiRequest(); db()->query('SELECT 1');
jsonSuccess(['status' => 'ready']);
