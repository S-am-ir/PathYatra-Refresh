<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/validator.php';
require_once __DIR__ . '/../config/haversine.php';

/** Deterministic proposal pipeline. Monetary calculations use integer paisa. */
final class ItineraryPlanner {
    public static function season(string $date): string {
        $month = (int)substr($date, 5, 2);
        return match (true) { $month >= 3 && $month <= 5 => 'Spring', $month >= 6 && $month <= 9 => 'Summer', $month >= 10 && $month <= 11 => 'Autumn', default => 'Winter' };
    }

    public static function input(array $input): array {
        $ids = $input['destinations'] ?? null;
        if (!is_array($ids) || !$ids || count($ids) > 25) throw new InvalidArgumentException('Choose between 1 and 25 destinations.');
        foreach ($ids as &$id) {
            if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id < 1) throw new InvalidArgumentException('Destination IDs must be positive integers.');
            $id = (int)$id;
        }
        unset($id);
        $ids = array_values(array_unique($ids));
        $start = $input['start_date'] ?? '';
        $date = is_string($start) ? DateTimeImmutable::createFromFormat('!Y-m-d', $start) : false;
        if (!$date || $date->format('Y-m-d') !== $start || $start < date('Y-m-d') || $start > date('Y-m-d', strtotime('+2 years'))) throw new InvalidArgumentException('Choose a valid start date from today to two years ahead.');
        $days = filter_var($input['days'] ?? null, FILTER_VALIDATE_INT);
        if ($days === false || $days < count($ids) || $days > 30) throw new InvalidArgumentException('Allow at least one day per destination, up to 30 days.');
        $budget = round(Validator::number($input['budget'] ?? null, 'Budget', 1500, 99999999), 2);
        if ($budget / $days < 1500) throw new InvalidArgumentException('Allow at least NPR 1,500 per day for the estimated stay.');
        $interests = $input['interests'] ?? null;
        if (!is_array($interests) || !$interests || count($interests) > 6) throw new InvalidArgumentException('Choose at least one interest.');
        $interests = array_values(array_unique(array_map(fn($i) => Validator::choice($i, Validator::CATEGORIES, 'interest'), $interests)));
        $origin = null;
        if (isset($input['origin'])) {
            if (!is_array($input['origin'])) throw new InvalidArgumentException('Origin must contain latitude and longitude.');
            $origin = ['latitude' => Validator::number($input['origin']['latitude'] ?? null, 'Origin latitude', -90, 90), 'longitude' => Validator::number($input['origin']['longitude'] ?? null, 'Origin longitude', -180, 180), 'label' => Validator::text($input['origin']['label'] ?? 'Starting location', 'Origin label', 1, 100)];
        }
        return ['destinations' => $ids, 'start_date' => $start, 'days' => $days, 'budget' => $budget, 'interests' => $interests, 'origin' => $origin];
    }

    public static function build(array $input, array $destinations, array $activities, array $tiers, array $advisories): array {
        $lookup = array_column($destinations, null, 'id');
        $selected = [];
        foreach ($input['destinations'] as $id) {
            if (!isset($lookup[$id])) throw new InvalidArgumentException('A selected destination no longer exists. Refresh the catalog.');
            $selected[] = $lookup[$id];
        }
        $route = RouteOptimizer::optimizeRoute($selected, $input['origin']);
        $daily = intdiv((int)round($input['budget'] * 100), $input['days']);
        usort($tiers, fn($a, $b) => $a['min_daily_budget'] <=> $b['min_daily_budget']);
        $tier = null;
        foreach ($tiers as $option) if ($daily >= (int)round($option['min_daily_budget'] * 100)) $tier = $option;
        if (!$tier || $daily < (int)round($tier['nightly_cost'] * 100)) throw new DomainException('The accommodation catalog cannot support this daily budget.');
        $stay = (int)round($tier['nightly_cost'] * 100);

        // Conservative scheduling allowance, NOT road routing or a live travel-time estimate.
        $legs = []; $transferDays = 0;
        for ($i = 1; $i < count($route); $i++) {
            $a = $route[$i - 1]; $b = $route[$i];
            $distance = RouteOptimizer::calculateDistance((float)$a['latitude'], (float)$a['longitude'], (float)$b['latitude'], (float)$b['longitude']);
            $hours = ceil(($distance * 1.5 / 35) * 4) / 4;
            $reserved = $hours > 4 ? (int)ceil($hours / 8) : 0;
            $transferDays += $reserved;
            $legs[$i] = ['from' => $a['name'], 'to' => $b['name'], 'distance_km' => round($distance, 1), 'allowance_hours' => $hours, 'reserved_days' => $reserved];
        }
        $minimum = count($route) + $transferDays;
        if ($input['days'] < $minimum) throw new InvalidArgumentException("This route needs at least {$minimum} days including transfer allowances. Add days or choose fewer, closer destinations.");
        $visits = array_fill(0, count($route), 1);
        for ($extra = $input['days'] - $minimum, $i = 0; $extra > 0; $extra--, $i++) $visits[$i % count($route)]++;
        $schedule = [];
        foreach ($route as $i => $destination) {
            $leg = $legs[$i] ?? null;
            for ($j = 0; $j < ($leg['reserved_days'] ?? 0); $j++) $schedule[] = ['destination' => $destination, 'transfer' => $leg, 'transfer_only' => true, 'transfer_hours' => min(8, $leg['allowance_hours'] - $j * 8)];
            for ($j = 0; $j < $visits[$i]; $j++) $schedule[] = ['destination' => $destination, 'transfer' => ($j === 0 && $leg && !$leg['reserved_days']) ? $leg : null, 'transfer_only' => false];
        }
        $used = []; $days = []; $highlights = []; $total = 0; $warnings = [];
        foreach ($schedule as $index => $entry) {
            $date = (new DateTimeImmutable($input['start_date']))->modify("+{$index} days")->format('Y-m-d');
            $season = self::season($date); $destination = $entry['destination'];
            $suitable = array_filter($activities, fn($a) => (int)$a['destination_id'] === (int)$destination['id'] && in_array($season, explode(',', str_replace(' ', '', $a['suitable_seasons'])), true));
            foreach ($suitable as &$activity) $activity['score'] = in_array($activity['category'], $input['interests'], true) ? 10 : 0;
            unset($activity);
            usort($suitable, fn($a, $b) => ($b['score'] <=> $a['score']) ?: ($a['cost_npr'] <=> $b['cost_npr']) ?: ($a['id'] <=> $b['id']));
            $capacity = ['morning' => 4.0, 'afternoon' => 4.0, 'evening' => 3.0]; $slots = []; $remaining = $daily - $stay;
            if ($entry['transfer_only']) {
                foreach ($capacity as $name => $hours) $slots[$name] = self::slot('Transfer / rest en route to ' . $destination['name'], $name === 'evening' ? 0 : min(4, max(0, $entry['transfer_hours'] - ($name === 'afternoon' ? 4 : 0))));
            } else {
                if ($entry['transfer']) { $slots['morning'] = self::slot('Transfer from ' . $entry['transfer']['from'], $entry['transfer']['allowance_hours']); }
                foreach ($capacity as $name => $hours) {
                    if (isset($slots[$name])) continue;
                    $candidate = null;
                    foreach ([true, false] as $preferred) {
                        foreach ($suitable as $a) {
                            $duration = (float)$a['duration_hours']; $cost = (int)round((float)$a['cost_npr'] * 100);
                            $fits = $duration <= $hours || ($name === 'morning' && $duration <= 8 && !isset($slots['afternoon']));
                            if (isset($used[$a['id']]) || !$fits || $cost > $remaining || ($preferred && strtolower($a['preferred_slot']) !== $name)) continue;
                            $candidate = $a; break 2;
                        }
                    }
                    if ($candidate) {
                        $cost = (int)round($candidate['cost_npr'] * 100); $remaining -= $cost; $used[$candidate['id']] = true;
                        $slots[$name] = ['activity_id' => (int)$candidate['id'], 'activity' => $candidate['name'], 'duration' => min($hours, (float)$candidate['duration_hours']), 'total_duration' => (float)$candidate['duration_hours'], 'cost' => $cost / 100, 'category' => $candidate['category']];
                        if ($name === 'morning' && $candidate['duration_hours'] > 4) $slots['afternoon'] = self::slot('Continue: ' . $candidate['name'], (float)$candidate['duration_hours'] - 4);
                        if (count($highlights) < 4) $highlights[] = $candidate['name'];
                    } else $slots[$name] = self::slot('Free time / rest (no suitable unused activity within this allowance)', 0);
                }
                if (!$suitable) $warnings[] = $destination['name'] . ': no catalog activities match ' . $season . '; free time is retained.';
                if (!in_array($season, explode(',', str_replace(' ', '', $destination['suitable_seasons'])), true)) $warnings[] = $destination['name'] . ': ' . $season . ' is outside the destination’s recommended season tags.';
            }
            $dayTotal = $stay + array_sum(array_map(fn($s) => (int)round($s['cost'] * 100), $slots)); $total += $dayTotal;
            $days[] = ['day_number' => $index + 1, 'date' => $date, 'season' => $season, 'destination_id' => $entry['transfer_only'] ? null : (int)$destination['id'], 'destination' => $entry['transfer_only'] ? 'Transit to ' . $destination['name'] : $destination['name'], 'region' => $destination['region'], 'latitude' => (float)$destination['latitude'], 'longitude' => (float)$destination['longitude'], 'transfer' => $entry['transfer'], 'slots' => $slots, 'accommodation' => ['name' => $tier['description'], 'tier' => $tier['name'], 'cost' => $stay / 100], 'day_total' => $dayTotal / 100];
        }
        $season = self::season($input['start_date']);
        return ['title' => mb_substr(implode(' + ', array_column($route, 'name')) . ' · ' . $input['days'] . ' Days Journey', 0, 200), 'season' => $season, 'weather_advisory' => $advisories[$season] ?? '', 'warnings' => array_values(array_unique($warnings)), 'total_days' => $input['days'], 'start_date' => $input['start_date'], 'end_date' => end($days)['date'], 'interests' => $input['interests'], 'origin' => $input['origin'], 'highlights' => $highlights, 'budget_summary' => ['total_budget' => $input['budget'], 'total_estimated' => $total / 100, 'remaining' => round($input['budget'] - $total / 100, 2), 'tier' => $tier['name']], 'days' => $days, 'route_legs' => array_values($legs), 'travel_note' => 'Intercity time allowance = straight-line km × 1.5 ÷ 35 km/h, rounded up. Longer legs reserve transfer days. This is a scheduling heuristic, not road directions. Travel to the first stop, fares, meals, permits and actual availability require separate confirmation.', 'map_data' => ['destinations' => array_map(fn($d) => ['id' => (int)$d['id'], 'name' => $d['name'], 'latitude' => (float)$d['latitude'], 'longitude' => (float)$d['longitude']], $route)]];
    }

    private static function slot(string $name, float $duration): array { return ['activity_id' => null, 'activity' => $name, 'duration' => $duration, 'cost' => 0, 'category' => 'leisure']; }
}
