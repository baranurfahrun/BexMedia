<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Satu Sehat Monitor

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS satu_sehat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT,
    tahun INT,
    endpoint VARCHAR(100),
    jumlah_data INT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$endpoints = ['Encounter', 'Condition', 'Observation', 'Procedure', 'Composition', 'Medication', 'CarePlan', 'Specimen', 'DiagnosticReport'];

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $ep = cleanInput($_POST['endpoint']);
    $total = intval($_POST['total']);
    
    $res = safe_query("INSERT INTO satu_sehat (bulan, tahun, endpoint, jumlah_data, petugas_id) VALUES (?, ?, ?, ?, ?)", 
                       [$bulan, $tahun, $ep, $total, $user_id]);
    
    if($res) {
        write_log("SATUSEHAT_SYNC_LOG", "Logging pengiriman data $ep periode $bulan/$tahun ($total record)");
        $_SESSION['flash_success'] = "Data sync SATUSEHAT $ep berhasil diperbarui.";
    }
    header("Location: satu_sehat.php");
    exit;
}

$bulan_nama = [1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April", 5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"];
$data_sync = safe_query("SELECT * FROM satu_sehat ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SatuSehat Monitor - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .sync-grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row { margin-bottom: 20px; }
        .endpoint-pill {
            display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;
            background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);
            font-family: monospace;
        }
        .sync-status { width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981; display: inline-block; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <div style="display:flex; align-items:center; margin-bottom: 8px;">
                        <span class="sync-status"></span>
                        <h1 style="margin:0;">SatuSehat Integration Tracker</h1>
                    </div>
                    <p>Pemantauan status pengiriman data HL7 FHIR ke platform SatuSehat Kemenkes RI.</p>
                </div>
            </header>

            <div class="sync-grid">
                <!-- FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Log Transmisi Baru</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Resource / Endpoint</label>
                            <select name="endpoint" required>
                                <option value="">-- Pilih Resource FHIR --</option>
                                <?php foreach($endpoints as $e): ?>
                                    <option value="<?= $e ?>"><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Periode</label>
                            <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:12px;">
                                <select name="bulan" required>
                                    <?php foreach($bulan_nama as $num => $nm) echo "<option value='$num' ".(date('n')==$num?'selected':'').">$nm</option>"; ?>
                                </select>
                                <select name="tahun" required>
                                    <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Jumlah Record Terkirim</label>
                            <input type="number" name="total" placeholder="Contoh: 1450" required>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="refresh-cw"></i> Log Transmisi
                        </button>
                    </form>
                    
                    <div style="margin-top: 32px; padding: 20px; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <div style="display:flex; gap:12px; align-items:flex-start;">
                            <i data-lucide="shield-check" style="color:#10b981;"></i>
                            <div>
                                <strong style="display:block; font-size: 0.9rem; color:#10b981;">HIS Compliance</strong>
                                <small style="opacity:0.6; line-height:1.4;">Data yang diinput harus sesuai dengan laporan log-bridge SatuSehat agar audit internal valid.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HISTORY TABLE -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Histori Sinkronisasi</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>FHIR Resource</th>
                                <th>Volume Data</th>
                                <th>Sync Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($data_sync)): ?>
                                <tr>
                                    <td><strong><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></strong></td>
                                    <td><span class="endpoint-pill"><?= h($row['endpoint']) ?></span></td>
                                    <td><?= number_format($row['jumlah_data']) ?> records</td>
                                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                    <td><span style="color:#10b981; font-size:0.8rem; font-weight:700;">SUCCESS</span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
