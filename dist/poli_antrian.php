<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Poliklinik Statistics

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS poliklinik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_poli VARCHAR(255) UNIQUE
)");

safe_query("INSERT IGNORE INTO poliklinik (nama_poli) VALUES 
    ('Poli Penyakit Dalam'), ('Poli Anak'), ('Poli Kandungan'), ('Poli Bedah Umum'), 
    ('Poli Mata'), ('Poli THT'), ('Poli Saraf'), ('Poli Gigi'), ('Poli Jantung')");

safe_query("CREATE TABLE IF NOT EXISTS poli_antrian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_poli INT,
    bulan INT,
    tahun INT,
    jumlah_sep INT,
    jumlah_antri INT,
    jumlah_mjkn INT,
    persen_all FLOAT,
    persen_mjkn FLOAT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $poli_id = intval($_POST['poli_id']);
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $sep = intval($_POST['sep']);
    $antri = intval($_POST['antri']);
    $mjkn = intval($_POST['mjkn']);
    
    $persen_all = ($sep > 0) ? round(($antri / $sep) * 100, 2) : 0;
    $persen_mjkn = ($sep > 0) ? round(($mjkn / $sep) * 100, 2) : 0;
    
    $res = safe_query("INSERT INTO poli_antrian (id_poli, bulan, tahun, jumlah_sep, jumlah_antri, jumlah_mjkn, persen_all, persen_mjkn, petugas_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$poli_id, $bulan, $tahun, $sep, $antri, $mjkn, $persen_all, $persen_mjkn, $user_id]);
    
    if($res) {
        write_log("POLI_STATS_INPUT", "Input capaian antrian unit poli ID $poli_id periode $bulan/$tahun");
        $_SESSION['flash_success'] = "Statistik poliklinik berhasil diperbarui.";
    }
    header("Location: poli_antrian.php");
    exit;
}

$bulan_nama = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"Mei", 6=>"Jun", 7=>"Jul", 8=>"Agu", 9=>"Sep", 10=>"Okt", 11=>"Nov", 12=>"Des"];
$poliklinik_list = safe_query("SELECT * FROM poliklinik ORDER BY nama_poli ASC");
$history = safe_query("SELECT pa.*, p.nama_poli 
                       FROM poli_antrian pa 
                       JOIN poliklinik p ON pa.id_poli = p.id 
                       ORDER BY pa.created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poliklinik Analytics - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .poli-container { display: grid; grid-template-columns: 400px 1fr; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row { margin-bottom: 20px; }
        .stats-badge { padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem; }
        .stats-low { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .stats-high { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Antrian Poliklinik</h1>
                    <p>Pemantauan utilisasi antrian online berbasis unit pelayanan spesialis.</p>
                </div>
            </header>

            <div class="poli-container">
                <!-- FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Update Data Unit</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Poliklinik</label>
                            <select name="poli_id" required>
                                <option value="">-- Pilih Poliklinik --</option>
                                <?php while($p = mysqli_fetch_assoc($poliklinik_list)) echo "<option value='{$p['id']}'>{$p['nama_poli']}</option>"; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Periode</label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                                <select name="bulan" required>
                                    <?php foreach($bulan_nama as $num => $nm) echo "<option value='$num' ".(date('n')==$num?'selected':'').">$nm</option>"; ?>
                                </select>
                                <select name="tahun" required>
                                    <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Jumlah SEP BPJS</label>
                            <input type="number" name="sep" required>
                        </div>
                        <div class="form-row">
                            <label>Total Antrian Bridging</label>
                            <input type="number" name="antri" required>
                        </div>
                        <div class="form-row">
                            <label>Utilisasi Mobile JKN</label>
                            <input type="number" name="mjkn" required>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="save"></i> Simpan Data Poli
                        </button>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Laporan Pemanfaatan Terakhir</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Poliklinik</th>
                                <th>Bulan</th>
                                <th>SEP</th>
                                <th>% Total</th>
                                <th>% MJKN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($history)): ?>
                                <tr>
                                    <td><strong><?= h($row['nama_poli']) ?></strong></td>
                                    <td><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></td>
                                    <td><?= number_format($row['jumlah_sep']) ?></td>
                                    <td>
                                        <span class="stats-badge <?= $row['persen_all'] < 80 ? 'stats-low' : 'stats-high' ?>">
                                            <?= $row['persen_all'] ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="stats-badge <?= $row['persen_mjkn'] < 50 ? 'stats-low' : 'stats-high' ?>">
                                            <?= $row['persen_mjkn'] ?>%
                                        </span>
                                    </td>
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
