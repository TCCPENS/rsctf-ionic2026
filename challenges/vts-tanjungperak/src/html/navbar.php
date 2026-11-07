<?php
// Shared navigation bar for VTS Tanjung Perak Portal
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surabaya Vessel Traffic Services (VTS) - Tanjung Perak</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚓</text></svg>">
</head>
<body>

<header class="top-nav">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div class="brand-wrapper">
            <div class="brand-logo">⚓</div>
            <div class="brand-title">
                <h1>VTS TANJUNG PERAK SURABAYA</h1>
                <p>Direktorat Jenderal Perhubungan Laut • Distrik Navigasi Tipe A Kelas I</p>
            </div>
        </div>

        <div class="live-metrics">
            <div class="metric-pill">
                <span class="pulse-dot"></span>
                <span>VTS RADAR: <strong>ONLINE</strong> (161.975 MHz)</span>
            </div>
            <div class="metric-pill">
                <span>🌊 Pasang Surut: <strong>+1.82 m MSL</strong></span>
            </div>
            <div class="metric-pill">
                <span>📡 Stasiun: <strong>APBS-03 (Karang Jamuang)</strong></span>
            </div>
        </div>
    </div>
</header>

<nav class="nav-tabs">
    <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
        <span>🗺️</span> Radar & Lalu Lintas Kapal
    </a>
    <a href="manifest_logs.php" class="nav-link <?= $current_page == 'manifest_logs.php' ? 'active' : '' ?>">
        <span>📜</span> Log Manifest & AIS Telemetry
    </a>
    <a href="console.php" class="nav-link <?= $current_page == 'console.php' ? 'active' : '' ?>">
        <span>💻</span> Konsol Operator Stasiun
    </a>
    <a href="health.php" target="_blank" class="nav-link">
        <span>🩺</span> SLA Diagnostic (JSON)
    </a>
</nav>
