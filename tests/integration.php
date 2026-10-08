<?php
declare(strict_types=1);
if (getenv('TEST_ALLOW_WRITES') !== '1') throw new RuntimeException('Set TEST_ALLOW_WRITES=1 only against a disposable/local test database.');
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();
$base = rtrim(getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8001', '/');
final class Client {
    public string $cookie = ''; public string $token = '';
    public function request(string $method, string $path, ?array $data = null, bool $csrf = true): array {
        global $base;
        $headers = ['Content-Type: application/json'];
        if ($this->cookie) $headers[] = 'Cookie: ' . $this->cookie;
        if ($csrf && $this->token) $headers[] = 'X-CSRF-Token: ' . $this->token;
        $ctx = stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$data === null ? '' : json_encode($data,JSON_THROW_ON_ERROR),'ignore_errors'=>true,'timeout'=>15]]);
        $raw = file_get_contents($base . '/api/' . $path, false, $ctx);
        if ($raw === false) throw new RuntimeException('HTTP server unavailable');
        $status = 0;
        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\S+ (\d+)#',$header,$m)) $status=(int)$m[1];
            if (preg_match('/^Set-Cookie: (pathyatra_session=[^;]+)/i',$header,$m)) $this->cookie=$m[1];
        }
        try { $response=json_decode($raw,true,512,JSON_THROW_ON_ERROR); } catch(Throwable $e) { throw new RuntimeException("Non-JSON {$path}: ".substr($raw,0,400)); }
        if (isset($response['data']['csrf_token'])) $this->token=$response['data']['csrf_token'];
        return [$status,$response];
    }
}
$count=0;
function expect(array $result, int $status, string $label): array { global $count; $count++; if ($result[0]!==$status) throw new RuntimeException("{$label}: expected {$status}, got {$result[0]} ".json_encode($result[1])); return $result[1]['data'] ?? []; }
$traveler=new Client; $other=new Client; $admin=new Client;
$users=[]; $customDest=null; $reviewDest=null; $trip=null;
$tag=bin2hex(random_bytes(6));
try {
    expect($traveler->request('GET','health.php'),200,'Health');
    expect($traveler->request('GET','itinerary/fetch.php'),401,'Anonymous protected endpoint');
    expect($traveler->request('GET','auth/check.php'),200,'CSRF bootstrap');
    expect($traveler->request('POST','auth/login.php',['email'=>'admin@yatra.com','password'=>'Admin@123'],false),403,'CSRF required');
    expect($traveler->request('POST','auth/register.php',['name'=>'Valid Name','email'=>'bad@example.com','password'=>"Invalid\0Password",'confirm_password'=>"Invalid\0Password"]),422,'Reject null-byte password');
    foreach ([$traveler,$other] as $i=>$client) {
        expect($client->request('GET','auth/check.php'),200,'Bootstrap');
        $u=expect($client->request('POST','auth/register.php',['name'=>'Test Traveler','email'=>"test_{$tag}_{$i}@example.com",'password'=>'Test@1234','confirm_password'=>'Test@1234']),201,'Registration'); $users[]=(int)$u['user']['id'];
    }
    expect($traveler->request('POST','auth/register.php',['name'=>'Test Traveler','email'=>"test_{$tag}_0@example.com",'password'=>'Test@1234','confirm_password'=>'Test@1234']),409,'Duplicate email');
    expect($traveler->request('GET','admin/users.php'),403,'Traveler cannot manage users');
    $catalog=expect($traveler->request('GET','destinations/index.php'),200,'Catalog');
    if (count($catalog)<25) throw new RuntimeException('Catalog requires at least 25 destinations');
    $place=(int)$catalog[0]['id'];
    expect($traveler->request('GET','destinations/index.php?season=Bad'),422,'Invalid filter');
    expect($traveler->request('GET','destinations/detail.php?id=9999999'),404,'Missing destination');
    $filtered=expect($traveler->request('GET','destinations/index.php?region=Terai&season=Winter&max_budget=3000&category=cultural'),200,'Combined filters');
    foreach ($filtered as $d) if ($d['region']!=='Terai'||$d['avg_cost_per_day']>3000||!str_contains($d['suitable_seasons'],'Winter')) throw new RuntimeException('Filter mismatch');
    expect($traveler->request('POST','reviews/submit.php',['destination_id'=>$place,'rating'=>5,'comment'=>'Before completion test']),403,'No unverified review');
    $request=['destinations'=>[$place],'start_date'=>date('Y-m-d',strtotime('+7 days')),'days'=>2,'budget'=>10000,'interests'=>['cultural']];
    expect($traveler->request('POST','itinerary/generate.php',array_replace($request,['destinations'=>[$place,9999999]])),422,'Reject missing selected destination');
    expect($traveler->request('GET','itinerary/generate.php'),405,'Method enforcement');
    expect($traveler->request('POST','itinerary/generate.php',array_replace($request,['interests'=>['unknown']])),422,'Interest validation');
    $start=microtime(true); $plan=expect($traveler->request('POST','itinerary/generate.php',$request),200,'Generate'); $elapsed=microtime(true)-$start;
    if ($elapsed>3) throw new RuntimeException('Generation exceeds proposal 3-second target');
    $saved=expect($traveler->request('POST','itinerary/save.php',['plan_token'=>$plan['plan_token'],'budget_summary'=>['total_estimated'=>0],'days'=>[]]),201,'Save trusted snapshot'); $trip=(int)$saved['itinerary_id'];
    $loaded=expect($traveler->request('GET',"itinerary/fetch.php?id={$trip}"),200,'Reopen');
    if ($loaded['budget_summary']!==$plan['budget_summary']||$loaded['days']!==$plan['days']||$loaded['map_data']!==$plan['map_data']) throw new RuntimeException('Saved plan mismatch/tampering accepted');
    $duplicate=expect($traveler->request('POST','itinerary/save.php',['plan_token'=>$plan['plan_token']]),200,'Idempotent save');
    if ($duplicate['itinerary_id']!==$trip) throw new RuntimeException('Duplicate save created another trip');
    expect($other->request('GET',"itinerary/fetch.php?id={$trip}"),404,'Other user cannot read trip');
    expect($other->request('DELETE',"itinerary/delete.php?id={$trip}"),404,'Other user cannot delete trip');
    expect($other->request('POST','itinerary/save.php',['plan_token'=>$plan['plan_token']]),410,'Other user cannot save generated token');
    expect($traveler->request('POST','itinerary/complete.php',['id'=>$trip]),422,'Future trip cannot complete');
    expect($admin->request('GET','auth/check.php'),200,'Admin bootstrap');
    expect($admin->request('POST','auth/login.php',['email'=>'admin@yatra.com','password'=>'wrong']),401,'Wrong password');
    expect($admin->request('POST','auth/login.php',['email'=>'admin@yatra.com','password'=>'Admin@123']),200,'Admin login');
    $analytics=expect($admin->request('GET','admin/analytics.php'),200,'Analytics');
    if ($analytics['metrics']['total_activities']<80) throw new RuntimeException('At least 80 activities required');
    $fields=['name'=>'Test Place '.$tag,'region'=>'Hilly','description'=>'Integration test catalog destination.','avg_cost_per_day'=>2400,'latitude'=>27.7,'longitude'=>85.3,'seasons'=>['Spring','Autumn','Winter']];
    expect($traveler->request('POST','destinations/create.php',$fields),403,'Admin write protection');
    expect($admin->request('POST','destinations/create.php',array_replace($fields,['latitude'=>99])),422,'Coordinate validation');
    $created=expect($admin->request('POST','destinations/create.php',$fields),201,'Create destination'); $customDest=(int)$created['id'];
    expect($admin->request('POST','destinations/update.php',array_replace($fields,['id'=>$customDest,'name'=>'Updated '.$tag])),200,'Update destination');
    $activity=['destination_id'=>$customDest,'name'=>'Test activity','category'=>'cultural','duration_hours'=>7,'cost_npr'=>500,'preferred_slot'=>'Morning','seasons'=>['Autumn']];
    $created=expect($admin->request('POST','activities/create.php',$activity),201,'Create activity'); $activityId=$created['id'];
    expect($admin->request('POST','activities/update.php',array_replace($activity,['id'=>$activityId,'duration_hours'=>2])),200,'Update activity');
    expect($admin->request('DELETE','activities/delete.php',['id'=>$activityId]),200,'Delete activity');
    expect($admin->request('POST','admin/status.php',['user_id'=>$users[1],'status'=>'inactive']),200,'Deactivate traveler');
    expect($other->request('GET','itinerary/fetch.php'),401,'Inactive session revoked');
    expect($admin->request('POST','admin/status.php',['user_id'=>$users[1],'status'=>'active']),200,'Reactivate traveler');
    // Historical fixture exercises completion/review without weakening date checks for real users.
    $pdo->prepare('UPDATE itineraries SET start_date=?, end_date=? WHERE id=?')->execute([date('Y-m-d',strtotime('-3 days')),date('Y-m-d',strtotime('-2 days')),$trip]);
    $pdo->prepare('UPDATE itin_days SET destination_id=? WHERE itinerary_id=?')->execute([$customDest,$trip]); $reviewDest=$customDest;
    expect($traveler->request('POST','itinerary/complete.php',['id'=>$trip]),200,'Complete historical trip');
    $detail=expect($traveler->request('GET',"destinations/detail.php?id={$customDest}"),200,'Review eligibility');
    if (!$detail['can_review']) throw new RuntimeException('Completed visit must allow review');
    expect($traveler->request('POST','reviews/submit.php',['destination_id'=>$customDest,'rating'=>4,'comment'=>'Verified completed visit review']),200,'Create verified review');
    expect($traveler->request('POST','reviews/submit.php',['destination_id'=>$customDest,'rating'=>5,'comment'=>'Updated completed visit review']),200,'Update same review');
    $detail=expect($traveler->request('GET',"destinations/detail.php?id={$customDest}"),200,'Rating recomputed');
    if ((int)$detail['total_reviews']!==1||(float)$detail['avg_rating']!==5.0) throw new RuntimeException('Review uniqueness/average incorrect');
    expect($traveler->request('POST','reviews/submit.php',['destination_id'=>$customDest,'rating'=>6,'comment'=>'Invalid rating test']),422,'Rating range');
    expect($traveler->request('DELETE',"itinerary/delete.php?id={$trip}"),200,'Delete owned trip');
    expect($traveler->request('GET',"itinerary/fetch.php?id={$trip}"),404,'Deleted trip unavailable');
    expect($admin->request('DELETE','destinations/delete.php',['id'=>$customDest]),200,'Delete destination'); $customDest=null;
    expect($traveler->request('POST','auth/logout.php'),200,'Logout');
    expect($traveler->request('GET','itinerary/fetch.php'),401,'Session ended');
    echo "API: {$count} checks passed; generation " . round($elapsed*1000) . " ms.\n";
} finally {
    if ($customDest) $pdo->prepare('DELETE FROM destinations WHERE id=?')->execute([$customDest]);
    foreach ($users as $uid) $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
}
