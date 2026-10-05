<?php
/**
 * YatraPath - CORS & Security Headers
 */
declare(strict_types=1);

function handleCors(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

    // Allow local development ports (Vite, Apache)
    header("Access-Control-Allow-Origin: {$origin}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    // Handle preflight OPTIONS request
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

handleCors();
