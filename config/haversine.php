<?php
/**
 * YatraPath - Haversine Distance & Nearest-Neighbor Route Optimization
 */
declare(strict_types=1);

class RouteOptimizer {
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Calculate Great Circle distance between two coordinates in kilometers using Haversine formula
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Order a sequence of destinations using the Nearest-Neighbor heuristic
     *
     * @param array $destinations Array of associative arrays with 'id', 'name', 'latitude', 'longitude'
     * @return array Ordered sequence of destinations
     */
    public static function optimizeRoute(array $destinations): array {
        if (count($destinations) <= 2) {
            return $destinations;
        }

        $unvisited = $destinations;
        $ordered = [];

        // Start with the first destination selected by traveler
        $current = array_shift($unvisited);
        $ordered[] = $current;

        while (!empty($unvisited)) {
            $nearestIdx = -1;
            $shortestDistance = INF;

            foreach ($unvisited as $idx => $candidate) {
                $dist = self::calculateDistance(
                    (float)$current['latitude'],
                    (float)$current['longitude'],
                    (float)$candidate['latitude'],
                    (float)$candidate['longitude']
                );

                if ($dist < $shortestDistance) {
                    $shortestDistance = $dist;
                    $nearestIdx = $idx;
                }
            }

            if ($nearestIdx !== -1) {
                $current = $unvisited[$nearestIdx];
                $ordered[] = $current;
                unset($unvisited[$nearestIdx]);
                $unvisited = array_values($unvisited); // reindex
            } else {
                break;
            }
        }

        return $ordered;
    }
}
