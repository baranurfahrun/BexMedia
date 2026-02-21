<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Mobile JKN Monitoring

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS mjk_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT,
    tahun INT,
    total_sep INT,
    total_mjkn INT,
    persen FLOAT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Input
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $sep = intval($_POST['sep']);
    $mjkn = intval($_POST['mjkn']);
    $persen = ($sep > 0) ? round(($mjkn / $sep) * 100, 2) : 0;
    
    $res = safe_query("INSERT INTO mjk_performance (bulan, tahun, total_sep, total_mjkn, persen, petugas_id) 
                       VALUES (?, ?, ?, ?, ?, ?)", 
                       [$bulan, $tahun, $sep, $mjkn, $persen, $user_id]);
    
    if($res) {
        write_log("MJKN_DATA_INPUT", "Input data capaian Mobile JKN periode $bulan/$tahun");
        $_SESSION['flash_success'] = "Data performa MJKN periode $bulan/$tahun berhasil disimpan.";
    }
    header("Location: mjkn_antrian.php");
    exit;
}

$bulan_nama = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"Mei", 6=>"Jun", 7=>"Jul", 8=>"Agu", 9=>"Sep", 10=>"Okt", 11=>"Nov", 12=>"Des"];
$data_history = safe_query("SELECT * FROM mjk_performance ORDER BY tahun DESC, bulan DESC LIMIT 12");

$labels = []; $values = [];
$temp_data = [];
while($row = mysqli_fetch_assoc($data_history)) {
    $temp_data[] = $row;
}
$temp_data = array_reverse($temp_data);
foreach($temp_data as $row) {
    $labels[] = $bulan_nama[$row['bulan']] . " " . $row['tahun'];
    $values[] = $row['persen'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile JKN Analytics - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .mjkn-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .chart-container { height: 400px; position: relative; }
        .form-row { margin-bottom: 20px; }
        .target-badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .target-met { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .target-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Mobile JKN Monitoring</h1>
                    <p>Pemantauan utilisasi sistem antrian online Mobile JKN terhadap total pendaftaran pasien BPJS.</p>
                </div>
            </header>

            <div class="mjkn-grid">
                <!-- CHART -->
                <div class="premium-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3>Tren Capaian Pemanfaatan</h3>
                        <div style="font-size: 0.8rem; opacity: 0.5;">Target Nasional: 95%</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="mjknChart"></canvas>
                    </div>
                </div>

                <!-- INPUT FORM -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Input Capaian Bulanan</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Periode Laporan</label>
                            <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:12px;">
                                <select name="bulan" required>
                                    <?php foreach($bulan_nama as $num => $nm) echo "<option value='$num' ".(date('n')==$num?'selected':'').">$nm</option>"; ?>
                                </select>
                                <select name="tahun" required>
                                    <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                                    <option value="<?= date('Y')-1 ?>"><?= date('Y')-1 ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <label>Total SEP Terbit</label>
                            <input type="number" name="sep" placeholder="Jumlah kunjungan BPJS" required>
                        </div>
                        <div class="form-row">
                            <label>Antrian via MJKN</label>
                            <input type="number" name="mjkn" placeholder="Jumlah antrian sukses" required>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="save"></i> Update Laporan
                        </button>
                    </form>

                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="margin-bottom: 16px; font-size: 0.9rem; opacity:0.6;">Daftar Terakhir</h4>
                        <?php foreach(array_reverse($temp_data) as $row): ?>
                            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 12px;">
                                <span><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></span>
                                <span class="target-badge <?= $row['persen'] >= 95 ? 'target-met' : 'target-failed' ?>">
                                    <?= $row['persen'] ?>%
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        const ctx = document.getElementById('mjknChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Utilisasi MJKN (%)',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6'
                }, {
                    label: 'Target (95%)',
                    data: Array(<?= count($labels) ?>).fill(95),
                    borderColor: 'rgba(239, 68, 68, 0.5)',
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 50,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: 'rgba(255,255,255,0.5)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(255,255,255,0.5)' }
                    }
                }
            }
        });
    </script>
</body>
</html>
