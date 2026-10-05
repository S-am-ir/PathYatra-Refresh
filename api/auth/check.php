<?php
/**
 * YatraPath API - Session State Verification Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';

if (!isLoggedIn()) {
    jsonSuccess([
        'authenticated' => false,
        'user' => null
    ]);
}

jsonSuccess([
    'authenticated' => true,
    'user' => [
        'id' => getCurrentUserId(),
        'name' => getCurrentUserName(),
        'role' => getCurrentUserRole()
    ]
]);
