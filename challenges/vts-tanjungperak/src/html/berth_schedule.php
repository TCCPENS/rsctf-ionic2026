<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_berth') {
    $quay = trim($_POST['quay'] ?? '');
    $vessel = trim($_POST['vessel'] ?? '');
    $pilot = trim($_POST['pilot'] ?? '');
    $tugs = (int)($_POST['tugs'] ?? 1);
    $window = trim($_POST['window'] ?? '');

    if ($quay !== '' && $vessel !== '' && $pilot !== '') {
        $stmt_ins = $db->prepare("
            INSERT INTO berth_allocations (quay_name, zone, max_depth, allocated_vessel, pilot_name, tugs_assigned, schedule_window, operational_status)
            VALUES (?, 'Zona Operasional Baru', 12.0, ?, ?, ?, ?, 'QUEUED')
        ");
        $stmt_ins->execute([$quay, $vessel, $pilot, $tugs, $window]);
        $message = "Permohonan alokasi tambatan untuk {$vessel} berhasil didaftarkan ke sistem pemanduan.";
    }
}

$stmt_b = $db->query("SELECT * FROM berth_allocations ORDER BY id ASC");
$berths = $stmt_b->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('berth', 16, '#00f2fe') ?>
                <span>Jadwal Alokasi Tambatan & Dermaga Pelindo Sub Regional Jawa</span>
            </span>
            <span class="badge badge-blue">BERTH OCCUPANCY</span>
        </div>

        <?php if ($message): ?>
            <div style="background: rgba(16,185,129,0.1); border: 1px solid var(--accent-green); color: #34d399; padding: 10px 14px; border-radius: 6px; font-size: 12px; margin-bottom: 16px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="vts-table">
                <thead>
                    <tr>
                        <th>Nama Dermaga / Kade</th>
                        <th>Zona Terminal</th>
                        <th>Kedalaman Maksimal</th>
                        <th>Kapal yang Bertambat</th>
                        <th>Pandu Jaga</th>
                        <th>Asistensi Tugboat</th>
                        <th>Jendela Waktu Operasi</th>
                        <th>Status Dermaga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($berths as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['quay_name']) ?></strong></td>
                            <td><?= htmlspecialchars($b['zone']) ?></td>
                            <td style="font-family: var(--font-mono); color: var(--accent-cyan);"><?= number_format($b['max_depth'], 1) ?> m LWS</td>
                            <td><strong><?= htmlspecialchars($b['allocated_vessel']) ?></strong></td>
                            <td><?= htmlspecialchars($b['pilot_name']) ?></td>
                            <td style="font-family: var(--font-mono);"><?= (int)$b['tugs_assigned'] ?> Unit</td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($b['schedule_window']) ?></td>
                            <td>
                                <?php
                                $b_badge = 'badge-green';
                                if ($b['operational_status'] === 'OCCUPIED') $b_badge = 'badge-blue';
                                if ($b['operational_status'] === 'RESERVED') $b_badge = 'badge-amber';
                                if ($b['operational_status'] === 'QUEUED') $b_badge = 'badge-amber';
                                ?>
                                <span class="badge <?= $b_badge ?>"><?= htmlspecialchars($b['operational_status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('ship', 16, '#00f2fe') ?>
                <span>Formulir Pengajuan Pelayanan Tambatan & Pemanduan Kapal</span>
            </span>
            <span style="font-size: 11px; color: var(--text-muted);">Divisi Pelayanan Kapal & Terminal Tanjung Perak</span>
        </div>

        <form method="POST" action="berth_schedule.php">
            <input type="hidden" name="action" value="request_berth">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Dermaga Tujuan:</label>
                    <input type="text" name="quay" class="form-input" placeholder="e.g. Dermaga Jamrud Selatan 03" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Kapal & Call Sign:</label>
                    <input type="text" name="vessel" class="form-input" placeholder="e.g. MV Samudera Pasifik (YDXA)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Perwira Pandu yang Ditugaskan:</label>
                    <input type="text" name="pilot" class="form-input" placeholder="e.g. Capt. Wahyu Hidayat" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Kapal Tunda (Tugboat):</label>
                    <select name="tugs" class="form-input">
                        <option value="1">1 Unit (Draught &lt; 7m)</option>
                        <option value="2" selected>2 Unit (Draught 7m - 10m)</option>
                        <option value="3">3 Unit (Draught &gt; 10m / VLCC)</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Estimasi Waktu Pemanduan & Sandar:</label>
                    <input type="text" name="window" class="form-input" placeholder="e.g. 18:00 - 02:00 WIB" required>
                </div>
            </div>
            <button type="submit" class="btn btn-cyan">
                <?= vts_icon('check', 14, '#07111e') ?>
                <span>Daftarkan Alokasi Tambatan</span>
            </button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
