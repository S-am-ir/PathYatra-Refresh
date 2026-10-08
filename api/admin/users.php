<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/api.php';
apiRequest(['GET'], 'admin');
jsonSuccess(db()->query("SELECT u.id, u.name, u.email, u.status, u.created_at, COUNT(i.id) AS itinerary_count FROM users u LEFT JOIN itineraries i ON i.user_id = u.id WHERE u.role = 'traveler' GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll());
