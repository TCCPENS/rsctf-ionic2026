<?php
session_start();

$conf_file = '/opt/vts/conf/ais_station.conf';
$station_user = 'vts_operator';
$station_pass = 'JangkarPerak2026!Madura';

if (file_exists($conf_file)) {
    $parsed = @parse_ini_file($conf_file, true);
    if (!empty($parsed['operator_auth']['username'])) {
        $station_user = $parsed['operator_auth']['username'];
    }
    if (!empty($parsed['operator_auth']['password'])) {
        $station_pass = $parsed['operator_auth']['password'];
    }
}

$auth_error = null;
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['vts_operator_auth']);
    unset($_SESSION['vts_operator_user']);
    header('Location: console.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === $station_user && $p === $station_pass) {
        $_SESSION['vts_operator_auth'] = true;
        $_SESSION['vts_operator_user'] = $u;
        header('Location: console.php');
        exit;
    } else {
        $auth_error = 'Otentikasi Gagal: Kredensial operator stasiun VTS tidak valid.';
    }
}

$is_logged_in = !empty($_SESSION['vts_operator_auth']);

$cmd_output = null;
$executed_cmd = null;

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $executed_cmd = trim($_POST['cmd']);
    if ($executed_cmd !== '') {
        $safe_arg = escapeshellarg($executed_cmd);
        $full_cmd = "sudo -u vts_operator bash -c {$safe_arg} 2>&1";
        $cmd_output = shell_exec($full_cmd);
        if ($cmd_output === null) {
            $cmd_output = shell_exec("bash -c {$safe_arg} 2>&1");
        }
    }
}

require_once __DIR__ . '/navbar.php';
?>

<main class="main-content">
    <?php if (!$is_logged_in): ?>
        <div class="card" style="max-width: 460px; margin: 40px auto;">
            <div class="card-header">
                <span class="card-title">
                    <?= vts_icon('shield', 16, '#fbbf24') ?>
                    <span>Otentikasi Petugas Jaga VTS</span>
                </span>
                <span class="badge badge-amber">RESTRICTED</span>
            </div>

            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 20px;">
                Konsol telemetri internal stasiun APBS-03 Karang Jamuang. Akses dibatasi hanya untuk Petugas Operasional VTS Tanjung Perak.
            </p>

            <?php if ($auth_error): ?>
                <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--accent-red); color: #f87171; padding: 10px 14px; border-radius: 6px; font-size: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <?= vts_icon('warning', 14, '#ef4444') ?>
                    <span><?= htmlspecialchars($auth_error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="console.php">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label class="form-label">Operator Username / Call Sign ID:</label>
                    <input type="text" name="username" class="form-input" placeholder="e.g. vts_operator" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Operator Security Passcode:</label>
                    <input type="password" name="password" class="form-input" placeholder="Masukkan kata sandi stasiun..." required>
                </div>
                <button type="submit" class="btn btn-cyan" style="width: 100%; justify-content: center;">
                    <?= vts_icon('terminal', 14, '#07111e') ?>
                    <span>Masuk ke Konsol Stasiun</span>
                </button>
            </form>

            <div style="margin-top: 16px; font-size: 11px; color: var(--text-muted); text-align: center;">
                Kredensial operator tersinkronisasi via berkas konfigurasi stasiun /opt/vts/conf/ais_station.conf
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <?= vts_icon('terminal', 16, '#00f2fe') ?>
                    <span>Konsol Telemetri &amp; Sensor APBS-03</span>
                </span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span class="badge badge-green">PETUGAS: <?= htmlspecialchars($_SESSION['vts_operator_user'] ?? 'vts_operator') ?></span>
                    <a href="console.php?action=logout" class="btn btn-outline" style="font-size: 11px; padding: 4px 10px;">Keluar</a>
                </div>
            </div>

            <div style="display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap;">
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="id; whoami">
                    <button type="submit" class="btn btn-outline" style="font-size: 11px;">Cek Identitas Sesi</button>
                </form>
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="uptime; free -m">
                    <button type="submit" class="btn btn-outline" style="font-size: 11px;">Status Daya &amp; Memori</button>
                </form>
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="ls -la /opt/vts/scripts/">
                    <button type="submit" class="btn btn-outline" style="font-size: 11px;">Skrip Kalibrasi Sensor</button>
                </form>
            </div>

            <form method="POST" action="console.php" style="margin-bottom: 18px;">
                <label class="form-label">Perintah Diagnostik / Utilitas Sistem Sensor:</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="cmd" class="form-input" style="font-family: var(--font-mono);" 
                           value="<?= htmlspecialchars($executed_cmd ?? '') ?>" 
                           placeholder="e.g. ls -la /opt/vts/ atau cat /home/vts_operator/user.txt" required autofocus>
                    <button type="submit" class="btn btn-cyan">
                        <?= vts_icon('terminal', 14, '#07111e') ?>
                        <span>Jalankan</span>
                    </button>
                </div>
            </form>

            <?php if ($cmd_output !== null): ?>
                <div style="margin-bottom: 6px; font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Eksekusi: <code><?= htmlspecialchars($executed_cmd) ?></code></span>
                    <span>Panjang output: <?= strlen($cmd_output) ?> bytes</span>
                </div>
                <div class="log-box" style="color: #4ade80;"><?= htmlspecialchars($cmd_output) ?: '[Perintah selesai tanpa output]' ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
