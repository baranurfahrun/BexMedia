<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Handling Time Analytics

$user_id = $_SESSION['user_id'] ?? 0;

$filter_type = $_GET['type'] ?? 'hardware';
$table = match($filter_type) {
    'software' => 'tiket_it_software',
    'sarpras' => 'tiket_sarpras',
    default => 'tiket_it_hardware',
};

// Summary Logic
$total_pending = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as c FROM $table WHERE status='menunggu'"))['c'];
$total_process = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as c FROM $table WHERE status='diproses'"))['c'];
$total_done    = mysqli_fetch_assoc(safe_query("SELECT COUNT(*) as c FROM $table WHERE status IN ('selesai', 'verified')"))['c'];

// List Data
$data = safe_query("SELECT *, 
    TIMESTAMPDIFF(MINUTE, tanggal_input, IFNULL(tanggal_selesai, NOW())) as durasi_menit 
    FROM $table ORDER BY tanggal_input DESC LIMIT 50");

function format_durasi($menit) {
    if($menit < 60) return $menit . "m";
    $jam = floor($menit / 60);
    $rem = $menit % 60;
    return $jam . "j " . $rem . "m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handling Time - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 24px; }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .stat-value { font-size: 3rem; font-weight: 800; display: block; margin: 12px 0; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 32px;
        }
        .filter-nav { display: flex; gap: 12px; margin-bottom: 32px; }
        .filter-btn {
            background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);
            padding: 10px 24px; border-radius: 12px; text-decoration: none; font-weight: 600;
            transition: 0.3s;
        }
        .filter-btn.active { background: var(--primary-color); border-color: var(--primary-color); }
        .durasi-pill {
            padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Handling Time Analysis</h1>
                    <p>Pemantauan efisiensi dan performa tim pendukung teknis dalam menyelesaikan laporan.</p>
                </div>
            </header>

            <div class="filter-nav">
                <a href="?type=hardware" class="filter-btn <?= $filter_type=='hardware'?'active':'' ?>">Hardware</a>
                <a href="?type=software" class="filter-btn <?= $filter_type=='software'?'active':'' ?>">Software</a>
                <a href="?type=sarpras" class="filter-btn <?= $filter_type=='sarpras'?'active':'' ?>">Sarpras</a>
            </div>

            <div class="analytics-grid">
                <div class="stat-card">
                    <small style="opacity:0.5; text-transform:uppercase; letter-spacing:1px;">Pending</small>
                    <span class="stat-value" style="color:#f59e0b;"><?= $total_pending ?></span>
                    <p style="font-size:0.85rem; opacity:0.6;">Menunggu Respon</p>
                </div>
                <div class="stat-card">
                    <small style="opacity:0.5; text-transform:uppercase; letter-spacing:1px;">On Progress</small>
                    <span class="stat-value" style="color:#3b82f6;"><?= $total_process ?></span>
                    <p style="font-size:0.85rem; opacity:0.6;">Sedang Dikerjakan</p>
                </div>
                <div class="stat-card">
                    <small style="opacity:0.5; text-transform:uppercase; letter-spacing:1px;">Completed</small>
                    <span class="stat-value" style="color:#10b981;"><?= $total_done ?></span>
                    <p style="font-size:0.85rem; opacity:0.6;">Selesai Teratasi</p>
                </div>
            </div>

            <div class="premium-card">
                <h3 style="margin-bottom: 24px;">Log Penanganan Terakhir</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tiket</th>
                            <th>Kategori</th>
                            <th>Lama Penanganan</th>
                            <th>Status Akhir</th>
                            <th>Petugas / Teknisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td>
                                    <strong><?= h($row['nomor_tiket']) ?></strong><br>
                                    <small style="opacity:0.5;"><?= date('d M, H:i', strtotime($row['tanggal_input'])) ?></small>
                                </td>
                                <td><?= h($row['kategori']) ?></td>
                                <td><span class="durasi-pill"><?= format_durasi($row['durasi_menit']) ?></span></td>
                                <td><?= h(strtoupper($row['status'])) ?></td>
                                <td><?= h($row['teknisi_nama'] ?? 'Tim IT') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
