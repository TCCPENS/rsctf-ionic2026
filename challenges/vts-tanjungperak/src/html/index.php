<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';

$sql = "SELECT * FROM vessels WHERE 1=1";
$params = [];

if ($search_query !== '') {
    $sql .= " AND (vessel_name LIKE ? OR call_sign LIKE ? OR mmsi LIKE ? OR imo LIKE ?)";
    $term = "%{$search_query}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if ($filter_type !== '') {
    $sql .= " AND vessel_type = ?";
    $params[] = $filter_type;
}

$sql .= " ORDER BY id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vessels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_all = $db->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
$berthed_count = $db->query("SELECT COUNT(*) FROM vessels WHERE status = 'BERTHED'")->fetchColumn();
$inbound_count = $db->query("SELECT COUNT(*) FROM vessels WHERE status = 'INBOUND'")->fetchColumn();
$anchored_count = $db->query("SELECT COUNT(*) FROM vessels WHERE status = 'ANCHORED'")->fetchColumn();

$types = $db->query("SELECT DISTINCT vessel_type FROM vessels ORDER BY vessel_type ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="main-content">
    <div class="grid-container">
        <aside>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <?= vts_icon('radar', 16, '#00f2fe') ?>
                        <span>Radar Alur APBS</span>
                    </span>
                    <span class="badge badge-green">LIVE TRACKING</span>
                </div>

                <div class="radar-box">
                    <div class="radar-grid"></div>
                    <div class="radar-sweep"></div>
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
                        <div class="stat-val"><?= $total_all ?></div>
                        <div class="stat-lbl">Armada Aktif</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val text-green"><?= $berthed_count ?></div>
                        <div class="stat-lbl">Sandar Dermaga</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val text-cyan"><?= $inbound_count ?></div>
                        <div class="stat-lbl">Inbound Alur</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-val text-amber"><?= $anchored_count ?></div>
                        <div class="stat-lbl">Labuh Jangkar</div>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 12px; background: rgba(0,242,254,0.03); border: 1px dashed var(--border-color); border-radius: 6px; font-size: 11px;">
                    <strong>Navigasi Alur Pelayaran Barat Surabaya:</strong><br>
                    Kedalaman alur rata-rata: -13.0m LWS.<br>
                    Kapal dengan sarat air lebih dari 11m wajib menggunakan asistensi dua kapal tunda.
                </div>
            </div>
        </aside>

        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <?= vts_icon('ship', 16, '#00f2fe') ?>
                        <span>Monitoring Pergerakan Kapal Real-Time</span>
                    </span>
                    <span style="font-size: 11px; color: var(--text-muted);">Sinkronisasi Terakhir: <?= date('d M Y, H:i:s') ?> WIB</span>
                </div>

                <form method="GET" action="index.php" class="filter-bar">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="q" class="form-input" placeholder="Cari nama kapal, MMSI, Call Sign, IMO..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div style="width: 200px;">
                        <select name="type" class="form-input" onchange="this.form.submit()">
                            <option value="">Semua Tipe Kapal</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-cyan">
                        <?= vts_icon('search', 14, '#07111e') ?>
                        <span>Cari</span>
                    </button>
                    <?php if ($search_query !== '' || $filter_type !== ''): ?>
                        <a href="index.php" class="btn btn-outline">Reset Filter</a>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="vts-table">
                        <thead>
                            <tr>
                                <th>Nama Kapal / Identitas</th>
                                <th>MMSI / IMO</th>
                                <th>Tipe Kapal</th>
                                <th>Bendera</th>
                                <th>Dimensi (Draft/LOA)</th>
                                <th>Tambatan / Posisi</th>
                                <th>Tujuan</th>
                                <th>Jadwal Status</th>
                                <th>Operasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vessels)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        Tidak ada data kapal yang sesuai dengan filter pencarian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vessels as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($row['vessel_name']) ?></strong><br>
                                            <small style="color: var(--text-muted); font-family: var(--font-mono);"><?= htmlspecialchars($row['call_sign']) ?></small>
                                        </td>
                                        <td style="font-family: var(--font-mono);">
                                            <span class="text-cyan"><?= htmlspecialchars($row['mmsi']) ?></span><br>
                                            <small style="color: var(--text-muted);"><?= htmlspecialchars($row['imo']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['vessel_type']) ?></td>
                                        <td><?= htmlspecialchars($row['flag_country']) ?></td>
                                        <td style="font-family: var(--font-mono);">
                                            <?= number_format($row['draught'], 1) ?>m / <?= number_format($row['length_overall'], 1) ?>m
                                        </td>
                                        <td><?= htmlspecialchars($row['berth_location']) ?></td>
                                        <td><?= htmlspecialchars($row['destination']) ?></td>
                                        <td style="font-size: 11px;"><?= htmlspecialchars($row['eta_etd']) ?></td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
