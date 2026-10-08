<?php
/**
 * YatraPath - Standard JSON Response & Request Parser Helpers
 */
declare(strict_types=1);

function jsonSuccess(mixed $data = null, string $message = 'Success', int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message = 'An error occurred', int $statusCode = 400, mixed $errors = null): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors'  => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST ?? [];
    }
    try { $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException $error) { jsonError('Request body must be valid JSON.', 422); }
    if (!is_array($decoded) || (array_is_list($decoded) && $decoded !== [])) jsonError('Request body must be a JSON object.', 422);
    return $decoded;
}
