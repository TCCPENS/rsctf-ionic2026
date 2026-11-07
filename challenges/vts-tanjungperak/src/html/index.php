<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

// Fetch vessels from SQLite
$stmt = $db->query("SELECT * FROM vessels ORDER BY id ASC");
$vessels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate metrics
$total_vessels = count($vessels);
$berthed_count = 0;
$inbound_count = 0;
$anchored_count = 0;

foreach ($vessels as $v) {
    if ($v['status'] == 'BERTHED') $berthed_count++;
    if ($v['status'] == 'INBOUND') $inbound_count++;
    if ($v['status'] == 'ANCHORED') $anchored_count++;
}
?>

<main class="main-content">
    <div class="grid-container">
        <!-- Sidebar: Radar & Harbor Telemetry -->
        <aside>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📡 Radar APBS & Selat Madura</span>
                    <span class="badge badge-green">LIVE AIS</span>
                </div>

                <div class="radar-box">
                    <div class="radar-grid"></div>
                    <div class="radar-sweep"></div>
                    
                    <!-- Simulated Vessel Radar Blips -->
                    <div class="radar-blip" style="top: 42%; left: 48%;" title="KM Dharma Rencana"></div>
                    <div class="radar-blip" style="top: 25%; left: 32%; background: #38bdf8; box-shadow: 0 0 8px #38bdf8;" title="MV Ever Glory"></div>
                    <div class="radar-blip" style="top: 68%; left: 55%;" title="MT Madura Petro"></div>
                    <div class="radar-blip" style="top: 35%; left: 60%; background: #fbbf24; box-shadow: 0 0 8px #fbbf24;" title="KN Chundamani P.116"></div>
                    <div class="radar-blip" style="top: 80%; left: 40%;" title="KM Kirana VII"></div>
                </div>

                <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-bottom: 12px;">
                    Jangkauan Radar: 24 NM • Base Station APBS-03 (Karang Jamuang)
                </div>

                <div class="stat-row">
                    <div class="stat-item">
                        <div class="stat-val"><?= $total_vessels ?></div>
                        <div class="stat-lbl">Kapal Terpantau</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val" style="color: var(--accent-green);"><?= $berthed_count ?></div>
                        <div class="stat-lbl">Sandar Dermaga</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val" style="color: #60a5fa;"><?= $inbound_count ?></div>
                        <div class="stat-lbl">Inbound Alur</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val" style="color: var(--accent-amber);"><?= $anchored_count ?></div>
                        <div class="stat-lbl">Labuh Jangkar</div>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 12px; background: rgba(0,242,254,0.03); border: 1px dashed var(--border-color); border-radius: 6px; font-size: 11px;">
                    <strong>Navigasi Alur Pelayaran Barat Surabaya:</strong><br>
                    Kedalaman alur rata-rata: -13.0m LWS.<br>
                    Kapal dengan sarat air (*draft*) &gt; 11m wajib menggunakan asistensi dua kapal tunda (*tugboat*).
                </div>
            </div>
        </aside>

        <!-- Main Column: Vessel Traffic Table -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🚢 Daftar Pergerakan & Pengawasan Kapal Real-Time</span>
                    <span style="font-size: 12px; color: var(--text-muted);">Diperbarui: <?= date('d M Y, H:i:s') ?> WIB</span>
                </div>

                <div class="table-responsive">
                    <table class="vts-table">
                        <thead>
                            <tr>
                                <th>Nama Kapal / Call Sign</th>
                                <th>MMSI</th>
                                <th>Tipe Kapal</th>
                                <th>Bendera</th>
                                <th>Sarat Air (Draft)</th>
                                <th>Lokasi / Tambatan</th>
                                <th>Jadwal / Status</th>
                                <th>Status Operasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vessels as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['vessel_name']) ?></strong><br>
                                        <small style="color: var(--text-muted); font-family: var(--font-mono);"><?= htmlspecialchars($row['call_sign']) ?></small>
                                    </td>
                                    <td style="font-family: var(--font-mono); color: var(--accent-cyan);">
                                        <?= htmlspecialchars($row['mmsi']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['vessel_type']) ?></td>
                                    <td><?= htmlspecialchars($row['flag_country']) ?></td>
                                    <td style="font-family: var(--font-mono);"><?= number_format($row['draught'], 1) ?> m</td>
                                    <td><?= htmlspecialchars($row['berth_location']) ?></td>
                                    <td style="font-size: 12px;"><?= htmlspecialchars($row['eta_etd']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-blue';
                                        if ($row['status'] == 'BERTHED') $badge_class = 'badge-green';
                                        if ($row['status'] == 'ANCHORED') $badge_class = 'badge-amber';
                                        if ($row['status'] == 'PATROLLING') $badge_class = 'badge-blue';
                                        if ($row['status'] == 'OUTBOUND') $badge_class = 'badge-amber';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
