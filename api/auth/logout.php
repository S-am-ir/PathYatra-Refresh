<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
apiRequest(['POST']); $_SESSION = []; session_regenerate_id(true);
jsonSuccess(['csrf_token' => csrfToken()], 'Signed out');
