<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: E-RM Statistics

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS data_erm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT,
    nama_unit VARCHAR(255),
    bulan INT,
    tahun INT,
    catatan_erm TEXT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$units = ['Poliklinik Spesialis', 'IGD', 'Rawat Inap', 'Farmasi', 'Laboratorium', 'Radiologi', 'Rekam Medis'];

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $unit = cleanInput($_POST['unit']);
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $catatan = cleanInput($_POST['catatan']);
    
    $res = safe_query("INSERT INTO data_erm (nama_unit, bulan, tahun, catatan_erm, petugas_id) VALUES (?, ?, ?, ?, ?)", 
                       [$unit, $bulan, $tahun, $catatan, $user_id]);
    
    if($res) {
        write_log("ERM_LOG_CREATED", "Input evaluasi E-RM unit $unit periode $bulan/$tahun");
        $_SESSION['flash_success'] = "Log evaluasi E-RM unit $unit berhasil disimpan.";
    }
    header("Location: erm.php");
    exit;
}

$bulan_nama = [1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April", 5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"];
$data_erm = safe_query("SELECT * FROM data_erm ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-RM Tracker - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .erm-content { display: grid; grid-template-columns: 450px 1fr; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row { margin-bottom: 20px; }
        .log-item {
            padding: 20px;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            margin-bottom: 16px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>E-RM Statistics</h1>
                    <p>Monitoring implementasi Rekam Medis Elektronik per unit pelayanan untuk memastikan digitalisasi yang efisien.</p>
                </div>
            </header>

            <div class="erm-content">
                <!-- FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Update Implementasi Unit</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Unit Pelayanan</label>
                            <select name="unit" required>
                                <option value="">-- Pilih Unit --</option>
                                <?php foreach($units as $u): ?>
                                    <option value="<?= $u ?>"><?= $u ?></option>
                                <?php endforeach; ?>
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
                            <label>Analisis & Catatan E-RM</label>
                            <textarea name="catatan" rows="5" placeholder="Sebutkan menu yang sudah aktif, kendala penginputan oleh dokter, atau stabilitas sistem di unit ini..." required></textarea>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="upload-cloud"></i> Publikasi Update
                        </button>
                    </form>
                </div>

                <!-- FEED / LOG -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Feed Aktivitas E-RM</h3>
                    <div style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                        <?php while($row = mysqli_fetch_assoc($data_erm)): ?>
                            <div class="log-item">
                                <div style="display:flex; justify-content: space-between; margin-bottom: 12px;">
                                    <strong style="color:var(--primary-color); text-transform:uppercase; font-size:0.8rem; letter-spacing:1px;"><?= h($row['nama_unit']) ?></strong>
                                    <span style="opacity:0.4; font-size:0.75rem;"><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></span>
                                </div>
                                <p style="font-size:0.95rem; line-height:1.6;"><?= nl2br(h($row['catatan_erm'])) ?></p>
                                <div style="margin-top: 12px; display:flex; gap:12px; align-items:center;">
                                    <div style="width:24px; height:24px; background:var(--primary-color); border-radius:50%; display:grid; place-items:center; font-size:0.6rem; color:#000; font-weight:800;">IT</div>
                                    <small style="opacity:0.4;">Verified by System Auditor</small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
