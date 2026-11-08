<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

$stmt_h = $db->query("SELECT * FROM hydro_telemetry ORDER BY id ASC");
$stations = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
$primary = $stations[0] ?? null;
?>

<main class="main-content">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('wave', 16, '#00f2fe') ?>
                <span>Telemetri Oseanografi Real-Time Stasiun Karang Jamuang (APBS-01)</span>
            </span>
            <span class="badge badge-green">LIVE SENSORS</span>
        </div>

        <?php if ($primary): ?>
            <div class="gauge-grid">
                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Pasang Surut (Tide Level)</span>
                        <?= vts_icon('gauge', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        +<?= number_format($primary['tide_level'], 2) ?><span class="gauge-unit">m MSL</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: <?= min(100, ($primary['tide_level'] / 2.5) * 100) ?>%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Tinggi Air Acuan Peta Alur: -13.0m LWS
                    </div>
                </div>

                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Kecepatan Arus (Current)</span>
                        <?= vts_icon('activity', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        <?= number_format($primary['current_velocity'], 1) ?><span class="gauge-unit">Kts</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: <?= min(100, ($primary['current_velocity'] / 3.0) * 100) ?>%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Arah Arus Permukaan: <?= (int)$primary['current_direction'] ?>° Selatan
                    </div>
                </div>

                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Kecepatan Angin (Wind Speed)</span>
                        <?= vts_icon('wind', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        <?= number_format($primary['wind_speed'], 1) ?><span class="gauge-unit">Kts</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: <?= min(100, ($primary['wind_speed'] / 30.0) * 100) ?>%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Arah Angin Rata-rata: <?= htmlspecialchars($primary['wind_direction']) ?>
                    </div>
                </div>

                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Tinggi Gelombang Signifikan</span>
                        <?= vts_icon('wave', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        <?= number_format($primary['wave_height'], 1) ?><span class="gauge-unit">Meter</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: <?= min(100, ($primary['wave_height'] / 2.0) * 100) ?>%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Kategori Laut: Tenang - Sedang
                    </div>
                </div>

                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Tekanan Udara Barometer</span>
                        <?= vts_icon('compass', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        <?= number_format($primary['baro_pressure'], 1) ?><span class="gauge-unit">hPa</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: 75%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Stabilitas Atmosfer: Normal
                    </div>
                </div>

                <div class="gauge-card">
                    <div class="gauge-header">
                        <span>Salinitas Air Laut</span>
                        <?= vts_icon('shield', 14, '#94a3b8') ?>
                    </div>
                    <div class="gauge-value">
                        <?= number_format($primary['salinity'], 1) ?><span class="gauge-unit">PSU</span>
                    </div>
                    <div class="gauge-bar-bg">
                        <div class="gauge-bar-fill" style="width: 65%;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--text-muted); margin-top: 8px;">
                        Sensor Konduktivitas Elektrik
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('radio', 16, '#00f2fe') ?>
                <span>Jaringan Stasiun Pemantauan Hidrometeorologi Selat Madura</span>
            </span>
            <span style="font-size: 11px; color: var(--text-muted);">BMKG Maritim Tanjung Perak &amp; Disnav Kelas I</span>
        </div>

        <div class="table-responsive">
            <table class="vts-table">
                <thead>
                    <tr>
                        <th>Kode Stasiun</th>
                        <th>Lokasi Sensor</th>
                        <th>Tinggi Pasut</th>
                        <th>Kecepatan Arus</th>
                        <th>Arah Arus</th>
                        <th>Kecepatan Angin</th>
                        <th>Arah Angin</th>
                        <th>Tinggi Gelombang</th>
                        <th>Tekanan Udara</th>
                        <th>Waktu Perekaman</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stations as $s): ?>
                        <tr>
                            <td><strong style="color: var(--accent-cyan); font-family: var(--font-mono);"><?= htmlspecialchars($s['station_code']) ?></strong></td>
                            <td><?= htmlspecialchars($s['location_name']) ?></td>
                            <td style="font-family: var(--font-mono);">+<?= number_format($s['tide_level'], 2) ?> m</td>
                            <td style="font-family: var(--font-mono);"><?= number_format($s['current_velocity'], 1) ?> Kts</td>
                            <td style="font-family: var(--font-mono);"><?= (int)$s['current_direction'] ?>°</td>
                            <td style="font-family: var(--font-mono);"><?= number_format($s['wind_speed'], 1) ?> Kts</td>
                            <td><?= htmlspecialchars($s['wind_direction']) ?></td>
                            <td style="font-family: var(--font-mono);"><?= number_format($s['wave_height'], 1) ?> m</td>
                            <td style="font-family: var(--font-mono);"><?= number_format($s['baro_pressure'], 1) ?> hPa</td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($s['recorded_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
