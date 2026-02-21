<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: SIMRS Development Progress

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS progres_kerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT,
    tahun INT,
    progres TEXT,
    persentase INT DEFAULT 0,
    status ENUM('Planning', 'Development', 'Testing', 'Deployed') DEFAULT 'Planning',
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $progres = cleanInput($_POST['progres']);
    $persen = intval($_POST['persentase']);
    $status = cleanInput($_POST['status']);
    
    $res = safe_query("INSERT INTO progres_kerja (bulan, tahun, progres, persentase, status, petugas_id) 
                       VALUES (?, ?, ?, ?, ?, ?)", 
                       [$bulan, $tahun, $progres, $persen, $status, $user_id]);
    
    if($res) {
        write_log("PROGRES_WORK_LOG", "Update progres kerja SIMRS: $progres ($persen%)");
        $_SESSION['flash_success'] = "Progres kerja berhasil didokumentasikan.";
    }
    header("Location: progres_kerja.php");
    exit;
}

$bulan_nama = [1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April", 5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"];
$data_progres = safe_query("SELECT * FROM progres_kerja ORDER BY tahun DESC, bulan DESC, created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Progress - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .work-grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row { margin-bottom: 20px; }
        .timeline { position: relative; padding-left: 32px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: rgba(255,255,255,0.05); }
        .timeline-item { position: relative; margin-bottom: 40px; }
        .timeline-dot { position: absolute; left: -36px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color); box-shadow: 0 0 10px var(--primary-color); }
        .progress-bar-bg { width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 12px 0; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: var(--primary-color); border-radius: 10px; }
        .status-pill { padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; background: rgba(255,255,255,0.05); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>SIMRS Development Progress</h1>
                    <p>Dokumentasi aktivitas pengembangan modul dan integrasi infrastruktur sistem informasi rumah sakit.</p>
                </div>
            </header>

            <div class="work-grid">
                <!-- FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Log Progres Baru</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
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
                            <label>Deskripsi Progres / Feature</label>
                            <textarea name="progres" rows="3" placeholder="Contoh: Implementasi bridging SatuSehat v2..." required></textarea>
                        </div>
                        <div class="form-row">
                            <label>Capaian (%)</label>
                            <input type="range" name="persentase" min="0" max="100" value="0" oninput="this.nextElementSibling.value = this.value + '%'">
                            <output style="display:block; text-align:right; font-weight:700;">0%</output>
                        </div>
                        <div class="form-row">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="Planning">Planning</option>
                                <option value="Development">Development</option>
                                <option value="Testing">Testing</option>
                                <option value="Deployed">Deployed</option>
                            </select>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="check-circle"></i> Log Progress
                        </button>
                    </form>
                </div>

                <!-- TIMELINE -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 32px;">Timeline Pengembangan</h3>
                    <div class="timeline">
                        <?php while($row = mysqli_fetch_assoc($data_progres)): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <h4 style="margin:0;"><?= h($row['progres']) ?></h4>
                                        <small style="opacity:0.4;"><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></small>
                                    </div>
                                    <span class="status-pill"><?= $row['status'] ?></span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?= $row['persentase'] ?>%;"></div>
                                </div>
                                <div style="text-align:right; font-size: 0.8rem; font-weight:700; opacity:0.6;"><?= $row['persentase'] ?>% Complete</div>
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
