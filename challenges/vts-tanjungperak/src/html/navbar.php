<?php
require_once __DIR__ . '/icons.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surabaya Vessel Traffic Services - Tanjung Perak</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <div class="brand-wrapper">
            <div class="brand-logo">
                <?= vts_icon('anchor', 24, '#00f2fe') ?>
            </div>
            <div class="brand-title">
                <h1>VTS TANJUNG PERAK SURABAYA</h1>
                <p>Direktorat Jenderal Perhubungan Laut • Distrik Navigasi Tipe A Kelas I</p>
            </div>
        </div>

        <div class="live-metrics">
            <div class="metric-pill">
                <span class="pulse-dot"></span>
                <span>RADAR APBS: <strong>AKTIF</strong> (161.975 MHz)</span>
            </div>
            <div class="metric-pill">
                <?= vts_icon('wave', 14, '#00f2fe') ?>
                <span>Pasang Surut: <strong>+1.82 m MSL</strong></span>
            </div>
            <div class="metric-pill">
                <?= vts_icon('radio', 14, '#00f2fe') ?>
                <span>Stasiun: <strong>APBS-03 Karang Jamuang</strong></span>
            </div>
        </div>
    </div>
</header>

<nav class="nav-tabs">
    <div class="nav-tabs-inner">
        <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <?= vts_icon('radar', 15) ?>
            <span>Lalu Lintas Kapal</span>
        </a>
        <a href="berth_schedule.php" class="nav-link <?= $current_page == 'berth_schedule.php' ? 'active' : '' ?>">
            <?= vts_icon('berth', 15) ?>
            <span>Jadwal Dermaga</span>
        </a>
        <a href="hydro_meteo.php" class="nav-link <?= $current_page == 'hydro_meteo.php' ? 'active' : '' ?>">
            <?= vts_icon('wave', 15) ?>
            <span>Oseanografi & Cuaca</span>
        </a>
        <a href="navwarn.php" class="nav-link <?= $current_page == 'navwarn.php' ? 'active' : '' ?>">
            <?= vts_icon('warning', 15) ?>
            <span>Peringatan Navigasi</span>
        </a>
        <a href="manifest_logs.php" class="nav-link <?= $current_page == 'manifest_logs.php' ? 'active' : '' ?>">
            <?= vts_icon('log', 15) ?>
            <span>Audit Log Manifest</span>
        </a>
        <a href="console.php" class="nav-link <?= $current_page == 'console.php' ? 'active' : '' ?>">
            <?= vts_icon('terminal', 15) ?>
            <span>Konsol Operator</span>
        </a>
        <a href="health.php" target="_blank" class="nav-link">
            <?= vts_icon('activity', 15) ?>
            <span>SLA Diagnostik</span>
        </a>
    </div>
</nav>
