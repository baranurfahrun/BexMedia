<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Institutional Correspondence

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS surat_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_surat VARCHAR(100),
    tgl_surat DATE,
    tgl_terima DATE,
    pengirim VARCHAR(255),
    asal_surat VARCHAR(255),
    perihal TEXT,
    jenis_surat VARCHAR(50),
    sifat_surat VARCHAR(50),
    disposisi_ke VARCHAR(255),
    file_path VARCHAR(255),
    status ENUM('Belum Dibalas', 'Sudah Dibalas', 'Arsip') DEFAULT 'Belum Dibalas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

safe_query("CREATE TABLE IF NOT EXISTS surat_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_surat VARCHAR(100),
    tgl_surat DATE,
    tujuan VARCHAR(255),
    perihal TEXT,
    jenis_surat VARCHAR(50),
    sifat_surat VARCHAR(50),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$type = $_GET['type'] ?? 'Masuk';
$data = ($type == 'Masuk') 
    ? safe_query("SELECT * FROM surat_masuk ORDER BY created_at DESC") 
    : safe_query("SELECT * FROM surat_keluar ORDER BY created_at DESC");

$total_masuk = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as total FROM surat_masuk"))['total'];
$total_keluar = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as total FROM surat_keluar"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correspondence - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .mail-layout { margin-top: 24px; }
        .mail-nav { display: flex; gap: 32px; margin-bottom: 32px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-link { padding: 16px 0; color: rgba(255,255,255,0.4); text-decoration: none; font-weight: 700; position: relative; display: flex; align-items: center; gap: 8px; }
        .nav-link.active { color: white; }
        .nav-link.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 3px; background: var(--primary-color); border-radius: 3px; box-shadow: 0 0 10px var(--primary-color); }
        .nav-count { font-size: 0.65rem; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 10px; opacity: 0.6; }

        .mail-row { transition: 0.3s; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .mail-row:hover { background: rgba(255,255,255,0.02); }
        .mail-brief { display: flex; align-items: center; gap: 20px; padding: 16px 24px; }
        
        .type-pill { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1); }
        .p-Undangan { color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); }
        .p-Penting { color: #ef4444; border-color: rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Correspondence Center</h1>
                    <p>Pemantauan alur persuratan resmi institusi secara terstruktur.</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary"><i data-lucide="plus"></i> Registrasi Surat</button>
                </div>
            </header>

            <div class="mail-layout">
                <nav class="mail-nav">
                    <a href="correspondence.php?type=Masuk" class="nav-link <?= $type == 'Masuk' ? 'active' : '' ?>">
                        <i data-lucide="inbox"></i> Surat Masuk <span class="nav-count"><?= $total_masuk ?></span>
                    </a>
                    <a href="correspondence.php?type=Keluar" class="nav-link <?= $type == 'Keluar' ? 'active' : '' ?>">
                        <i data-lucide="send"></i> Surat Keluar <span class="nav-count"><?= $total_keluar ?></span>
                    </a>
                </nav>

                <div class="glass-card" style="padding:0;">
                    <div style="padding:16px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:0.75rem; opacity:0.4;">Menampilkan arsip terbaru.</span>
                        <div style="display:flex; gap:12px;">
                            <input type="text" placeholder="Search mail..." style="width:200px; padding:6px 12px; font-size:0.8rem;">
                        </div>
                    </div>

                    <div class="mail-list">
                        <?php if(mysqli_num_rows($data) > 0): ?>
                            <?php while($m = mysqli_fetch_assoc($data)): ?>
                            <div class="mail-row">
                                <div class="mail-brief">
                                    <div style="flex: 1;">
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <span class="type-pill p-<?= $m['jenis_surat'] ?>"><?= $m['jenis_surat'] ?></span>
                                            <strong style="font-size:0.95rem;"><?= h($m['no_surat']) ?></strong>
                                        </div>
                                        <p style="margin:8px 0 0 0; font-size:0.9rem; opacity:0.7;"><?= h($m['perihal']) ?></p>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:700; font-size:0.85rem;"><?= h($type == 'Masuk' ? $m['pengirim'] : $m['tujuan']) ?></div>
                                        <small style="opacity:0.4;"><?= date('d M Y', strtotime($m['tgl_surat'])) ?></small>
                                    </div>
                                    <div style="display:flex; gap:8px;">
                                        <button class="icon-btn" style="width:32px; height:32px;"><i data-lucide="external-link" size="14"></i></button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="padding:100px 0; text-align:center; opacity:0.1;">
                                <i data-lucide="mail-x" style="width:64px; height:64px; margin-bottom:16px;"></i>
                                <p>Tidak ada surat ditemukan.</p>
                            </div>
                        <?php endif; ?>
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







