<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: AC Unit Inventory

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS data_barang_ac (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_asset VARCHAR(50) UNIQUE,
    merk VARCHAR(100),
    kapasitas VARCHAR(50),
    lokasi VARCHAR(100),
    tanggal_pemasangan DATE,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Baik',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $no_asset = cleanInput($_POST['no_asset']);
    $merk = cleanInput($_POST['merk']);
    $kapasitas = cleanInput($_POST['kapasitas']);
    $lokasi = cleanInput($_POST['lokasi']);
    $tgl = $_POST['tanggal_pemasangan'];
    $kondisi = $_POST['kondisi'];
    
    $res = safe_query("INSERT INTO data_barang_ac (no_asset, merk, kapasitas, lokasi, tanggal_pemasangan, kondisi) VALUES (?, ?, ?, ?, ?, ?)", 
                       [$no_asset, $merk, $kapasitas, $lokasi, $tgl, $kondisi]);
    
    if($res) {
        write_log("AC_ASSET", "Registrasi asset AC: $no_asset - $merk");
        $_SESSION['flash_success'] = "Asset AC berhasil didaftarkan.";
    }
    header("Location: data_barang_ac.php");
    exit;
}

$data_ac = safe_query("SELECT * FROM data_barang_ac ORDER BY lokasi ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AC Inventory - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .ac-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 24px; }
        .ac-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: 0.3s;
        }
        .ac-card:hover { transform: translateY(-5px); border-color: var(--primary-color); }
        .ac-card::before {
            content: ''; position: absolute; top: -50px; right: -50px; width: 100px; height: 100px;
            background: var(--primary-color); opacity: 0.1; filter: blur(40px); border-radius: 50%;
        }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .st-Baik { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .st-Rusak_Ringan { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .st-Rusak_Berat { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .spec-item { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; margin-top: 12px; opacity: 0.6; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Air Conditioner Inventory</h1>
                    <p>Manajemen aset pendingin ruangan di seluruh area Rumah Sakit.</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="$('#acModal').show()">
                        <i data-lucide="plus"></i> Registrasi AC
                    </button>
                </div>
            </header>

            <div class="ac-grid">
                <?php while($row = mysqli_fetch_assoc($data_ac)): ?>
                <div class="ac-card">
                    <div style="display:flex; justify-content: space-between; align-items:flex-start;">
                        <div>
                            <span style="font-size:0.65rem; opacity:0.4; letter-spacing:1px; text-transform:uppercase;">Asset ID: <?= $row['no_asset'] ?></span>
                            <h3 style="margin:4px 0;"><?= h($row['merk']) ?></h3>
                        </div>
                        <span class="status-pill st-<?= str_replace(' ', '_', $row['kondisi']) ?>"><?= $row['kondisi'] ?></span>
                    </div>
                    
                    <div style="margin-top:16px;">
                        <div class="spec-item"><i data-lucide="map-pin" size="14"></i> <?= h($row['lokasi']) ?></div>
                        <div class="spec-item"><i data-lucide="zap" size="14"></i> <?= h($row['kapasitas']) ?></div>
                        <div class="spec-item"><i data-lucide="calendar" size="14"></i> Install: <?= date('d M Y', strtotime($row['tanggal_pemasangan'])) ?></div>
                    </div>

                    <div style="margin-top:20px; display:flex; gap:8px;">
                        <button class="btn btn-secondary btn-sm" style="flex:1;"><i data-lucide="tool"></i> Maintenance</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- MODAL REGISTRATION -->
    <div id="acModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:100; backdrop-filter:blur(10px);">
        <div style="background:var(--bg-card); max-width:500px; margin: 100px auto; border-radius:24px; padding:32px; border:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                <h2>Register New AC</h2>
                <button onclick="$('#acModal').hide()" style="background:none; border:none; color:white; cursor:pointer;"><i data-lucide="x"></i></button>
            </div>
            <form method="POST">
                <?= csrf_field(); ?>
                <div class="input-group">
                    <label>No. Asset / Sticker</label>
                    <input type="text" name="no_asset" required placeholder="RS/AC/001">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="input-group">
                        <label>Merk</label>
                        <input type="text" name="merk" required placeholder="Daikin / Panasonic">
                    </div>
                    <div class="input-group">
                        <label>Kapasitas (BTU/PK)</label>
                        <input type="text" name="kapasitas" required placeholder="1 PK / 9000 BTU">
                    </div>
                </div>
                <div class="input-group">
                    <label>Lokasi Penempatan</label>
                    <input type="text" name="lokasi" required placeholder="Ruang Perawat Lt. 2">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="input-group">
                        <label>Tgl Pemasangan</label>
                        <input type="date" name="tanggal_pemasangan" required>
                    </div>
                    <div class="input-group">
                        <label>Kondisi Awal</label>
                        <select name="kondisi">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding:16px; margin-top:12px;">Simpan Aset</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
