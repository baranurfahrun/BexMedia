<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: All Queuing Statistics

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS semua_antrian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT,
    tahun INT,
    jumlah_sep INT,
    jumlah_antri INT,
    jumlah_mjkn INT,
    persen_all FLOAT,
    persen_mjkn FLOAT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $bulan = intval($_POST['bulan']);
    $tahun = intval($_POST['tahun']);
    $sep = intval($_POST['sep']);
    $antri = intval($_POST['antri']);
    $mjkn = intval($_POST['mjkn']);
    
    $persen_all = ($sep > 0) ? round(($antri / $sep) * 100, 2) : 0;
    $persen_mjkn = ($sep > 0) ? round(($mjkn / $sep) * 100, 2) : 0;
    
    $res = safe_query("INSERT INTO semua_antrian (bulan, tahun, jumlah_sep, jumlah_antri, jumlah_mjkn, persen_all, persen_mjkn, petugas_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$bulan, $tahun, $sep, $antri, $mjkn, $persen_all, $persen_mjkn, $user_id]);
    
    if($res) {
        write_log("ANTRIAN_ALL_INPUT", "Input data pemanfaatan antrian online periode $bulan/$tahun");
        $_SESSION['flash_success'] = "Data utilisasi antrian periode $bulan/$tahun berhasil disimpan.";
    }
    header("Location: semua_antrian.php");
    exit;
}

$bulan_nama = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"Mei", 6=>"Jun", 7=>"Jul", 8=>"Agu", 9=>"Sep", 10=>"Okt", 11=>"Nov", 12=>"Des"];
$data_history = safe_query("SELECT * FROM semua_antrian ORDER BY tahun DESC, bulan DESC LIMIT 12");

$labels = []; $values_all = []; $values_mjkn = [];
$temp_data = [];
while($row = mysqli_fetch_assoc($data_history)) {
    $temp_data[] = $row;
}
$temp_data = array_reverse($temp_data);
foreach($temp_data as $row) {
    $labels[] = $bulan_nama[$row['bulan']] . " " . $row['tahun'];
    $values_all[] = $row['persen_all'];
    $values_mjkn[] = $row['persen_mjkn'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Queuing Analytics - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .antrian-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px; margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .chart-container { height: 400px; position: relative; }
        .form-row { margin-bottom: 20px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .data-table th, .data-table td { padding: 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .tag { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .tag-blue { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .tag-green { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Antrian Online (Global)</h1>
                    <p>Statistik pemanfaatan sistem antrian online lintas platform dibandingkan dengan total kunjungan pasien.</p>
                </div>
            </header>

            <div class="antrian-grid">
                <!-- CHART -->
                <div class="premium-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3>Utilisasi Bridging Antrian</h3>
                        <div style="display:flex; gap: 16px;">
                            <span style="display:flex; align-items:center; gap:8px; font-size:0.8rem; opacity:0.6;"><span style="width:12px; height:12px; background:#3b82f6; border-radius:3px;"></span> Semua Platform</span>
                            <span style="display:flex; align-items:center; gap:8px; font-size:0.8rem; opacity:0.6;"><span style="width:12px; height:12px; background:#10b981; border-radius:3px;"></span> Mobile JKN Only</span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="antrianChart"></canvas>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Total SEP</th>
                                <th>Antrian Sukses</th>
                                <th>% Pemanfaatan</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_reverse($temp_data) as $row): ?>
                                <tr>
                                    <td><?= $bulan_nama[$row['bulan']] ?> <?= $row['tahun'] ?></td>
                                    <td><?= number_format($row['jumlah_sep']) ?></td>
                                    <td><?= number_format($row['jumlah_antri']) ?></td>
                                    <td><strong><?= $row['persen_all'] ?>%</strong></td>
                                    <td><span class="tag <?= $row['persen_all'] >= 95 ? 'tag-green' : 'tag-blue' ?>"><?= $row['persen_all'] >= 95 ? 'Met' : 'Standard' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- INPUT -->
                <div class="premium-card">
                    <h3 style="margin-bottom: 24px;">Input Data Capaian</h3>
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="form-row">
                            <label>Periode</label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
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
                            <label>Jumlah SEP (Rawat Jalan)</label>
                            <input type="number" name="sep" placeholder="Total pasien BPJS" required>
                        </div>
                        <div class="form-row">
                            <label>Antrian Online (Semua Platform)</label>
                            <input type="number" name="antri" placeholder="Mobile JKN + Web + Kiosk" required>
                        </div>
                        <div class="form-row">
                            <label>Khusus Mobile JKN</label>
                            <input type="number" name="mjkn" placeholder="Hanya dari App Mobile JKN" required>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary" style="width:100%; padding: 16px;">
                            <i data-lucide="bar-chart"></i> Simpan Statistik
                        </button>
                    </form>

                    <div style="margin-top: 32px; padding: 24px; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1);">
                        <h4 style="margin-bottom: 12px; font-size: 0.9rem;">Analisis Cepat</h4>
                        <p style="font-size: 0.85rem; opacity: 0.6; line-height: 1.6;">Lakukan perbandingan antara utilisasi total dengan kontribusi Mobile JKN untuk mengevaluasi efektivitas kampanye aplikasi BPJS.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        const ctx = document.getElementById('antrianChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Total Pemanfaatan (%)',
                    data: <?= json_encode($values_all) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4
                }, {
                    label: 'Mobile JKN Only (%)',
                    data: <?= json_encode($values_mjkn) ?>,
                    borderColor: '#10b981',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: 'rgba(255,255,255,0.5)', callback: v => v + '%' }
                    },
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)' } }
                }
            }
        });
    </script>
</body>
</html>
