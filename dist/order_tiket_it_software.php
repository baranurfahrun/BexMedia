<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: IT Software Support

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS tiket_it_software (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nomor_tiket VARCHAR(50),
    kategori VARCHAR(100),
    kendala TEXT,
    catatan_it TEXT,
    status ENUM('menunggu', 'diproses', 'selesai', 'ditolak') DEFAULT 'menunggu',
    status_validasi ENUM('Belum Validasi', 'Diterima', 'Ditolak') DEFAULT 'Belum Validasi',
    tanggal_input DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_selesai DATETIME NULL
)");

// Mock data categories if not exists
$categories = ['SIMRS Khanza', 'Website Profile', 'E-RM System', 'Email & Collaboration', 'BexMedia App', 'Lainnya'];

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $kategori = cleanInput($_POST['kategori']);
    $kendala = cleanInput($_POST['kendala']);
    $nomor_tiket = "TKT/SW/" . date('Ymd') . "/" . strtoupper(substr(uniqid(), -4));
    
    $res = safe_query("INSERT INTO tiket_it_software (user_id, nomor_tiket, kategori, kendala) VALUES (?, ?, ?, ?)", 
                       [$user_id, $nomor_tiket, $kategori, $kendala]);
    
    if($res) {
        write_log("TICKET_CREATED", "Membuat tiket baru: $nomor_tiket [$kategori]");
        $_SESSION['flash_success'] = "Tiket Anda berhasil dikirim dengan nomor: $nomor_tiket";
    }
    header("Location: order_tiket_it_software.php");
    exit;
}

// Handle Validation
if (isset($_POST['validasi'])) {
    csrf_verify();
    $id = intval($_POST['tiket_id']);
    safe_query("UPDATE tiket_it_software SET status_validasi='Diterima' WHERE id=? AND user_id=?", [$id, $user_id]);
    $_SESSION['flash_success'] = "Tiket telah diverifikasi sebagai selesai.";
    header("Location: order_tiket_it_software.php");
    exit;
}

$u = mysqli_fetch_assoc(safe_query("SELECT * FROM users WHERE id=?", [$user_id]));
$data_tiket = safe_query("SELECT * FROM tiket_it_software WHERE user_id=? ORDER BY tanggal_input DESC", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Software Tiket - BexMedia</title>
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
        .tabs { border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 32px; }
        .tab-btn {
            background: none; border: none; color: var(--text-muted);
            padding: 12px 24px; cursor: pointer; font-weight: 600;
            border-bottom: 2px solid transparent; transition: 0.3s;
        }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .status-badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .st-menunggu { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .st-diproses { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .st-selesai { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .st-ditolak { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>IT Software Support</h1>
                    <p>Laporkan kendala aplikasi atau ajukan permintaan fitur baru ke tim pengembang.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('new')">Request Baru</button>
                        <button class="tab-btn" onclick="showTab('history')">Tiket Saya</button>
                    </div>
                </div>
            </header>

            <div class="ticket-container">
                <!-- NEW TICKET TAB -->
                <div id="tab-new" class="tab-content active">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="premium-card">
                            <div class="form-grid">
                                <div>
                                    <label style="opacity:0.5; font-size:0.8rem;">PELAPOR</label>
                                    <p style="font-weight:700; font-size:1.1rem;"><?= h($u['nama_lengkap']) ?></p>
                                    <small style="opacity:0.4;"><?= h($u['jabatan']) ?> / <?= h($u['unit_kerja']) ?></small>
                                </div>
                                <div class="input-group">
                                    <label>Kategori Kendala</label>
                                    <select name="kategori" required>
                                        <option value="">-- Pilih Sistem --</option>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat ?>"><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="input-group" style="margin-bottom: 24px;">
                                <label>Deskripsi Kendala Secara Detail</label>
                                <textarea name="kendala" rows="5" placeholder="Jelaskan apa yang terjadi, apa yang Anda harapkan, dan langkah untuk mereproduksi error tersebut..." required></textarea>
                            </div>

                            <div style="display:flex; justify-content: space-between; align-items: center;">
                                <div style="display:flex; gap:12px; color:var(--text-muted); font-size:0.85rem;">
                                    <i data-lucide="info" size="16"></i>
                                    <span>Tim IT akan merespon dalam waktu maksimal 1x24 jam kerja.</span>
                                </div>
                                <button type="submit" name="simpan" class="btn btn-primary" style="padding: 16px 48px;">
                                    <i data-lucide="send"></i> Kirim Tiket
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- HISTORY TAB -->
                <div id="tab-history" class="tab-content">
                    <div class="premium-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No. Tiket</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi / Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($data_tiket)): ?>
                                    <tr>
                                        <td><strong><?= h($d['nomor_tiket']) ?></strong></td>
                                        <td><?= h($d['kategori']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($d['tanggal_input'])) ?></td>
                                        <td><span class="status-badge st-<?= $d['status'] ?>"><?= $d['status'] ?></span></td>
                                        <td>
                                            <?php if($d['status'] == 'selesai' && $d['status_validasi'] == 'Belum Validasi'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="tiket_id" value="<?= $d['id'] ?>">
                                                    <button type="submit" name="validasi" class="btn btn-primary btn-sm">Verifikasi Selesai</button>
                                                </form>
                                            <?php elseif($d['status_validasi'] == 'Diterima'): ?>
                                                <span style="color:#10b981; font-size:0.8rem; font-weight:700;">Verified <i data-lucide="check" size="12"></i></span>
                                            <?php else: ?>
                                                <button class="btn btn-outline btn-sm" onclick="alert('Detail: <?= addslashes($d['kendala']) ?>\n\nCatatan IT: <?= addslashes($d['catatan_it'] ?: '-') ?>')">Detail</button>
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
            $('.tab-content').removeClass('active');
            $('.tab-btn').removeClass('active');
            $('#tab-' + tab).addClass('active');
            $(`button[onclick="showTab('${tab}')"]`).addClass('active');
        }
    </script>
</body>
</html>
