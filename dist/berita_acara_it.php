<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Official IT Reports (Berita Acara)

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS berita_acara_it (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_ba VARCHAR(50) UNIQUE,
    nomor_tiket VARCHAR(50),
    jenis ENUM('Software', 'Hardware') DEFAULT 'Hardware',
    tanggal_ba DATETIME DEFAULT CURRENT_TIMESTAMP,
    pelapor_nama VARCHAR(255),
    unit_kerja VARCHAR(100),
    deskripsi_masalah TEXT,
    solusi_teknis TEXT,
    teknisi_nama VARCHAR(100),
    status ENUM('Draft', 'Signed', 'Archived') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Search/Filter
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%%";
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : "Hardware";

$data_ba = safe_query("SELECT * FROM berita_acara_it WHERE jenis = ? AND (nomor_ba LIKE ? OR pelapor_nama LIKE ? OR nomor_tiket LIKE ?) ORDER BY tanggal_ba DESC", [$jenis, $search, $search, $search]);

// Summary Counts
$count_hw = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as total FROM berita_acara_it WHERE jenis = 'Hardware'"))['total'] ?? 0;
$count_sw = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as total FROM berita_acara_it WHERE jenis = 'Software'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara IT - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .ba-container { margin-top: 24px; }
        .stats-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        .stat-card.active { border-color: var(--primary-color); background: rgba(59, 130, 246, 0.05); }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; background: rgba(59, 130, 246, 0.1); color: var(--primary-color); }
        
        .ba-list-card { background: rgba(255, 255, 255, 0.03); border-radius: 24px; padding: 32px; border: 1px solid rgba(255, 255, 255, 0.1); }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .st-Draft { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.4); }
        .st-Signed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .st-Archived { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Official Reports Archive</h1>
                    <p>Arsip digital Berita Acara (BA) penyelesaian kendala infrastruktur dan sistem informasi.</p>
                </div>
                <div class="header-right">
                    <div style="position:relative;">
                        <i data-lucide="search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; opacity:0.3;"></i>
                        <input type="text" id="baSearch" placeholder="Cari Berita Acara..." style="padding-left:40px; width:280px;">
                    </div>
                </div>
            </header>

            <div class="ba-container">
                <div class="stats-row">
                    <div class="stat-card <?= $jenis == 'Hardware' ? 'active' : '' ?>" onclick="window.location='berita_acara_it.php?jenis=Hardware'">
                        <div class="icon"><i data-lucide="cpu"></i></div>
                        <div class="info">
                            <span style="display:block; font-size:1.5rem; font-weight:800;"><?= $count_hw ?></span>
                            <span style="font-size:0.75rem; letter-spacing:1px; opacity:0.4; text-transform:uppercase;">Hardware Reports</span>
                        </div>
                    </div>
                    <div class="stat-card <?= $jenis == 'Software' ? 'active' : '' ?>" onclick="window.location='berita_acara_it.php?jenis=Software'">
                        <div class="icon" style="color:#10b981; background:rgba(16,185,129,0.1);"><i data-lucide="code"></i></div>
                        <div class="info">
                            <span style="display:block; font-size:1.5rem; font-weight:800;"><?= $count_sw ?></span>
                            <span style="font-size:0.75rem; letter-spacing:1px; opacity:0.4; text-transform:uppercase;">Software Reports</span>
                        </div>
                    </div>
                </div>

                <div class="ba-list-card">
                    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:32px;">
                        <h3>Daftar Berita Acara : <?= $jenis ?></h3>
                        <button class="btn btn-primary" onclick="alert('Pilih tiket yang sudah berstatus SELESAI untuk membuat Berita Acara otomatis.')"><i data-lucide="plus"></i> Buat BA Baru</button>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Berita Acara</th>
                                <th>Unit / Pelapor</th>
                                <th>Tanggal BA</th>
                                <th>Kendala / Solusi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="baBody">
                            <?php if(mysqli_num_rows($data_ba) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($data_ba)): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700;"><?= $row['nomor_ba'] ?></div>
                                        <small style="opacity:0.4;">Ref: <?= $row['nomor_tiket'] ?></small>
                                    </td>
                                    <td>
                                        <div><?= h($row['pelapor_nama']) ?></div>
                                        <small style="opacity:0.4;"><?= h($row['unit_kerja']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_ba'])) ?></td>
                                    <td>
                                        <div style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.85rem;" title="<?= h($row['deskripsi_masalah']) ?>">
                                            <?= h($row['deskripsi_masalah']) ?>
                                        </div>
                                        <small style="color:var(--primary-color)">Done by <?= $row['teknisi_nama'] ?></small>
                                    </td>
                                    <td><span class="status-badge st-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                                    <td>
                                        <div style="display:flex; gap:8px;">
                                            <button class="btn btn-secondary" style="padding:4px;" title="Print BA"><i data-lucide="printer"></i></button>
                                            <button class="btn btn-secondary" style="padding:4px;" title="Electronic Signature"><i data-lucide="shield-check"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:80px 0; opacity:0.1;">
                                        <i data-lucide="file-x" style="width:64px; height:64px; margin-bottom:16px;"></i>
                                        <p>Belum ada arsip Berita Acara untuk kategori ini.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        $('#baSearch').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            $("#baBody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
            });
        });
    </script>
</body>
</html>
