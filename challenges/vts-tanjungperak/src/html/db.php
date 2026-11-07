<?php
// ========================================================
// VTS Tanjung Perak - Database Initialization (SQLite)
// ========================================================

$db_file = __DIR__ . '/data/vts.db';
$db_dir = dirname($db_file);

if (!is_dir($db_dir)) {
    @mkdir($db_dir, 0755, true);
}

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create vessels table
    $db->exec("
        CREATE TABLE IF NOT EXISTS vessels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_name TEXT NOT NULL,
            call_sign TEXT NOT NULL,
            mmsi TEXT NOT NULL,
            vessel_type TEXT NOT NULL,
            flag_country TEXT NOT NULL,
            draught REAL NOT NULL,
            berth_location TEXT NOT NULL,
            eta_etd TEXT NOT NULL,
            status TEXT NOT NULL
        )
    ");

    // Populate initial vessels if empty
    $count = $db->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("
            INSERT INTO vessels (vessel_name, call_sign, mmsi, vessel_type, flag_country, draught, berth_location, eta_etd, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $seed = [
            ['KM Dharma Rencana', 'YDBR', '525012345', 'Ro-Ro Passenger', 'Indonesia', 4.2, 'Jamrud Selatan 02', '10:30 WIB (Berthing)', 'BERTHED'],
            ['MV Meratus Kalabahi', 'PKLK', '525098761', 'Container Cargo', 'Indonesia', 7.8, 'Berlian Terminal 04', '14:00 WIB (Inbound)', 'INBOUND'],
            ['MT Madura Petro', 'YCEQ', '525112233', 'Oil Products Tanker', 'Indonesia', 8.5, 'Nilam Jetty 01', '08:15 WIB (Discharge)', 'BERTHED'],
            ['TB Bima Sena 01', 'YDBX', '525334455', 'Harbor Tug', 'Indonesia', 3.1, 'Kalimas Pangkalan', 'Standby Pilotage', 'STANDBY'],
            ['MV Ever Glory', '9V812', '563001928', 'Container Ship', 'Singapore', 10.2, 'Anchorage Buoy 3', 'Pending Pilot Boat', 'ANCHORED'],
            ['KN Chundamani P.116', 'PLP1', '525000116', 'KPLP Patrol Ship', 'Indonesia', 2.8, 'APBS Channel KM 12', 'Securing Fairway', 'PATROLLING'],
            ['KM Kirana VII', 'YDCK', '525445566', 'Passenger / Ferry', 'Indonesia', 4.5, 'Gapura Surya Nusantara', '16:00 WIB (Departure)', 'BOARDING'],
            ['MV Oceanic Star', '3FYB', '352981002', 'Bulk Cargo Carrier', 'Panama', 11.4, 'APBS Fairway Outbound', 'Outbound to Java Sea', 'OUTBOUND']
        ];

        foreach ($seed as $row) {
            $stmt->execute($row);
        }
    }
} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
