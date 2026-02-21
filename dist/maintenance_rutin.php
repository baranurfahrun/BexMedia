<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Routine Asset Maintenance

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS maintanance_rutin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT,
    nama_teknisi VARCHAR(100),
    kondisi_fisik TEXT,
    fungsi_perangkat TEXT,
    catatan TEXT,
    waktu_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES data_barang_it(id) ON DELETE CASCADE
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $barang_id = intval($_POST['barang_id']);
    $kondisi_fisik = isset($_POST['kondisi_fisik']) ? implode(", ", $_POST['kondisi_fisik']) : '';
    $fungsi_perangkat = isset($_POST['fungsi_perangkat']) ? implode(", ", $_POST['fungsi_perangkat']) : '';
    $catatan = cleanInput($_POST['catatan']);
    
    // Get technician name
    $u = mysqli_fetch_assoc(safe_query("SELECT nama FROM users WHERE id = ?", [$user_id]));
    $teknisi = $u['nama'] ?? 'Unknown';
    
    $res = safe_query("INSERT INTO maintanance_rutin (barang_id, nama_teknisi, kondisi_fisik, fungsi_perangkat, catatan) 
                       VALUES (?, ?, ?, ?, ?)", 
                       [$barang_id, $teknisi, $kondisi_fisik, $fungsi_perangkat, $catatan]);
    
    if($res) {
        write_log("MAINTENANCE_LOG", "Maintenance rutin perangkat ID: $barang_id");
        $_SESSION['flash_success'] = "Data maintenance berhasil disimpan.";
    }
    header("Location: maintenance_rutin.php");
    exit;
}

$assets = safe_query("SELECT id, no_barang, nama_barang, lokasi FROM data_barang_it ORDER BY no_barang ASC");
$history = safe_query("SELECT mr.*, db.nama_barang, db.no_barang, db.lokasi 
                       FROM maintanance_rutin mr 
                       JOIN data_barang_it db ON mr.barang_id = db.id 
                       ORDER BY mr.waktu_input DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routine Maintenance - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .maintenance-container { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
        .glass-card { background: rgba(255,255,255,0.03); border-radius: 20px; padding: 32px; border: 1px solid rgba(255,255,255,0.1); }
        .check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
        .check-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; font-size: 0.85rem; cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
        .check-item:hover { background: rgba(255,255,255,0.05); }
        .check-item input { width: 18px; height: 18px; }
        
        .timeline-item { position: relative; padding-left: 24px; margin-bottom: 24px; border-left: 1px solid rgba(255,255,255,0.1); }
        .timeline-item::before { content: ''; position: absolute; left: -5px; top: 0; width: 9px; height: 9px; border-radius: 50%; background: var(--primary-color); box-shadow: 0 0 10px var(--primary-color); }
        
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Routine Maintenance Logs</h1>
                    <p>Dokumentasi perawatan periodik untuk menjamin stabilitas infrastruktur Rumah Sakit.</p>
                </div>
            </header>

            <div class="maintenance-container">
                <!-- FORM -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 24px; display:flex; align-items:center; gap:12px;"><i data-lucide="wrench"></i> Log Maintenance Baru</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Pilih Asset IT</label>
                            <select name="barang_id" required>
                                <option value="">-- Pilih Perangkat --</option>
                                <?php while($a = mysqli_fetch_assoc($assets)): ?>
                                    <option value="<?= $a['id'] ?>"><?= $a['no_barang'] ?> - <?= $a['nama_barang'] ?> (<?= $a['lokasi'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div style="margin-top: 24px;">
                            <label style="font-size:0.75rem; opacity:0.4; text-transform:uppercase; letter-spacing:1px;">Kondisi Fisik</label>
                            <div class="check-grid">
                                <?php $fisik = ['Bodi Utuh', 'Layar Jernih', 'Kabel Aman', 'Port Normal', 'Lampu Indikator', 'Bersih']; 
                                foreach($fisik as $f): ?>
                                <label class="check-item">
                                    <input type="checkbox" name="kondisi_fisik[]" value="<?= $f ?>"> <?= $f ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div style="margin-top: 24px;">
                            <label style="font-size:0.75rem; opacity:0.4; text-transform:uppercase; letter-spacing:1px;">Fungsi Perangkat</label>
                            <div class="check-grid">
                                <?php $fungsi = ['Booting Speed', 'OS Stabil', 'Driver Lengkap', 'USB Deteksi', 'Keyboard/Mouse', 'Update Tersedia']; 
                                foreach($fungsi as $fu): ?>
                                <label class="check-item">
                                    <input type="checkbox" name="fungsi_perangkat[]" value="<?= $fu ?>"> <?= $fu ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-row" style="margin-top:24px;">
                            <label>Catatan Tambahan</label>
                            <textarea name="catatan" rows="3" placeholder="Deskripsikan temuan atau tindakan yang diambil..."></textarea>
                        </div>

                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding:16px; margin-top:12px;">
                            <i data-lucide="check-circle"></i> Selesaikan Log Maintenance
                        </button>
                    </form>
                </div>

                <!-- HISTORY -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 24px;"><i data-lucide="history"></i> Riwayat Terakhir</h3>
                    <?php if(mysqli_num_rows($history) > 0): ?>
                        <?php while($h = mysqli_fetch_assoc($history)): ?>
                        <div class="timeline-item">
                            <div style="display:flex; justify-content: space-between;">
                                <strong><?= h($h['nama_barang']) ?></strong>
                                <span class="status-pill"><?= date('d M Y', strtotime($h['waktu_input'])) ?></span>
                            </div>
                            <p style="font-size:0.8rem; opacity:0.6; margin: 4px 0;"><?= h($h['no_barang']) ?> • <?= h($h['lokasi']) ?></p>
                            <div style="display:flex; flex-wrap:wrap; gap:4px; margin: 8px 0;">
                                <?php 
                                $tags = array_merge(explode(', ', $h['kondisi_fisik']), explode(', ', $h['fungsi_perangkat']));
                                foreach(array_slice($tags, 0, 4) as $tag): if($tag): ?>
                                    <span style="font-size:0.65rem; padding:2px 6px; background:rgba(255,255,255,0.05); border-radius:4px; opacity:0.7;"><?= $tag ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                            <p style="font-size:0.75rem; font-style:italic; opacity:0.4;">Technician: <?= $h['nama_teknisi'] ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:60px 0; opacity:0.2;">
                            <i data-lucide="inbox" style="width:48px; height:48px;"></i>
                            <p>Belum ada aktivitas hari ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
