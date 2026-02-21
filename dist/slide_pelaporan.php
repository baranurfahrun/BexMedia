<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Executive Presentation Slides

$user_id = $_SESSION['user_id'] ?? 0;

$bulan_nama = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"Mei", 6=>"Jun", 7=>"Jul", 8=>"Agu", 9=>"Sep", 10=>"Okt", 11=>"Nov", 12=>"Des"];

// Fetch Data for Slides
$q_antri = safe_query("SELECT * FROM semua_antrian ORDER BY tahun DESC, bulan DESC LIMIT 5");
$q_poli = safe_query("SELECT pa.*, p.nama_poli FROM poli_antrian pa JOIN poliklinik p ON pa.id_poli = p.id ORDER BY pa.tahun DESC, pa.bulan DESC LIMIT 5");
$q_mjkn = safe_query("SELECT * FROM mjk_performance ORDER BY tahun DESC, bulan DESC LIMIT 5");
$q_erm = safe_query("SELECT * FROM data_erm ORDER BY created_at DESC LIMIT 5");
$q_sync = safe_query("SELECT * FROM satu_sehat ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Reporting - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .presentation-container {
            max-width: 1200px;
            margin: 40px auto;
            position: relative;
        }
        .slide {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .slide.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            padding: 60px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }
        .slide-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .slide-header h2 { font-size: 2.5rem; margin-bottom: 8px; color: var(--primary-color); }
        .slide-header p { opacity: 0.5; font-size: 1.1rem; }
        
        .slide-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }
        .nav-btn {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all 0.3s ease;
        }
        .nav-btn:hover { background: var(--primary-color); color: #000; }
        
        .slide-counter {
            text-align: center;
            margin-top: 16px;
            font-size: 0.9rem;
            opacity: 0.4;
            letter-spacing: 2px;
        }
        
        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }
        .table-premium th { padding: 12px 24px; text-align: left; opacity: 0.4; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .table-premium td { padding: 24px; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .table-premium tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px 0 0 12px; }
        .table-premium tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 12px 12px 0; }
        
        .percentage-ring { font-weight: 800; color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Executive Presentation Slides</h1>
                    <p>Visualisasi data performa rumah sakit untuk rapat direksi dan pelaporan eksekutif.</p>
                </div>
            </header>

            <div class="presentation-container">
                <!-- SLIDE 1: GLOBAL ANTRIAN -->
                <div class="slide active" id="slide1">
                    <div class="slide-card">
                        <div class="slide-header">
                            <h2>Utilisasi Antrian Global</h2>
                            <p>Performa bridging sistem antrian online terhadap total kunjungan pasien.</p>
                        </div>
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Total SEP</th>
                                    <th>Volume Antrian</th>
                                    <th>Capaian (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($q_antri)): ?>
                                <tr>
                                    <td><?= $bulan_nama[$r['bulan']] ?> <?= $r['tahun'] ?></td>
                                    <td><?= number_format($r['jumlah_sep']) ?></td>
                                    <td><?= number_format($r['jumlah_antri']) ?></td>
                                    <td class="percentage-ring"><?= $r['persen_all'] ?>%</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SLIDE 2: POLI ANTRIAN -->
                <div class="slide" id="slide2">
                    <div class="slide-card">
                        <div class="slide-header">
                            <h2>Efektivitas Per Unit Poli</h2>
                            <p>Distribusi pemanfaatan antrian online di poliklinik spesialis.</p>
                        </div>
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Poliklinik</th>
                                    <th>Periode</th>
                                    <th>Antrian Sukses</th>
                                    <th>Utilisasi (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($q_poli)): ?>
                                <tr>
                                    <td><strong><?= h($r['nama_poli']) ?></strong></td>
                                    <td><?= $bulan_nama[$r['bulan']] ?> <?= $r['tahun'] ?></td>
                                    <td><?= number_format($r['jumlah_antri']) ?></td>
                                    <td class="percentage-ring"><?= $r['persen_all'] ?>%</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SLIDE 3: SATUSEHAT SYNC -->
                <div class="slide" id="slide3">
                    <div class="slide-card">
                        <div class="slide-header">
                            <h2>Status Integrasi SatuSehat</h2>
                            <p>Log transmisi data HL7 FHIR ke platform SatuSehat Kemenkes.</p>
                        </div>
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>FHIR Resource</th>
                                    <th>Periode</th>
                                    <th>Data Records</th>
                                    <th>Sync Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($q_sync)): ?>
                                <tr>
                                    <td><code style="color:var(--primary-color)"><?= h($r['endpoint']) ?></code></td>
                                    <td><?= $bulan_nama[$r['bulan']] ?> <?= $r['tahun'] ?></td>
                                    <td><?= number_format($r['jumlah_data']) ?> records</td>
                                    <td style="color:#10b981; font-weight:700;">VERIFIED</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SLIDE 4: E-RM ADOPTION -->
                <div class="slide" id="slide4">
                    <div class="slide-card">
                        <div class="slide-header">
                            <h2>Adopsi Rekam Medis Elektronik</h2>
                            <p>Rekapitulasi implementasi E-RM per unit pelayanan rumah sakit.</p>
                        </div>
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Unit Pelayanan</th>
                                    <th>Periode</th>
                                    <th>Status Implementasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = mysqli_fetch_assoc($q_erm)): ?>
                                <tr>
                                    <td><strong><?= h($r['nama_unit']) ?></strong></td>
                                    <td><?= $bulan_nama[$r['bulan']] ?> <?= $r['tahun'] ?></td>
                                    <td><?= truncate(h($r['catatan_erm']), 80) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="slide-nav">
                    <button class="nav-btn" onclick="changeSlide(-1)"><i data-lucide="chevron-left"></i></button>
                    <button class="nav-btn" onclick="changeSlide(1)"><i data-lucide="chevron-right"></i></button>
                </div>
                <div class="slide-counter">
                    SLIDE <span id="current-slide">1</span> / 4
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        let currentSlide = 1;
        const totalSlides = 4;
        
        function changeSlide(direction) {
            $(`#slide${currentSlide}`).removeClass('active');
            currentSlide += direction;
            
            if (currentSlide > totalSlides) currentSlide = 1;
            if (currentSlide < 1) currentSlide = totalSlides;
            
            $(`#slide${currentSlide}`).addClass('active');
            $(`#current-slide`).text(currentSlide);
        }
        
        // Auto slide every 10 seconds
        setInterval(() => changeSlide(1), 10000);
    </script>
</body>
</html>
