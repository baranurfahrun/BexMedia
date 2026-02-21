<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Central Digital Archive

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Table
safe_query("CREATE TABLE IF NOT EXISTS arsip_digital (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nama_dokumen VARCHAR(255),
    kategori VARCHAR(100),
    nomor_dokumen VARCHAR(100),
    file_path VARCHAR(255),
    keterangan TEXT,
    tanggal_arsip DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_signed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Categories
$cats = ['SK Direktur', 'Surat Tugas', 'SOP Layanan', 'Dokumen Akreditasi', 'MoU / Kerjasama', 'Lainnya'];

// Handle Search
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%%";
$active_cat = $_GET['cat'] ?? 'All';

$where = "WHERE (nama_dokumen LIKE ? OR nomor_dokumen LIKE ?)";
$params = [$search, $search];

if($active_cat != 'All') {
    $where .= " AND kategori = ?";
    $params[] = $active_cat;
}

$docs = safe_query("SELECT arsip_digital.*, users.nama as pembuat_nama 
                    FROM arsip_digital 
                    JOIN users ON arsip_digital.user_id = users.id 
                    $where ORDER BY tanggal_arsip DESC", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Archive - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .archive-layout { display: grid; grid-template-columns: 280px 1fr; gap: 32px; margin-top: 24px; }
        .sidebar-nav { background: rgba(255, 255, 255, 0.02); border-radius: 20px; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: rgba(255,255,255,0.4); text-decoration: none; transition: 0.3s; margin-bottom: 4px; border: 1px solid transparent; }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { background: rgba(59, 130, 246, 0.1); color: var(--primary-color); border-color: rgba(59, 130, 246, 0.2); }
        
        .doc-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .doc-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            transition: 0.3s;
        }
        .doc-card:hover { transform: translateY(-4px); background: rgba(255,255,255,0.05); border-color: var(--primary-color); }
        .file-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; background: rgba(255,255,255,0.05); color: var(--primary-color); margin-bottom: 16px; }
        
        .badge-tte { position: absolute; top: 12px; right: 12px; display: flex; align-items: center; gap: 4px; font-size: 0.6rem; font-weight: 800; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Digital Repository</h1>
                    <p>Pusat arsip dokumen digital institusi yang terorganisir dan terenkripsi.</p>
                </div>
                <div class="header-right">
                    <div style="display:flex; gap:12px;">
                        <input type="text" id="archiveSearch" placeholder="Cari Surat / SK..." style="width:250px;">
                        <button class="btn btn-primary"><i data-lucide="upload"></i> Upload</button>
                    </div>
                </div>
            </header>

            <div class="archive-layout">
                <aside class="sidebar-nav">
                    <h5 style="margin-bottom:20px; opacity:0.3; text-transform:uppercase; font-size:0.7rem; letter-spacing:1px;">Collections</h5>
                    <a href="arsip_digital.php?cat=All" class="nav-item <?= $active_cat == 'All' ? 'active' : '' ?>">
                        <i data-lucide="layers"></i> Semua Dokumen
                    </a>
                    <?php foreach($cats as $c): ?>
                    <a href="arsip_digital.php?cat=<?= urlencode($c) ?>" class="nav-item <?= $active_cat == $c ? 'active' : '' ?>">
                        <i data-lucide="folder"></i> <?= $c ?>
                    </a>
                    <?php endforeach; ?>
                </aside>

                <div class="doc-list">
                    <?php if(mysqli_num_rows($docs) > 0): ?>
                        <?php while($d = mysqli_fetch_assoc($docs)): ?>
                        <div class="doc-card">
                            <?php if($d['is_signed']): ?>
                            <div class="badge-tte"><i data-lucide="shield-check" size="10"></i> Signed</div>
                            <?php endif; ?>

                            <div class="file-icon"><i data-lucide="file-text"></i></div>
                            <h4 style="margin:0; font-size:0.95rem; line-height:1.4;"><?= h($d['nama_dokumen']) ?></h4>
                            <p style="font-size:0.75rem; opacity:0.4; margin: 8px 0;"><?= h($d['nomor_dokumen']) ?></p>
                            
                            <div style="margin-top:20px; display:flex; justify-content: space-between; align-items:center;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:24px; height:24px; border-radius:50%; background:var(--primary-color); display:grid; place-items:center; font-size:0.6rem; font-weight:800;"><?= strtoupper(substr($d['pembuat_nama'], 0, 1)) ?></div>
                                    <span style="font-size:0.75rem; opacity:0.6;"><?= date('d M Y', strtotime($d['tanggal_arsip'])) ?></span>
                                </div>
                                <div style="display:flex; gap:4px;">
                                    <button class="btn btn-secondary" style="padding:6px;"><i data-lucide="eye" size="14"></i></button>
                                    <button class="btn btn-secondary" style="padding:6px;"><i data-lucide="download" size="14"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align:center; padding:100px 0; opacity:0.1;">
                            <i data-lucide="folder-x" style="width:80px; height:80px; margin-bottom:20px;"></i>
                            <h3>Repository Kosong</h3>
                            <p>Belum ada dokumen di kategori ini.</p>
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
