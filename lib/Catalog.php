<?php
declare(strict_types=1);
function destinationFields(array $input): array {
    return [Validator::text($input['name'] ?? '', 'Name', 2, 150),
        Validator::choice($input['region'] ?? '', Validator::REGIONS, 'region'),
        Validator::text($input['description'] ?? '', 'Description', 10, 3000),
        Validator::number($input['avg_cost_per_day'] ?? null, 'Daily estimate', 0, 1000000),
        Validator::seasons($input['seasons'] ?? $input['suitable_seasons'] ?? []),
        Validator::number($input['latitude'] ?? null, 'Latitude', -90, 90),
        Validator::number($input['longitude'] ?? null, 'Longitude', -180, 180)];
}
function activityFields(array $input): array {
    $destination = positiveId($input['destination_id'] ?? 0, 'destination ID'); recordExists('destinations', $destination);
    return [$destination, Validator::text($input['name'] ?? '', 'Activity name', 2, 150),
        Validator::choice($input['category'] ?? '', Validator::CATEGORIES, 'category'),
        Validator::number($input['duration_hours'] ?? null, 'Duration in hours', 0.25, 8),
        Validator::number($input['cost_npr'] ?? null, 'Activity estimate', 0, 1000000),
        Validator::choice($input['preferred_slot'] ?? '', ['Morning', 'Afternoon', 'Evening'], 'time slot'),
        Validator::seasons($input['seasons'] ?? $input['suitable_seasons'] ?? [])];
}
function reviewEligibility(int $destination, int $user): ?array {
    $q = db()->prepare("SELECT i.end_date FROM itineraries i JOIN itin_days d ON d.itinerary_id = i.id WHERE i.user_id = ? AND i.status = 'completed' AND d.destination_id = ? AND i.end_date <= ? ORDER BY i.end_date DESC LIMIT 1");
    $q->execute([$user, $destination, date('Y-m-d')]); return $q->fetch() ?: null;
}
