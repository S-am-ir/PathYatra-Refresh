<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/ItineraryPlanner.php';
date_default_timezone_set('Asia/Kathmandu');
$checks = 0;
function check(bool $condition, string $message): void { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
function rejects(callable $fn, string $message): void { try { $fn(); } catch (InvalidArgumentException | DomainException $e) { check(true, $message); return; } check(false, $message); }
$dest = [
    ['id'=>1,'name'=>'West','latitude'=>28,'longitude'=>83,'region'=>'Hilly','suitable_seasons'=>'Spring,Summer,Autumn,Winter'],
    ['id'=>2,'name'=>'East','latitude'=>28,'longitude'=>85,'region'=>'Hilly','suitable_seasons'=>'Spring,Summer,Autumn,Winter'],
    ['id'=>3,'name'=>'Nearby','latitude'=>28.01,'longitude'=>85.01,'region'=>'Hilly','suitable_seasons'=>'Spring,Summer,Autumn,Winter']
];
$tiers = [['name'=>'Budget','min_daily_budget'=>1500,'nightly_cost'=>1500,'description'=>'Lodge'],['name'=>'Mid-Range','min_daily_budget'=>3500,'nightly_cost'=>3500,'description'=>'Hotel'],['name'=>'Luxury','min_daily_budget'=>10000.01,'nightly_cost'=>8000,'description'=>'Resort']];
$activities = [];
foreach ($dest as $d) foreach (['Morning','Afternoon','Evening'] as $i=>$slot) $activities[] = ['id'=>$d['id']*10+$i,'destination_id'=>$d['id'],'name'=>"Activity {$d['id']} {$i}",'duration_hours'=>2,'cost_npr'=>500,'category'=>'cultural','preferred_slot'=>$slot,'suitable_seasons'=>'Spring,Summer,Autumn,Winter'];
$input = ['destinations'=>[1,2], 'origin'=>['latitude'=>28,'longitude'=>85,'label'=>'Here'], 'start_date'=>date('Y-m-d'),'days'=>5,'budget'=>17500,'interests'=>['cultural']];
$clean = ItineraryPlanner::input($input);
check(ItineraryPlanner::input(array_replace($input,['budget'=>17500.005]))['budget']===17500.01,'Budget precision matches stored currency');
$plan = ItineraryPlanner::build($clean, $dest, $activities, $tiers, []);
check($plan['map_data']['destinations'][0]['id']===2, 'Origin must reorder a two-stop route');
check(count($plan['days'])===5, 'All requested days must be allocated');
check(count(array_filter($plan['days'],fn($d)=>$d['destination_id']===null))>=1,'Long transfers reserve days');
check($plan['budget_summary']['total_estimated']<=$input['budget'],'Budget cannot be exceeded');
check(abs(RouteOptimizer::calculateDistance(0,0,0,1)-111.1949)<.01,'Haversine reference distance');
check(RouteOptimizer::calculateDistance(1,1,1,1)===0.0,'Coincident points');
check(is_finite(RouteOptimizer::calculateDistance(90,0,-90,180)),'Antipodal distance remains finite');
foreach (['2027-03-01'=>'Spring','2027-05-31'=>'Spring','2027-06-01'=>'Summer','2027-09-30'=>'Summer','2027-10-01'=>'Autumn','2027-12-01'=>'Winter','2027-02-01'=>'Winter'] as $date=>$season) check(ItineraryPlanner::season($date)===$season,'Season boundary '.$date);
foreach ([['days'=>0],['days'=>1.5],['budget'=>1499],['destinations'=>[0]],['destinations'=>['1 OR 1=1']],['interests'=>['madeup']],['origin'=>['latitude'=>91,'longitude'=>0]],['start_date'=>'2027-02-30'],['start_date'=>'2020-01-01']] as $invalid) rejects(fn()=>ItineraryPlanner::input(array_replace($input,$invalid)),'Invalid request must be rejected');
rejects(fn()=>ItineraryPlanner::build(array_replace($clean,['days'=>2]),$dest,$activities,$tiers,[]),'Insufficient transfer days');
rejects(fn()=>ItineraryPlanner::build(array_replace($clean,['destinations'=>[999]]),$dest,$activities,$tiers,[]),'Missing destinations');
$long = [['id'=>100,'destination_id'=>1,'name'=>'Full-day hike','duration_hours'=>7,'cost_npr'=>100,'category'=>'adventure','preferred_slot'=>'Morning','suitable_seasons'=>'Autumn']];
$hikeInput = array_replace($clean,['destinations'=>[1],'start_date'=>'2027-10-01','days'=>1,'budget'=>3000,'interests'=>['adventure']]);
$hike = ItineraryPlanner::build($hikeInput,$dest,$long,$tiers,[]);
check($hike['days'][0]['slots']['morning']['duration']===4.0 && $hike['days'][0]['slots']['afternoon']['duration']===3.0,'Long activity reserves adjacent slots');
check($hike['days'][0]['slots']['afternoon']['cost']===0,'Continuation is not charged twice');
check($hike['days'][0]['slots']['evening']['activity_id']===null,'Activity cannot repeat');
$winter = ItineraryPlanner::build(array_replace($hikeInput,['start_date'=>'2027-12-01']),$dest,$long,$tiers,[]);
check(!$winter['highlights'] && $winter['warnings'],'Excluded seasonal activities cannot become highlights');
// Hand-calculated outcomes supplement the randomized invariant checks below.
$line = [];
foreach ([1=>0,2=>3,3=>1,4=>2] as $id=>$longitude) $line[] = ['id'=>$id,'name'=>'Stop '.$id,'latitude'=>0,'longitude'=>$longitude];
check(array_column(RouteOptimizer::optimizeRoute($line),'id') === [1,3,4,2], 'Four-stop nearest-neighbor route starts with first selection');
check(array_column(RouteOptimizer::optimizeRoute($line,['latitude'=>0,'longitude'=>3]),'id') === [2,4,3,1], 'Four-stop nearest-neighbor route starts closest to origin');
$tied = [['id'=>1,'latitude'=>0,'longitude'=>0],['id'=>2,'latitude'=>0,'longitude'=>1],['id'=>3,'latitude'=>0,'longitude'=>-1]];
check(array_column(RouteOptimizer::optimizeRoute($tied),'id') === [1,2,3], 'Equal distances preserve selection order');
$one = array_replace($clean,['destinations'=>[1],'origin'=>null,'days'=>1,'start_date'=>'2027-11-30','budget'=>3000]);
$ranked = [];
foreach ([[110,'nature',10],[113,'cultural',100],[112,'cultural',100],[111,'cultural',200]] as [$id,$category,$cost]) $ranked[] = ['id'=>$id,'destination_id'=>1,'name'=>'Rank '.$id,'duration_hours'=>2,'cost_npr'=>$cost,'category'=>$category,'preferred_slot'=>'Morning','suitable_seasons'=>'Autumn,Winter'];
$rankPlan = ItineraryPlanner::build($one,$dest,$ranked,$tiers,[]);
check($rankPlan['days'][0]['slots']['morning']['activity_id'] === 112, 'Interest matches outrank cheaper nonmatches; equal match cost breaks by ID');
check($rankPlan['days'][0]['slots']['afternoon']['activity_id'] === 113, 'Fallback keeps ranking and excludes already-used activity');
$naturePlan = ItineraryPlanner::build(array_replace($one,['interests'=>['nature']]),$dest,$ranked,$tiers,[]);
check($naturePlan['days'][0]['slots']['morning']['activity_id'] === 110, 'Changing interests changes the selected activity');
check($rankPlan === ItineraryPlanner::build($one,$dest,$ranked,$tiers,[]), 'Identical inputs and catalog produce identical plans');
foreach ([[1500,'Budget',1500],[3499.99,'Budget',1500],[3500,'Mid-Range',3500],[10000,'Mid-Range',3500],[10000.01,'Luxury',8000]] as [$budget,$tier,$stay]) {
    $boundary = ItineraryPlanner::build(array_replace($one,['budget'=>$budget]),$dest,[],$tiers,[]);
    check($boundary['budget_summary']['tier'] === $tier, 'Accommodation threshold '.$budget);
    check($boundary['days'][0]['accommodation']['cost'] == $stay, 'Stay cost at threshold '.$budget);
}
$seasonActivities = [
    ['id'=>201,'destination_id'=>1,'name'=>'Autumn only','duration_hours'=>2,'cost_npr'=>100,'category'=>'cultural','preferred_slot'=>'Morning','suitable_seasons'=>'Autumn'],
    ['id'=>202,'destination_id'=>1,'name'=>'Winter only','duration_hours'=>2,'cost_npr'=>100,'category'=>'cultural','preferred_slot'=>'Morning','suitable_seasons'=>'Winter']
];
$crossing = ItineraryPlanner::build(array_replace($one,['days'=>2,'budget'=>6000]),$dest,$seasonActivities,$tiers,[]);
check(array_column($crossing['days'],'date') === ['2027-11-30','2027-12-01'], 'Day dates cross the month boundary correctly');
check(array_column($crossing['days'],'season') === ['Autumn','Winter'], 'Season is recalculated for each travel day');
check($crossing['days'][0]['slots']['morning']['activity_id'] === 201 && $crossing['days'][1]['slots']['morning']['activity_id'] === 202, 'Activities follow each day season, not only the initial season');
$seasonDest = $dest;
$seasonDest[0]['suitable_seasons'] = 'Autumn';
$warningPlan = ItineraryPlanner::build(array_replace($one,['days'=>2,'budget'=>6000]),$seasonDest,$seasonActivities,$tiers,[]);
check(count($warningPlan['warnings']) === 1 && str_contains($warningPlan['warnings'][0],'Winter'), 'Destination season warning appears on the affected day');
$exhausted = ItineraryPlanner::build(array_replace($one,['days'=>30,'budget'=>45000]),$dest,$ranked,$tiers,[]);
check(count($exhausted['days']) === 30, 'Maximum-length plan has thirty days');
check($exhausted['budget_summary']['total_estimated'] === 45000, 'Minimum allowance charges only the stay when paid activities cannot fit');
check($exhausted['days'][29]['slots']['morning']['activity_id'] === null, 'Unavailable activities leave honest free time');
// Property checks across randomized budgets, durations, categories and available catalog records.
mt_srand(101);
for ($run=0;$run<160;$run++) {
    $generated=[];
    for ($i=1;$i<=18;$i++) $generated[]=['id'=>$i,'destination_id'=>1,'name'=>'Random '.$i,'duration_hours'=>mt_rand(1,32)/4,'cost_npr'=>mt_rand(0,500000)/100,'category'=>$i%2?'cultural':'nature','preferred_slot'=>['Morning','Afternoon','Evening'][$i%3],'suitable_seasons'=>'Spring,Summer,Autumn,Winter'];
    $days=mt_rand(1,8); $budget=mt_rand(150000,1800000)*$days/100;
    $result=ItineraryPlanner::build(array_replace($clean,['destinations'=>[1],'days'=>$days,'budget'=>$budget]),$dest,$generated,$tiers,[]);
    $seen=[]; $sum=0;
    foreach ($result['days'] as $day) {
        $sum+=$day['day_total']; check($day['day_total']<=floor($budget*100/$days)/100+.001,'Daily budget invariant');
        foreach ($day['slots'] as $slot=>$record) {
            check($record['duration']<=($slot==='evening'?3:4),'Slot duration invariant');
            if ($record['activity_id']) { check(!isset($seen[$record['activity_id']]),'No activity repeats'); $seen[$record['activity_id']]=true; }
        }
    }
    check(abs($sum-$result['budget_summary']['total_estimated'])<.001,'Summary equals day totals');
    check($sum<=$budget+.001,'Trip budget invariant');
}
echo "Planner: {$checks} assertions passed.\n";
