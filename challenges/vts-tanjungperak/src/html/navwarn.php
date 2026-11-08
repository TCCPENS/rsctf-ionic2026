<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/navbar.php';

$stmt_n = $db->query("SELECT * FROM nav_warnings ORDER BY id ASC");
$warnings = $stmt_n->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <?= vts_icon('warning', 16, '#00f2fe') ?>
                <span>Maklumat Keselamatan Pelayaran (Notice to Mariners - NOTMAR)</span>
            </span>
            <span class="badge badge-amber">ACTIVE BROADCASTS</span>
        </div>

        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 20px;">
            Siaran peringatan navigasi radio pantai resmi Stasiun Radio Pantai (SROP) Surabaya VTS melalui frekuensi internasional NAVTEX 518 kHz dan VHF Saluran 16.
        </p>

        <div class="table-responsive">
            <table class="vts-table">
                <thead>
                    <tr>
                        <th>Nomor Maklumat</th>
                        <th>Sektor Alur Pelayaran</th>
                        <th>Kategori Bahaya</th>
                        <th>Rincian Instruksi Navigasi</th>
                        <th>Masa Berlaku</th>
                        <th>Tingkat Urgensi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warnings as $w): ?>
                        <tr>
                            <td><strong style="color: var(--accent-cyan); font-family: var(--font-mono);"><?= htmlspecialchars($w['notice_number']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($w['fairway_sector']) ?></strong></td>
                            <td><span class="badge badge-blue"><?= htmlspecialchars($w['hazard_type']) ?></span></td>
                            <td style="font-size: 12px; max-width: 420px; line-height: 1.4;"><?= htmlspecialchars($w['details']) ?></td>
                            <td style="font-size: 11px;"><?= htmlspecialchars($w['effective_until']) ?></td>
                            <td>
                                <?php
                                $s_badge = 'badge-blue';
                                if ($w['severity_level'] === 'HIGH') $s_badge = 'badge-red';
                                if ($w['severity_level'] === 'MEDIUM') $s_badge = 'badge-amber';
                                ?>
                                <span class="badge <?= $s_badge ?>"><?= htmlspecialchars($w['severity_level']) ?></span>
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
                <?= vts_icon('radio', 16, '#00f2fe') ?>
                <span>Direktori Saluran Komunikasi Radio Pantai (VHF Maritime Mobile)</span>
            </span>
            <span style="font-size: 11px; color: var(--text-muted);">Alokasi Frekuensi Disnav Surabaya</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 6px; padding: 14px;">
                <div style="font-size: 14px; font-weight: 700; color: var(--accent-cyan); font-family: var(--font-mono);">VHF Channel 16</div>
                <div style="font-size: 12px; font-weight: 600; margin: 4px 0;">Frekuensi 156.800 MHz</div>
                <div style="font-size: 11px; color: var(--text-muted);">Panggilan Darurat Internasional, Marabahaya, dan Panggilan Awal Syahbandar.</div>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 6px; padding: 14px;">
                <div style="font-size: 14px; font-weight: 700; color: var(--accent-cyan); font-family: var(--font-mono);">VHF Channel 12</div>
                <div style="font-size: 12px; font-weight: 600; margin: 4px 0;">Frekuensi 156.600 MHz</div>
                <div style="font-size: 11px; color: var(--text-muted);">Operasional Pelayanan Lalu Lintas Kapal VTS Tanjung Perak &amp; Pergerakan APBS.</div>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 6px; padding: 14px;">
                <div style="font-size: 14px; font-weight: 700; color: var(--accent-cyan); font-family: var(--font-mono);">VHF Channel 14</div>
                <div style="font-size: 12px; font-weight: 600; margin: 4px 0;">Frekuensi 156.700 MHz</div>
                <div style="font-size: 11px; color: var(--text-muted);">Komunikasi Stasiun Pandu Surabaya &amp; Kapal Pandu Laut Karang Jamuang.</div>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 6px; padding: 14px;">
                <div style="font-size: 14px; font-weight: 700; color: var(--accent-cyan); font-family: var(--font-mono);">VHF Channel 72</div>
                <div style="font-size: 12px; font-weight: 600; margin: 4px 0;">Frekuensi 156.625 MHz</div>
                <div style="font-size: 11px; color: var(--text-muted);">Koordinasi Kapal Tunda (Tugboat) Pelindo Marine Service selama penyandaran.</div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
