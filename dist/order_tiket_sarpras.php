<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Sarpras & Facilities Support

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS tiket_sarpras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nomor_tiket VARCHAR(50),
    kategori VARCHAR(100),
    lokasi VARCHAR(255),
    kendala TEXT,
    catatan_it TEXT,
    teknisi_nama VARCHAR(255),
    status ENUM('menunggu', 'diproses', 'selesai', 'tidak bisa diperbaiki') DEFAULT 'menunggu',
    status_validasi ENUM('Belum Validasi', 'Diterima', 'Ditolak') DEFAULT 'Belum Validasi',
    tanggal_input DATETIME DEFAULT CURRENT_TIMESTAMP,
    waktu_validasi DATETIME NULL
)");

$sarpras_categories = ['Perbaikan AC', 'Pengecekan AC Rutin', 'Kelistrikan / Lampu', 'Furniture / Kursi / Meja', 'Plumbing / Air', 'Bangunan / Cat', 'Lainnya'];

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $kategori = cleanInput($_POST['kategori']);
    $lokasi = cleanInput($_POST['lokasi']);
    $kendala = cleanInput($_POST['kendala']);
    $nomor_tiket = "SAR/" . date('Ymd') . "/" . strtoupper(substr(uniqid(), -4));
    
    $res = safe_query("INSERT INTO tiket_sarpras (user_id, nomor_tiket, kategori, lokasi, kendala) VALUES (?, ?, ?, ?, ?)", 
                       [$user_id, $nomor_tiket, $kategori, $lokasi, $kendala]);
    
    if($res) {
        write_log("SARPRAS_TICKET_CREATED", "Membuat tiket sarpras baru: $nomor_tiket [$kategori]");
        $_SESSION['flash_success'] = "Tiket Sarpras berhasil dikirim: $nomor_tiket";
    }
    header("Location: order_tiket_sarpras.php");
    exit;
}

// Handle Validation
if (isset($_POST['validasi'])) {
    csrf_verify();
    $id = intval($_POST['tiket_id']);
    safe_query("UPDATE tiket_sarpras SET status_validasi='Diterima', waktu_validasi=NOW() WHERE id=? AND user_id=?", [$id, $user_id]);
    $_SESSION['flash_success'] = "Pekerjaan Sarpras telah diterima/diverifikasi.";
    header("Location: order_tiket_sarpras.php");
    exit;
}

$u = mysqli_fetch_assoc(safe_query("SELECT * FROM users WHERE id=?", [$user_id]));
$data_tiket = safe_query("SELECT * FROM tiket_sarpras WHERE user_id=? ORDER BY tanggal_input DESC", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarpras Support - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .ticket-container { margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
        .tab-btn {
            background: none; border: none; color: var(--text-muted); padding: 12px 24px; cursor: pointer; font-weight: 600; border-bottom: 2px solid transparent; transition: 0.3s;
        }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .status-badge { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .st-menunggu { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .st-diproses { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .st-selesai { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .st-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Sarpras & Facilities</h1>
                    <p>Laporkan kerusakan infrastruktur gedung, AC, listrik, atau peralatan non-IT.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('new')">Order Baru</button>
                        <button class="tab-btn" onclick="showTab('history')">Daftar Tiket</button>
                    </div>
                </div>
            </header>

            <div class="ticket-container">
                <div id="tab-new" class="tab-content active" style="display:block;">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="premium-card">
                            <div class="form-grid">
                                <div class="input-group">
                                    <label>Kategori Kerusakan</label>
                                    <select name="kategori" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach($sarpras_categories as $cat): ?>
                                            <option value="<?= $cat ?>"><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>Lokasi / Ruangan</label>
                                    <input type="text" name="lokasi" placeholder="Contoh: Gedung A Lt 2, Ruang Rapat, dll" required>
                                </div>
                            </div>
                            <div class="input-group" style="margin-bottom: 24px;">
                                <label>Detail Kendala</label>
                                <textarea name="kendala" rows="4" placeholder="Jelaskan kendala fisik yang terjadi..." required></textarea>
                            </div>
                            <div style="display:flex; justify-content: flex-end;">
                                <button type="submit" name="simpan" class="btn btn-primary" style="padding: 16px 48px;">
                                    <i data-lucide="shield-alert"></i> Kirim ke Sarpras
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="tab-history" class="tab-content" style="display:none;">
                    <div class="premium-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No. Tiket</th>
                                    <th>Kategori / Lokasi</th>
                                    <th>Tgl Lapor</th>
                                    <th>Status</th>
                                    <th>Validasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($data_tiket)): ?>
                                    <tr>
                                        <td><strong><?= h($d['nomor_tiket']) ?></strong></td>
                                        <td><?= h($d['kategori']) ?><br><small style="opacity:0.5"><?= h($d['lokasi']) ?></small></td>
                                        <td><?= date('d/m/Y', strtotime($d['tanggal_input'])) ?></td>
                                        <td><span class="status-badge st-<?= ($d['status']=='tidak bisa diperbaiki') ? 'failed' : $d['status'] ?>"><?= h($d['status']) ?></span></td>
                                        <td>
                                            <?php if($d['status'] == 'selesai' && $d['status_validasi'] == 'Belum Validasi'): ?>
                                                <form method="POST">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="tiket_id" value="<?= $d['id'] ?>">
                                                    <button type="submit" name="validasi" class="btn btn-primary btn-sm">Validasi</button>
                                                </form>
                                            <?php elseif($d['status_validasi'] == 'Diterima'): ?>
                                                <span style="color:#10b981; font-weight:700;">Diterima</span>
                                            <?php else: ?>
                                                <button class="btn btn-outline btn-sm" onclick="alert('Laporan: <?= addslashes($d['kendala']) ?>')">Detail</button>
                                            <?php endif; ?>
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
        function showTab(tab) {
            $('.tab-content').hide();
            $('.tab-btn').removeClass('active');
            $('#tab-' + tab).show();
            $(`button[onclick="showTab('${tab}')"]`).addClass('active');
        }
    </script>
</body>
</html>
