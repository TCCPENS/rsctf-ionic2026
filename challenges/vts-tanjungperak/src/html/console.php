<?php
session_start();

// Load station credentials from config file (with fallback)
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

// Handle login / logout
$auth_error = null;
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['vts_operator_auth']);
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

// Handle command execution if logged in
$cmd_output = null;
$executed_cmd = null;

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $executed_cmd = trim($_POST['cmd']);
    if ($executed_cmd !== '') {
        // Execute command as user 'vts_operator'
        // Uses sudo -u vts_operator if configured in sudoers
        $safe_arg = escapeshellarg($executed_cmd);
        $full_cmd = "sudo -u vts_operator bash -c {$safe_arg} 2>&1";
        
        // Check if sudo is available, fallback to direct shell
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
        <!-- Operator Login View -->
        <div class="card" style="max-width: 480px; margin: 40px auto;">
            <div class="card-header">
                <span class="card-title">🔐 Otentikasi Petugas Jaga VTS</span>
                <span class="badge badge-amber">RESTRICTED</span>
            </div>

            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
                Konsol telemetri internal stasiun APBS-03 Karang Jamuang. Akses dibatasi hanya untuk Petugas Operasional VTS Tanjung Perak.
            </p>

            <?php if ($auth_error): ?>
                <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--accent-red); color: #f87171; padding: 10px 14px; border-radius: 6px; font-size: 12px; margin-bottom: 16px;">
                    <?= htmlspecialchars($auth_error) ?>
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
                    Masuk ke Konsol Stasiun
                </button>
            </form>

            <div style="margin-top: 16px; font-size: 11px; color: var(--text-muted); text-align: center;">
                Catatan: Kredensial operator tersinkronisasi via file konfigurasi stasiun <code>/opt/vts/conf/ais_station.conf</code>.
            </div>
        </div>

    <?php else: ?>
        <!-- Authenticated Operator Terminal View -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">💻 Konsol Telemetri & Sensor APBS-03</span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span class="badge badge-green">OPERATOR: <?= htmlspecialchars($_SESSION['vts_operator_user'] ?? 'vts_operator') ?></span>
                    <a href="console.php?action=logout" class="btn" style="background: #334155; font-size: 11px; padding: 4px 10px;">Logout</a>
                </div>
            </div>

            <!-- Quick Action Diagnostic Buttons -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="id; whoami">
                    <button type="submit" class="btn" style="background: #1e293b; font-size: 12px;">👤 Cek Identitas Sesi</button>
                </form>
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="uptime; free -m">
                    <button type="submit" class="btn" style="background: #1e293b; font-size: 12px;">📊 Status Daya & Memori</button>
                </form>
                <form method="POST" action="console.php" style="display:inline;">
                    <input type="hidden" name="cmd" value="ls -la /opt/vts/scripts/">
                    <button type="submit" class="btn" style="background: #1e293b; font-size: 12px;">🌊 Cek Skrip Kalibrasi Sensor</button>
                </form>
            </div>

            <!-- Custom Command Form -->
            <form method="POST" action="console.php" style="margin-bottom: 20px;">
                <label class="form-label">Perintah Diagnostik / Utilitas Sistem Sensor:</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="cmd" class="form-input" style="font-family: var(--font-mono);" 
                           value="<?= htmlspecialchars($executed_cmd ?? '') ?>" 
                           placeholder="e.g. cat /home/vts_operator/user.txt atau ls -la /opt/vts/" required autofocus>
                    <button type="submit" class="btn btn-cyan">Jalankan</button>
                </div>
            </form>

            <!-- Command Output Display -->
            <?php if ($cmd_output !== null): ?>
                <div style="margin-bottom: 6px; font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Eksekusi: <code><?= htmlspecialchars($executed_cmd) ?></code></span>
                    <span>Panjang output: <?= strlen($cmd_output) ?> bytes</span>
                </div>
                <div class="log-box" style="color: #4ade80;"><?= htmlspecialchars($cmd_output) ?: '[Perintah selesai tanpa output]' ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
