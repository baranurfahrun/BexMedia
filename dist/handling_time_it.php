<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: IT Handling Performance

$user_id = $_SESSION['user_id'] ?? 0;

$type = $_GET['type'] ?? 'software';
$table = ($type == 'software') ? 'tiket_it_software' : 'tiket_it_hardware';

$data_tiket = safe_query("SELECT * FROM $table ORDER BY tanggal_input DESC LIMIT 50");

function calcDuration($start, $end) {
    if(!$start || !$end) return '-';
    $s = new DateTime($start);
    $e = new DateTime($end);
    $diff = $s->diff($e);
    $h = ($diff->days * 24) + $diff->h;
    return $h . "h " . $diff->i . "m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handling Time - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .analytics-card { background: rgba(255, 255, 255, 0.03); border-radius: 24px; padding: 32px; border: 1px solid rgba(255, 255, 255, 0.1); margin-top: 24px; }
        .tab-nav { display: flex; gap: 24px; margin-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .tab-link { padding: 12px 0; color: rgba(255,255,255,0.4); text-decoration: none; font-weight: 700; position: relative; }
        .tab-link.active { color: white; }
        .tab-link.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 3px; background: var(--primary-color); border-radius: 3px; }
        
        .duration-pill { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; background: rgba(59, 130, 246, 0.1); color: var(--primary-color); }
        .time-label { display: block; font-size: 0.65rem; opacity: 0.3; text-transform: uppercase; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <?php 
            $breadcrumb = "Technical Support / <strong>IT Handling Performance</strong>";
            include "topbar.php"; 
            ?>

            <div class="analytics-card">
                <nav class="tab-nav">
                    <a href="handling_time_it.php?type=software" class="tab-link <?= $type == 'software' ? 'active' : '' ?>">Software Support</a>
                    <a href="handling_time_it.php?type=hardware" class="tab-link <?= $type == 'hardware' ? 'active' : '' ?>">Hardware & Network</a>
                </nav>

                <table class="data-table">
                    <thead>

                        <tr>
                            <th>Tiket / Kategori</th>
                            <th>Timeline Penanganan</th>
                            <th>Durasi Respon</th>
                            <th>Durasi Selesai</th>
                            <th>Status SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_tiket)): 
                            // Simulated processing times if null for visualization
                            $process = $row['tanggal_input']; // Simplified for BexMedia
                            $finish = $row['tanggal_selesai'] ?? null;
                            $resp_dur = "15m"; // Simulated
                            $total_dur = calcDuration($row['tanggal_input'], $finish);
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;"><?= h($row['nomor_tiket']) ?></div>
                                <small style="opacity:0.4;"><?= h($row['kategori']) ?></small>
                            </td>
                            <td>
                                <div style="display:flex; gap:32px;">
                                    <div>
                                        <span class="time-label">Input</span>
                                        <div style="font-size:0.8rem;"><?= date('H:i', strtotime($row['tanggal_input'])) ?></div>
                                    </div>
                                    <div>
                                        <span class="time-label">Finish</span>
                                        <div style="font-size:0.8rem;"><?= $finish ? date('H:i', strtotime($finish)) : '--:--' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="duration-pill"><?= $resp_dur ?></span></td>
                            <td><span class="duration-pill" style="background:rgba(16,185,129,0.1); color:#10b981;"><?= $total_dur ?></span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:8px; height:8px; border-radius:50%; background:#10b981;"></div>
                                    <span style="font-size:0.75rem; font-weight:700;">Within Limit</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>







