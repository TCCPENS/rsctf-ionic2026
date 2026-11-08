<?php
require_once __DIR__ . '/navbar.php';

$available_logs = [
    'apbs_traffic_202611.log' => 'Laporan Lalu Lintas Radar & AIS (November 2026)',
    'pilotage_berthing.log' => 'Jadwal Pemanduan & Sandar Kapal (Pelindo Marine)',
    'sensor_bathymetry.log' => 'Telemetri Sensor Kedalaman Alur & Pasang Surut'
];

$selected_source = isset($_GET['source']) ? trim($_GET['source']) : 'apbs_traffic_202611.log';

$log_dir = __DIR__ . '/logs/';
$log_path = $log_dir . $selected_source;

$content = null;
$error = null;

if (file_exists($log_path) && !is_dir($log_path)) {
    $content = file_get_contents($log_path);
} else {
    $direct = @file_get_contents($selected_source);
    if ($direct !== false) {
        $content = $direct;
    } else {
        $error = "Berkas log \"" . htmlspecialchars($selected_source) . "\" tidak ditemukan atau gagal dimuat.";
    }
}
?>

<main class="main-content">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('log', 16, '#00f2fe') ?>
                <span>Audit Log Manifest Kapal &amp; Telemetri Stasiun</span>
            </span>
            <span class="badge badge-blue">TELEMETRY ARCHIVE</span>
        </div>

        <form method="GET" action="manifest_logs.php" style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pilih Arsip Log:</label>
            <select name="source" class="form-input" style="max-width: 440px;" onchange="this.form.submit()">
                <?php foreach ($available_logs as $file => $label): ?>
                    <option value="<?= htmlspecialchars($file) ?>" <?= $selected_source == $file ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($file) ?>)
                    </option>
                <?php endforeach; ?>
                <?php if (!array_key_exists($selected_source, $available_logs)): ?>
                    <option value="<?= htmlspecialchars($selected_source) ?>" selected>
                        Custom Source: <?= htmlspecialchars($selected_source) ?>
                    </option>
                <?php endif; ?>
            </select>
            <button type="submit" class="btn btn-cyan">
                <?= vts_icon('search', 14, '#07111e') ?>
                <span>Muat Log</span>
            </button>
        </form>

        <?php if ($error): ?>
            <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--accent-red); color: #f87171; padding: 12px 16px; border-radius: 6px; font-size: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <?= vts_icon('warning', 15, '#ef4444') ?>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if ($content !== null): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                <span>Sumber: <?= htmlspecialchars($selected_source) ?> (<?= strlen($content) ?> bytes)</span>
                <span>Enkoding: UTF-8 Plaintext</span>
            </div>
            <div class="log-box"><?= htmlspecialchars($content) ?></div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
