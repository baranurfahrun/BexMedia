<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Digital Identity & TTE

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS tte_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nama VARCHAR(255),
    nik VARCHAR(50),
    jabatan VARCHAR(100),
    unit VARCHAR(100),
    token VARCHAR(255) UNIQUE,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Handle Generate
if (isset($_POST['generate'])) {
    csrf_verify();
    
    // Check if active TTE exists
    $existing = mysqli_fetch_assoc(safe_query("SELECT id FROM tte_user WHERE user_id = ? AND status = 'aktif'", [$user_id]));
    if($existing) {
        $_SESSION['flash_error'] = "Anda sudah memiliki TTE aktif. Nonaktifkan yang lama untuk membuat yang baru.";
    } else {
        $u = mysqli_fetch_assoc(safe_query("SELECT * FROM users WHERE id = ?", [$user_id]));
        $token = bin2hex(random_bytes(32));
        
        $res = safe_query("INSERT INTO tte_user (user_id, nama, nik, jabatan, unit, token) VALUES (?, ?, ?, ?, ?, ?)", 
                           [$user_id, $u['nama_lengkap'], $u['nik'], $u['jabatan'], $u['unit_kerja'], $token]);
        
        if($res) {
            write_log("TTE_GENERATE", "Generate TTE baru untuk: " . $u['nama_lengkap']);
            $_SESSION['flash_success'] = "TTE Identity berhasil dibuat!";
        }
    }
    header("Location: buat_tte.php");
    exit;
}

$tte = mysqli_fetch_assoc(safe_query("SELECT * FROM tte_user WHERE user_id = ? AND status = 'aktif' ORDER BY created_at DESC LIMIT 1", [$user_id]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My TTE - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .tte-layout { display: grid; grid-template-columns: 1fr 400px; gap: 40px; margin-top: 24px; }
        .id-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.05) 100%);
            border-radius: 32px;
            padding: 40px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        .id-card::before { content: 'BEXMEDIA'; position: absolute; bottom: -20px; right: -20px; font-size: 8rem; font-weight: 900; opacity: 0.03; color: white; transform: rotate(-5deg); pointer-events: none; }
        
        .qr-placeholder { background: white; width: 140px; height: 140px; border-radius: 16px; padding: 12px; display: grid; place-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .qr-placeholder img { width: 100%; height: 100%; }

        .feature-card { background: rgba(255,255,255,0.03); border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 16px; display: flex; align-items: center; gap: 16px; }
        .feature-card .icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: grid; place-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Digital Identity & TTE</h1>
                    <p>Kelola identitas digital dan Tanda Tangan Elektronik untuk keaslian dokumen institusi.</p>
                </div>
            </header>

            <div class="tte-layout">
                <div class="left-pane">
                    <?php if($tte): ?>
                    <div class="id-card">
                        <div style="display:flex; justify-content: space-between; align-items:flex-start; margin-bottom:40px;">
                            <div style="display:flex; align-items:center; gap:16px;">
                                <div style="width:64px; height:64px; border-radius:50%; background:var(--primary-color); display:grid; place-items:center; font-size:1.5rem; font-weight:800; border:4px solid rgba(255,255,255,0.1);"><?= strtoupper(substr($tte['nama'], 0, 1)) ?></div>
                                <div>
                                    <h2 style="margin:0;"><?= h($tte['nama']) ?></h2>
                                    <p style="opacity:0.6; margin:4px 0;"><?= h($tte['jabatan']) ?></p>
                                </div>
                            </div>
                            <div class="qr-placeholder" title="Digital Signature Token: <?= $tte['token'] ?>">
                                <!-- Using a placeholder for QR, real implementation would use a library -->
                                <div style="width:100%; height:100%; background: #000; border-radius:4px; opacity:0.8;"></div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top:24px;">
                            <div>
                                <label style="font-size:0.6rem; opacity:0.4; letter-spacing:1px; text-transform:uppercase;">NIK Karyawan</label>
                                <p style="font-weight:700;"><?= h($tte['nik']) ?></p>
                            </div>
                            <div>
                                <label style="font-size:0.6rem; opacity:0.4; letter-spacing:1px; text-transform:uppercase;">Unit Kerja</label>
                                <p style="font-weight:700;"><?= h($tte['unit']) ?></p>
                            </div>
                            <div>
                                <label style="font-size:0.6rem; opacity:0.4; letter-spacing:1px; text-transform:uppercase;">Digital Token</label>
                                <p style="font-family:monospace; font-size:0.7rem; opacity:0.6;"><?= substr($tte['token'], 0, 24) ?>...</p>
                            </div>
                            <div>
                                <label style="font-size:0.6rem; opacity:0.4; letter-spacing:1px; text-transform:uppercase;">Status</label>
                                <p style="color:#10b981; font-weight:800; display:flex; align-items:center; gap:4px;"><i data-lucide="shield-check" size="14"></i> VERIFIED</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center; padding:100px; background:rgba(255,255,255,0.02); border-radius:32px; border:2px dashed rgba(255,255,255,0.05);">
                        <i data-lucide="signature" style="width:80px; height:80px; opacity:0.1; margin-bottom:24px;"></i>
                        <h3>Identitas TTE Belum Aktif</h3>
                        <p style="opacity:0.6; max-width:400px; margin: 16px auto;">Anda harus mengaktifkan identitas digital untuk mulai menandatangani dokumen secara elektronik.</p>
                        <form method="POST">
                            <?= csrf_field(); ?>
                            <button type="submit" name="generate" class="btn btn-primary" style="padding:16px 40px; margin-top:24px;">
                                <i data-lucide="zap"></i> Aktifkan TTE Sekarang
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:40px;">
                        <h3>Security Best Practices</h3>
                        <div class="feature-card">
                            <div class="icon"><i data-lucide="lock"></i></div>
                            <div>
                                <h4 style="margin:0; font-size:0.9rem;">Private Token Management</h4>
                                <p style="font-size:0.75rem; opacity:0.4; margin-top:4px;">Token Anda bersifat rahasia dan unik untuk setiap akun, digunakan sebagai kunci enkripsi tanda tangan.</p>
                            </div>
                        </div>
                        <div class="feature-card">
                            <div class="icon" style="background:rgba(59,130,246,0.1); color:var(--primary-color);"><i data-lucide="scan"></i></div>
                            <div>
                                <h4 style="margin:0; font-size:0.9rem;">QR-Verification System</h4>
                                <p style="font-size:0.75rem; opacity:0.4; margin-top:4px;">Gunakan QR Code pada dokumen tercetak untuk memverifikasi keaslian via portal BexMedia.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="right-pane">
                    <div class="glass-card" style="padding:32px;">
                        <h3 style="margin-bottom:20px;"><i data-lucide="info"></i> Tentang TTE</h3>
                        <p style="font-size:0.85rem; line-height:1.6; opacity:0.7;">Tanda Tangan Elektronik (TTE) BexMedia menggunakan algoritma enkripsi institusi untuk menjamin bahwa dokumen tidak dimodifikasi setelah ditandatangani.</p>
                        <ul style="padding-left:20px; font-size:0.8rem; line-height:1.8; opacity:0.6; margin-top:16px;">
                            <li>Legalitas internal untuk SK dan Surat Tugas.</li>
                            <li>Mengurangi penggunaan kertas (Paperless).</li>
                            <li>Pelacakan waktu tanda tangan yang akurat.</li>
                            <li>Otentikasi ganda via akun BexMedia.</li>
                        </ul>
                        <button class="btn btn-secondary" style="width:100%; margin-top:24px;">Panduan Selengkapnya</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
