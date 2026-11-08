<?php
$db_file = __DIR__ . '/data/vts.db';
$db_dir = dirname($db_file);

if (!is_dir($db_dir)) {
    @mkdir($db_dir, 0755, true);
}

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("
        CREATE TABLE IF NOT EXISTS vessels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_name TEXT NOT NULL,
            call_sign TEXT NOT NULL,
            mmsi TEXT NOT NULL,
            imo TEXT NOT NULL,
            vessel_type TEXT NOT NULL,
            flag_country TEXT NOT NULL,
            draught REAL NOT NULL,
            length_overall REAL NOT NULL,
            berth_location TEXT NOT NULL,
            destination TEXT NOT NULL,
            eta_etd TEXT NOT NULL,
            status TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS berth_allocations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quay_name TEXT NOT NULL,
            zone TEXT NOT NULL,
            max_depth REAL NOT NULL,
            allocated_vessel TEXT NOT NULL,
            pilot_name TEXT NOT NULL,
            tugs_assigned INTEGER NOT NULL,
            schedule_window TEXT NOT NULL,
            operational_status TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS nav_warnings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            notice_number TEXT NOT NULL,
            fairway_sector TEXT NOT NULL,
            hazard_type TEXT NOT NULL,
            details TEXT NOT NULL,
            effective_until TEXT NOT NULL,
            severity_level TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS hydro_telemetry (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            station_code TEXT NOT NULL,
            location_name TEXT NOT NULL,
            tide_level REAL NOT NULL,
            current_velocity REAL NOT NULL,
            current_direction INTEGER NOT NULL,
            wind_speed REAL NOT NULL,
            wind_direction TEXT NOT NULL,
            wave_height REAL NOT NULL,
            baro_pressure REAL NOT NULL,
            salinity REAL NOT NULL,
            recorded_at TEXT NOT NULL
        );
    ");

    $count_vessels = $db->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
    if ($count_vessels == 0) {
        $stmt = $db->prepare("
            INSERT INTO vessels (vessel_name, call_sign, mmsi, imo, vessel_type, flag_country, draught, length_overall, berth_location, destination, eta_etd, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $vessels_seed = [
            ['KM Dharma Rencana', 'YDBR', '525012345', 'IMO9123456', 'Ro-Ro Passenger', 'Indonesia', 4.2, 138.5, 'Jamrud Selatan 02', 'Lembar, Lombok', '10:30 WIB (Berthing)', 'BERTHED'],
            ['MV Meratus Kalabahi', 'PKLK', '525098761', 'IMO9234567', 'Container Carrier', 'Indonesia', 7.8, 162.0, 'Berlian Terminal 04', 'Makassar, Sulsel', '14:00 WIB (Inbound)', 'INBOUND'],
            ['MT Madura Petro', 'YCEQ', '525112233', 'IMO9345678', 'Oil Products Tanker', 'Indonesia', 8.5, 145.2, 'Nilam Jetty 01', 'Balikpapan, Kaltim', '08:15 WIB (Discharge)', 'BERTHED'],
            ['TB Bima Sena 01', 'YDBX', '525334455', 'IMO8956789', 'Harbor Tug', 'Indonesia', 3.1, 32.0, 'Kalimas Pangkalan', 'Tanjung Perak Escort', 'Standby Pilotage', 'STANDBY'],
            ['MV Ever Glory', '9V812', '563001928', 'IMO9456780', 'Container Ship', 'Singapore', 10.2, 294.1, 'Anchorage Buoy 3', 'Singapore', 'Pending Pilot Boat', 'ANCHORED'],
            ['KN Chundamani P.116', 'PLP1', '525000116', 'IMO8812345', 'Patrol Vessel', 'Indonesia', 2.8, 42.5, 'APBS Channel KM 12', 'Surabaya Fairway', 'Securing Fairway', 'PATROLLING'],
            ['KM Kirana VII', 'YDCK', '525445566', 'IMO9567891', 'Passenger Ferry', 'Indonesia', 4.5, 126.8, 'Gapura Surya Nusantara', 'Banjarmasin', '16:00 WIB (Departure)', 'BOARDING'],
            ['MV Oceanic Star', '3FYB', '352981002', 'IMO9678902', 'Bulk Cargo Carrier', 'Panama', 11.4, 225.0, 'APBS Fairway Outbound', 'Hong Kong', 'Outbound to Java Sea', 'OUTBOUND'],
            ['TB Pelindo Marine 08', 'PKTM', '525667788', 'IMO9012345', 'Escort Tug', 'Indonesia', 3.4, 34.5, 'Nilam Pangkalan', 'Surabaya Roadstead', 'Mooring Assist', 'STANDBY'],
            ['MT Gresik Gas', 'YDEG', '525889900', 'IMO9789013', 'LPG Tanker', 'Indonesia', 6.2, 118.0, 'Gresik Petro Jetty', 'Bontang', 'Loading Cargo', 'BERTHED'],
            ['KM Niki Sejahtera', 'YDNS', '525114477', 'IMO9198765', 'Ro-Ro Cargo', 'Indonesia', 4.1, 115.0, 'Kalimas Dermaga Timur', 'Labuan Bajo', 'Loading Vehicles', 'BERTHED'],
            ['MV Wan Hai 315', 'BLH8', '564019283', 'IMO9890124', 'Container Carrier', 'Singapore', 9.8, 213.0, 'Teluk Lamong Berth 02', 'Kaohsiung', 'Container Handling', 'BERTHED']
        ];

        foreach ($vessels_seed as $row) {
            $stmt->execute($row);
        }
    }

    $count_berths = $db->query("SELECT COUNT(*) FROM berth_allocations")->fetchColumn();
    if ($count_berths == 0) {
        $stmt_b = $db->prepare("
            INSERT INTO berth_allocations (quay_name, zone, max_depth, allocated_vessel, pilot_name, tugs_assigned, schedule_window, operational_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $berths_seed = [
            ['Dermaga Jamrud Selatan 02', 'Zona Konvensional', 10.5, 'KM Dharma Rencana', 'Capt. M. Ridwan', 2, '08:00 - 18:00 WIB', 'OCCUPIED'],
            ['Dermaga Jamrud Utara 01', 'Zona Konvensional', 9.5, 'KM Sabuk Nusantara 91', 'Capt. Sugiono', 1, '06:00 - 14:00 WIB', 'OCCUPIED'],
            ['Berlian Terminal Berth 04', 'Zona Petikemas Domestik', 11.0, 'MV Meratus Kalabahi', 'Capt. Aris Munandar', 2, '14:00 - 23:00 WIB', 'RESERVED'],
            ['Nilam Jetty Tanker 01', 'Zona Curah Cair', 11.5, 'MT Madura Petro', 'Capt. Joko Wahyono', 2, '07:30 - 20:00 WIB', 'OCCUPIED'],
            ['Dermaga Mirah 03', 'Zona Kargo Curah', 8.5, 'MV Sinar Kudus', 'Capt. Bambang Tri', 1, '11:00 - 21:00 WIB', 'RESERVED'],
            ['Teluk Lamong Berth 02', 'Zona Ramah Lingkungan', 14.0, 'MV Wan Hai 315', 'Capt. Hendra Gunawan', 3, '04:00 - 19:00 WIB', 'OCCUPIED'],
            ['Gapura Surya Nusantara 01', 'Terminal Penumpang Internasional', 9.0, 'KM Kirana VII', 'Capt. Agus Salim', 1, '13:00 - 17:00 WIB', 'BOARDING'],
            ['Kalimas Pangkalan Tug 01', 'Zona Pelayanan Armada', 5.0, 'TB Bima Sena 01', 'N/A', 0, '24 Hours Standby', 'STANDBY']
        ];

        foreach ($berths_seed as $b) {
            $stmt_b->execute($b);
        }
    }

    $count_nav = $db->query("SELECT COUNT(*) FROM nav_warnings")->fetchColumn();
    if ($count_nav == 0) {
        $stmt_n = $db->prepare("
            INSERT INTO nav_warnings (notice_number, fairway_sector, hazard_type, details, effective_until, severity_level)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $nav_seed = [
            ['NOTMAR-SUB-2026/11-042', 'APBS Fairway KM 07 - KM 09', 'Dredging Operations', 'Pengerukan alur pemeliharaan oleh Kapal Keruk TSB Bali 01. Saluran terbatas, jaga jarak aman minimal 500 meter.', '15 Nov 2026', 'HIGH'],
            ['NOTMAR-SUB-2026/11-043', 'Karang Kering Outer Approach', 'Buoy #5 Light Extinguished', 'Pelampung Suar No. 5 (07°04.12 S 112°42.18 E) dilaporkan padam. Navigasi malam hari harap ekstra waspada.', '10 Nov 2026', 'MEDIUM'],
            ['NOTMAR-SUB-2026/11-044', 'Selat Madura Sektor Timur', 'Subsea Cable Inspection', 'Survei kabel bawah laut transmisi Madura. Kapal dilarang membuang jangkar di radius 1 mil laut dari koordinat yang ditentukan.', '20 Nov 2026', 'HIGH'],
            ['NOTMAR-SUB-2026/11-045', 'Dermaga Ujung - Perak', 'Ferry Traffic Density', 'Peningkatan kepadatan penyeberangan kapal ferry rute Ujung-Kamal pada jam sibuk 06:30 - 08:30 WIB.', 'Permanent', 'LOW']
        ];

        foreach ($nav_seed as $n) {
            $stmt_n->execute($n);
        }
    }

    $count_hydro = $db->query("SELECT COUNT(*) FROM hydro_telemetry")->fetchColumn();
    if ($count_hydro == 0) {
        $stmt_h = $db->prepare("
            INSERT INTO hydro_telemetry (station_code, location_name, tide_level, current_velocity, current_direction, wind_speed, wind_direction, wave_height, baro_pressure, salinity, recorded_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $hydro_seed = [
            ['ST-KJ01', 'Stasiun Karang Jamuang (APBS Entrance)', 1.82, 1.4, 185, 14.2, 'SSW', 0.8, 1011.8, 32.4, date('Y-m-d H:i:s')],
            ['ST-TP02', 'Stasiun Menara Navigasi Tanjung Perak', 1.65, 0.9, 172, 11.5, 'S', 0.4, 1012.1, 31.8, date('Y-m-d H:i:s')],
            ['ST-TL03', 'Stasiun Muara Teluk Lamong', 1.70, 0.7, 160, 9.8, 'SE', 0.3, 1012.0, 30.5, date('Y-m-d H:i:s')],
            ['ST-GR04', 'Stasiun Dermaga Pelabuhan Gresik', 1.58, 1.1, 190, 12.0, 'S', 0.5, 1011.9, 31.2, date('Y-m-d H:i:s')]
        ];

        foreach ($hydro_seed as $h) {
            $stmt_h->execute($h);
        }
    }
} catch (Exception $e) {
    die("Database initialization error");
}
