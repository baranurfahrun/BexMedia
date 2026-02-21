<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: IT Infrastructure Inventory

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS data_barang_it (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_barang VARCHAR(50) UNIQUE,
    nama_barang VARCHAR(255),
    kategori ENUM('Notebook', 'PC Desktop', 'Printer', 'Scanner', 'Networking', 'Server', 'Other') DEFAULT 'Other',
    merk VARCHAR(100),
    spesifikasi TEXT,
    ip_address VARCHAR(50),
    lokasi VARCHAR(255),
    kondisi ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Broken') DEFAULT 'Good',
    status ENUM('Active', 'Backup', 'Repair', 'Scrapped') DEFAULT 'Active',
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Function to generate Inventory ID
function generateInvID() {
    $last = mysqli_fetch_assoc(safe_query("SELECT no_barang FROM data_barang_it ORDER BY id DESC LIMIT 1"));
    $num = 1;
    if ($last) {
        $parts = explode('/', $last['no_barang']);
        $num = intval($parts[0]) + 1;
    }
    return sprintf("%05d", $num) . "/INV-IT/BEX/" . date('Y');
}

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $inv_id = generateInvID();
    $nama = cleanInput($_POST['nama_barang']);
    $kat = cleanInput($_POST['kategori']);
    $merk = cleanInput($_POST['merk']);
    $specs = cleanInput($_POST['spesifikasi']);
    $ip = cleanInput($_POST['ip_address']);
    $lokasi = cleanInput($_POST['lokasi']);
    $kondisi = cleanInput($_POST['kondisi']);
    
    $res = safe_query("INSERT INTO data_barang_it (no_barang, nama_barang, kategori, merk, spesifikasi, ip_address, lokasi, kondisi, petugas_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$inv_id, $nama, $kat, $merk, $specs, $ip, $lokasi, $kondisi, $user_id]);
    
    if($res) {
        write_log("IT_ASSET_ADD", "Inventory baru: $nama [$inv_id]");
        $_SESSION['flash_success'] = "Asset $inv_id berhasil didaftarkan.";
    }
    header("Location: data_barang_it.php");
    exit;
}

$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%%";
$data_assets = safe_query("SELECT * FROM data_barang_it WHERE (nama_barang LIKE ? OR no_barang LIKE ? OR ip_address LIKE ?) ORDER BY no_barang DESC", [$search, $search, $search]);

$rekap_kat = safe_query("SELECT kategori, COUNT(*) as total FROM data_barang_it GROUP BY kategori");
$rekap_kondisi = safe_query("SELECT kondisi, COUNT(*) as total FROM data_barang_it GROUP BY kondisi");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .inventory-grid { display: grid; grid-template-columns: 350px 1fr; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row { margin-bottom: 20px; }
        .asset-chip { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .chip-Notebook { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .chip-PC { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .chip-Printer { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .chip-Excellent { border: 1px solid #10b981; color: #10b981; }
        .chip-Good { border: 1px solid #3b82f6; color: #3b82f6; }
        .chip-Broken { border: 1px solid #ef4444; color: #ef4444; }

        .report-section { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 24px; }
        .report-pill { display: flex; justify-content: space-between; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>IT Asset Management</h1>
                    <p>Pusat kendali inventaris infrastruktur teknologi dan perangkat keras operasional.</p>
                </div>
                <div class="header-right">
                    <div style="position:relative;">
                        <i data-lucide="search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; opacity:0.3;"></i>
                        <input type="text" id="assetSearch" placeholder="Cari Asset / IP / S/N..." style="padding-left:40px; width:300px;">
                    </div>
                </div>
            </header>

            <div class="inventory-grid">
                <!-- REGISTRATION FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Registrasi Asset</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Nama Perangkat</label>
                            <input type="text" name="nama_barang" placeholder="Contoh: Dell Latitude 5420" required>
                        </div>
                        <div class="form-row">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <option value="Notebook">Notebook</option>
                                <option value="PC Desktop">PC Desktop</option>
                                <option value="Printer">Printer</option>
                                <option value="Scanner">Scanner</option>
                                <option value="Networking">Networking</option>
                                <option value="Server">Server</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label>Identitas Perangkat</label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                                <input type="text" name="merk" placeholder="Merk (HP, Dell..)">
                                <input type="text" name="ip_address" placeholder="IP Address">
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Spesifikasi Teknis</label>
                            <textarea name="spesifikasi" rows="2" placeholder="CPU, RAM, Storage..."></textarea>
                        </div>
                        <div class="form-row">
                            <label>Lingkungan Operasional</label>
                            <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:12px;">
                                <input type="text" name="lokasi" placeholder="Lokasi (Unit Kerja)" required>
                                <select name="kondisi">
                                    <option value="Excellent">Excellent</option>
                                    <option value="Good" selected>Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                    <option value="Broken">Broken</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="database"></i> Daftarkan Asset
                        </button>
                    </form>

                    <div class="report-section">
                        <div style="grid-column: span 2; font-size: 0.8rem; opacity: 0.4; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">Summary By Condition</div>
                        <?php while($c = mysqli_fetch_assoc($rekap_kondisi)): ?>
                            <div class="report-pill">
                                <span style="font-size:0.8rem;"><?= $c['kondisi'] ?></span>
                                <strong style="color:var(--primary-color)"><?= $c['total'] ?></strong>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- ASSET TABLE -->
                <div class="premium-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3>Inventaris Aktif</h3>
                        <div style="display:flex; gap:12px;">
                            <button class="btn btn-secondary" title="Export PDF"><i data-lucide="file-text"></i></button>
                            <button class="btn btn-secondary" title="Export Excel"><i data-lucide="table"></i></button>
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID Inventory</th>
                                    <th>Perangkat</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryBody">
                                <?php while($row = mysqli_fetch_assoc($data_assets)): ?>
                                <tr>
                                    <td><strong><?= $row['no_barang'] ?></strong><br><small style="opacity:0.4;"><?= $row['ip_address'] ?: 'No IP' ?></small></td>
                                    <td><?= h($row['nama_barang']) ?><br><small style="opacity:0.4;"><?= $row['merk'] ?></small></td>
                                    <td><span class="asset-chip chip-<?= explode(' ', $row['kategori'])[0] ?>"><?= $row['kategori'] ?></span></td>
                                    <td><?= h($row['lokasi']) ?></td>
                                    <td><span class="asset-chip chip-<?= $row['kondisi'] ?>"><?= $row['kondisi'] ?></span></td>
                                    <td>
                                        <div style="display:flex; gap:4px;">
                                            <button class="btn btn-secondary" style="padding:4px;" title="Maintenance Details"><i data-lucide="wrench"></i></button>
                                            <button class="btn btn-secondary" style="padding:4px;" title="Edit Asset"><i data-lucide="edit-3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        $('#assetSearch').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            $("#inventoryBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
            });
        });
    </script>
</body>
</html>
