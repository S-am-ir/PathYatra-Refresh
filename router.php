<?php
// Development router: expose only REST endpoints, never schema/config/test files.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/api/(?:[a-z]+/)?[a-z]+\.php$#', $path) && is_file(__DIR__ . $path)) return false;
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Open the React interface at http://localhost:5180.']);
