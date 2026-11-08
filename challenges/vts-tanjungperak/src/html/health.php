<?php
header('Content-Type: application/json');

$response = [
    'status' => 'healthy',
    'service' => 'vts_tanjung_perak',
    'station' => 'VTS-SUB-APBS03',
    'checks' => [],
    'timestamp' => time()
];

$errors = [];

try {
    require_once __DIR__ . '/db.php';
    $vessel_count = $db->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
    $response['checks']['database'] = [
        'status' => 'ok',
        'vessels_tracked' => (int)$vessel_count
    ];
} catch (Exception $e) {
    $errors[] = 'Database failure: ' . $e->getMessage();
    $response['checks']['database'] = ['status' => 'error', 'detail' => $e->getMessage()];
}

$log_sample = __DIR__ . '/logs/apbs_traffic_202611.log';
if (file_exists($log_sample) && is_readable($log_sample)) {
    $response['checks']['telemetry_logs'] = ['status' => 'ok'];
} else {
    $errors[] = 'Telemetry log archive missing or unreadable';
    $response['checks']['telemetry_logs'] = ['status' => 'error'];
}

$heartbeat_file = '/var/run/vts/cron_heartbeat';
if (file_exists($heartbeat_file)) {
    $last_hb = (int)@file_get_contents($heartbeat_file);
    $diff = time() - $last_hb;
    if ($diff > 180) {
        $errors[] = "Cron maintenance heartbeat stale ({$diff}s ago)";
        $response['checks']['cron_heartbeat'] = ['status' => 'stale', 'elapsed_seconds' => $diff];
    } else {
        $response['checks']['cron_heartbeat'] = ['status' => 'ok', 'last_pulse_seconds_ago' => $diff];
    }
} else {
    $sync_script = '/opt/vts/scripts/tide_depth_sync.sh';
    if (file_exists($sync_script)) {
        $response['checks']['cron_heartbeat'] = ['status' => 'initializing'];
    } else {
        $response['checks']['cron_heartbeat'] = ['status' => 'dev_standalone'];
    }
}

if (!empty($errors)) {
    http_response_code(500);
    $response['status'] = 'unhealthy';
    $response['errors'] = $errors;
} else {
    http_response_code(200);
}

echo json_encode($response, JSON_PRETTY_PRINT);
