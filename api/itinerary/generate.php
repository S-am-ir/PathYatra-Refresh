<?php
/**
 * PathYatra — rule-based itinerary generator.
 * Uses season filtering, interest ranking, a nearest-neighbor route heuristic,
 * and a greedy day-by-day activity assignment with estimated costs.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/validator.php';
require_once __DIR__ . '/../../config/haversine.php';

$input = getJsonInput();

$destIds = $input['destinations'] ?? [];
$startDateStr = $input['start_date'] ?? date('Y-m-d');
$totalDays = (int)($input['days'] ?? 5);
$totalBudget = (float)($input['budget'] ?? 25000.0);
$interests = $input['interests'] ?? ['cultural', 'nature'];

if (empty($destIds) || !is_array($destIds)) {
    jsonError('Please select at least one destination.', 422);
}
$destIds = array_values(array_unique(array_map('intval', $destIds)));
if (in_array(0, $destIds, true) || count($destIds) > 30) {
    jsonError('Please select valid destinations.', 422);
}

if ($totalDays < 1 || $totalDays > 30) {
    jsonError('Trip duration must be between 1 and 30 days.', 422);
}

if ($totalBudget < 3000) {
    jsonError('Budget must be at least NPR 3,000.', 422);
}

if (!is_string($startDateStr)) {
    jsonError('Please provide a valid start date in YYYY-MM-DD format.', 422);
}
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $startDateStr);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $startDateStr) {
    jsonError('Please provide a valid start date in YYYY-MM-DD format.', 422);
}

try {
    $db = Database::getInstance()->getConnection();

    // ==========================================
    // Phase 1: Determine Season & Weather Advisory
    // ==========================================
    $month = (int)date('n', strtotime($startDateStr));
    $season = 'Spring';
    $advisory = 'Favorable weather with blooming rhododendrons and mild temperatures.';

    if (in_array($month, [3, 4, 5], true)) {
        $season = 'Spring';
        $advisory = 'Spring season: Clear skies, moderate temperatures, and blooming rhododendrons.';
    } elseif (in_array($month, [6, 7, 8, 9], true)) {
        $season = 'Summer';
        $advisory = 'Monsoon season: Expect intermittent rainfall. Lush green scenery, carry rain gear.';
    } elseif (in_array($month, [10, 11], true)) {
        $season = 'Autumn';
        $advisory = 'Peak Autumn season: Crystal-clear Himalayan views, ideal temperatures for trekking.';
    } else {
        $season = 'Winter';
        $advisory = 'Winter season: Crisp mountain views, cold mornings and evenings. Warm layers required.';
    }

    // ==========================================
    // Fetch Selected Destinations
    // ==========================================
    $placeholders = implode(',', array_fill(0, count($destIds), '?'));
    $destStmt = $db->prepare("SELECT * FROM destinations WHERE id IN ({$placeholders})");
    $destStmt->execute($destIds);
    $selectedDests = $destStmt->fetchAll();

    if (empty($selectedDests)) {
        jsonError('Selected destinations could not be found.', 404);
    }

    if ($totalDays < count($selectedDests)) {
        jsonError('Allow at least one day for each selected destination.', 422);
    }

    // Maintain initial selection order for start
    $destLookup = [];
    foreach ($selectedDests as $d) {
        $destLookup[(int)$d['id']] = $d;
    }
    $orderedSelected = [];
    foreach ($destIds as $did) {
        if (isset($destLookup[(int)$did])) {
            $orderedSelected[] = $destLookup[(int)$did];
        }
    }

    // ==========================================
    // Phase 5: Route Optimization (Nearest Neighbor)
    // ==========================================
    $optimizedDestinations = RouteOptimizer::optimizeRoute($orderedSelected);

    // ==========================================
    // Phase 2 & 3: Query, Season Filter, and Score Activities
    // ==========================================
    $actStmt = $db->prepare("
        SELECT * FROM activities 
        WHERE destination_id IN ({$placeholders})
    ");
    $actStmt->execute($destIds);
    $allActivities = $actStmt->fetchAll();

    // Group activities by destination and score them
    $scoredActivitiesByDest = [];
    $allScoredList = [];

    foreach ($allActivities as $act) {
        $suitableSeasons = array_map('trim', explode(',', $act['suitable_seasons']));
        // Check if current season matches (or if summer/monsoon variant)
        $isSuitable = false;
        foreach ($suitableSeasons as $s) {
            if (strcasecmp($s, $season) === 0 || ($season === 'Summer' && stripos($s, 'summer') !== false)) {
                $isSuitable = true;
                break;
            }
        }

        if (!$isSuitable) {
            continue; // Prune activities unsuitable for the season
        }

        // Calculate score based on interest matches
        $score = 0;
        $actCategory = strtolower($act['category']);
        foreach ($interests as $interest) {
            $userInterest = strtolower((string)$interest);
            if ($actCategory === $userInterest) {
                $score += 10;
            } elseif (str_contains($actCategory, $userInterest) || str_contains($userInterest, $actCategory)) {
                $score += 5;
            }
        }

        $act['score'] = $score;
        $did = (int)$act['destination_id'];
        $scoredActivitiesByDest[$did][] = $act;
        $allScoredList[] = $act;
    }

    // Sort activities in each destination by score DESC, cost ASC
    foreach ($scoredActivitiesByDest as $did => &$acts) {
        usort($acts, function($a, $b) {
            if ($b['score'] === $a['score']) {
                return $a['cost_npr'] <=> $b['cost_npr'];
            }
            return $b['score'] <=> $a['score'];
        });
    }
    unset($acts);

    // ==========================================
    // Phase 4: Budget Allocation & Tiering
    // ==========================================
    $perDayBudget = $totalBudget / $totalDays;
    if ($perDayBudget < 3500) {
        $tierName = 'Budget';
        $accomName = 'Standard Guest House / Lodge';
        $accomCost = 1500.0;
    } elseif ($perDayBudget <= 10000) {
        $tierName = 'Mid-Range';
        $accomName = 'Comfort 3-Star Hotel / Boutique Resort';
        $accomCost = 3500.0;
    } else {
        $tierName = 'Luxury';
        $accomName = 'Luxury Heritage Resort / 5-Star Suite';
        $accomCost = 8000.0;
    }

    if ($perDayBudget < $accomCost) {
        jsonError('The budget is too low for the estimated stay over this trip length.', 422);
    }
    $dailyActivityBudget = max(0.0, $perDayBudget - $accomCost);

    // ==========================================
    // Phase 6: Even Day Assignment
    // ==========================================
    $numDests = count($optimizedDestinations);
    $dayAllocations = [];

    if ($numDests === 1) {
        $dayAllocations[0] = $totalDays;
    } else {
        // Base: at least 1 day per destination
        $remainingDays = $totalDays - $numDests;
        for ($i = 0; $i < $numDests; $i++) {
            $dayAllocations[$i] = 1;
        }

        // Distribute extra days evenly in route order.
        while ($remainingDays > 0) {
            for ($i = 0; $i < $numDests && $remainingDays > 0; $i++) {
                $dayAllocations[$i]++;
                $remainingDays--;
            }
        }
    }

    // Map each day number to its destination
    $dayToDestMap = [];
    $currentDay = 1;
    for ($i = 0; $i < $numDests; $i++) {
        $allocated = $dayAllocations[$i];
        for ($k = 0; $k < $allocated && $currentDay <= $totalDays; $k++) {
            $dayToDestMap[$currentDay] = $optimizedDestinations[$i];
            $currentDay++;
        }
    }

    // ==========================================
    // Phase 7: Greedy Time Slot Filling (Morning, Afternoon, Evening)
    // ==========================================
    $itineraryDays = [];
    $totalEstimatedCost = 0.0;
    $usedActivityIds = [];
    $highlights = [];

    $startDateTimestamp = strtotime($startDateStr);

    for ($dayNum = 1; $dayNum <= $totalDays; $dayNum++) {
        $dayDate = date('Y-m-d', strtotime('+' . ($dayNum - 1) . ' days', $startDateTimestamp));
        $dest = $dayToDestMap[$dayNum] ?? $optimizedDestinations[0];
        $did = (int)$dest['id'];

        $destActivities = $scoredActivitiesByDest[$did] ?? [];
        $remainingDayBudget = $dailyActivityBudget;
        $daySlots = [];

        foreach (['Morning', 'Afternoon', 'Evening'] as $slotName) {
            $assignedActivity = null;

            foreach ($destActivities as $candidate) {
                $actId = (int)$candidate['id'];
                if (in_array($actId, $usedActivityIds, true)) {
                    continue;
                }

                // Match slot preference or fallback
                if (strcasecmp($candidate['preferred_slot'], $slotName) === 0) {
                    if ((float)$candidate['cost_npr'] <= $remainingDayBudget) {
                        $assignedActivity = $candidate;
                        $usedActivityIds[] = $actId;
                        $remainingDayBudget -= (float)$candidate['cost_npr'];
                        break;
                    }
                }
            }

            // Fallback: If no slot-specific activity found, take highest scored affordable unused activity
            if (!$assignedActivity) {
                foreach ($destActivities as $candidate) {
                    $actId = (int)$candidate['id'];
                    if (in_array($actId, $usedActivityIds, true)) {
                        continue;
                    }
                    if ((float)$candidate['cost_npr'] <= $remainingDayBudget) {
                        $assignedActivity = $candidate;
                        $usedActivityIds[] = $actId;
                        $remainingDayBudget -= (float)$candidate['cost_npr'];
                        break;
                    }
                }
            }

            if ($assignedActivity) {
                $daySlots[strtolower($slotName)] = [
                    'activity_id'    => (int)$assignedActivity['id'],
                    'activity'       => $assignedActivity['name'],
                    'category'       => $assignedActivity['category'],
                    'duration'       => (float)$assignedActivity['duration_hours'],
                    'cost'           => (float)$assignedActivity['cost_npr']
                ];

                if (count($highlights) < 4 && $assignedActivity['score'] >= 10) {
                    $highlights[] = $assignedActivity['name'];
                }
            } else {
                $daySlots[strtolower($slotName)] = [
                    'activity_id'    => null,
                    'activity'       => 'Free time / Explore local sights',
                    'category'       => 'leisure',
                    'duration'       => 2.0,
                    'cost'           => 0.0
                ];
            }
        }

        $dayActivitiesCost = array_sum(array_column($daySlots, 'cost'));
        $dayTotal = $accomCost + $dayActivitiesCost;
        $totalEstimatedCost += $dayTotal;

        $itineraryDays[] = [
            'day_number'     => $dayNum,
            'date'           => $dayDate,
            'destination_id' => $did,
            'destination'    => $dest['name'],
            'region'         => $dest['region'],
            'slots'          => $daySlots,
            'accommodation'  => [
                'name' => $accomName,
                'tier' => $tierName,
                'cost' => $accomCost
            ],
            'day_total'      => $dayTotal
        ];
    }

    // Top highlights fallback if none collected
    if (empty($highlights)) {
        foreach (array_slice($allActivities, 0, 3) as $act) {
            $highlights[] = $act['name'];
        }
    }

    // ==========================================
    // Phase 8: Assemble Structured Response
    // ==========================================
    $destNames = array_column($optimizedDestinations, 'name');
    $title = implode(' + ', $destNames) . " · {$totalDays} Days Journey";

    $mapData = [
        'destinations' => array_map(function($d) {
            return [
                'id'        => (int)$d['id'],
                'name'      => $d['name'],
                'latitude'  => (float)$d['latitude'],
                'longitude' => (float)$d['longitude']
            ];
        }, $optimizedDestinations),
        'route' => array_map(function($d) {
            return [(float)$d['latitude'], (float)$d['longitude']];
        }, $optimizedDestinations)
    ];

    $responsePayload = [
        'title'            => $title,
        'season'           => $season,
        'weather_advisory' => $advisory,
        'total_days'       => $totalDays,
        'start_date'       => $startDateStr,
        'end_date'         => date('Y-m-d', strtotime('+' . ($totalDays - 1) . ' days', $startDateTimestamp)),
        'budget_summary'   => [
            'total_budget'     => $totalBudget,
            'total_estimated'  => $totalEstimatedCost,
            'remaining'        => max(0.0, $totalBudget - $totalEstimatedCost),
            'tier'             => $tierName
        ],
        'days'             => $itineraryDays,
        'highlights'       => array_unique($highlights),
        'map_data'         => $mapData
    ];

    jsonSuccess($responsePayload, 'Itinerary generated successfully');

} catch (Throwable $e) {
    jsonError('Itinerary generation error: ' . $e->getMessage(), 500);
}
