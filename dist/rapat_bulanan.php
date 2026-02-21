<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Executive Meeting Minutes

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS meeting_minutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_rapat VARCHAR(100),
    tanggal_rapat DATE,
    perihal VARCHAR(255),
    pemimpin_rapat VARCHAR(255),
    notulis VARCHAR(255),
    tempat VARCHAR(255),
    agenda TEXT,
    penerima TEXT,
    status ENUM('draft', 'final') DEFAULT 'draft',
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $tanggal_rapat = cleanInput($_POST['tanggal_rapat']);
    $perihal = cleanInput($_POST['perihal']);
    $pemimpin = cleanInput($_POST['pemimpin']);
    $notulis = cleanInput($_POST['notulis']);
    $tempat = cleanInput($_POST['tempat']);
    $agenda = $_POST['agenda'];
    $penerima = json_encode($_POST['penerima'] ?? []);
    
    // Generate simple order number
    $count = mysqli_fetch_array(safe_query("SELECT COUNT(*) FROM meeting_minutes WHERE YEAR(created_at) = YEAR(NOW())"))[0];
    $nomor = "MTG/".date('Y')."/".str_pad($count+1, 4, '0', STR_PAD_LEFT);
    
    $res = safe_query("INSERT INTO meeting_minutes (nomor_rapat, tanggal_rapat, perihal, pemimpin_rapat, notulis, tempat, agenda, penerima, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')", 
                       [$nomor, $tanggal_rapat, $perihal, $pemimpin, $notulis, $tempat, $agenda, $penerima]);
    
    if($res) {
        write_log("MEETING_MINUTES_CREATE", "Membuat notulen rapat: $perihal ($nomor)");
        $_SESSION['flash_success'] = "Notulen rapat berhasil disimpan sebagai draft.";
    }
    header("Location: rapat_bulanan.php");
    exit;
}

$data_rapat = safe_query("SELECT * FROM meeting_minutes ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Minutes - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .meeting-minutes-grid { margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; }
        .input-group input, .input-group textarea, .input-group select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: white;
        }
        .tab-btn {
            background: none; border: none; color: var(--text-muted);
            padding: 12px 24px; cursor: pointer; font-weight: 600;
            border-bottom: 2px solid transparent; transition: 0.3s;
        }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Executive Meeting Minutes</h1>
                    <p>Dokumentasi resmi hasil rapat koordinasi dan pengambilan keputusan strategis.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('input')">Buat Baru</button>
                        <button class="tab-btn" onclick="showTab('data')">Arsip Notulen</button>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['flash_success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                    <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <div class="meeting-minutes-grid">
                <!-- FORM INPUT -->
                <div id="tab-input" class="tab-content active">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <div class="premium-card">
                            <div class="form-grid">
                                <div class="input-group">
                                    <label>Perihal Rapat</label>
                                    <input type="text" name="perihal" placeholder="Contoh: Rapat Evaluasi Q1 2026" required>
                                </div>
                                <div class="input-group">
                                    <label>Tanggal Rapat</label>
                                    <input type="date" name="tanggal_rapat" required>
                                </div>
                                <div class="input-group">
                                    <label>Pemimpin Rapat</label>
                                    <input type="text" name="pemimpin" placeholder="Nama Pemimpin Rapat" required>
                                </div>
                                <div class="input-group">
                                    <label>Notulis</label>
                                    <input type="text" name="notulis" value="<?= h($_SESSION['username']) ?>" required>
                                </div>
                                <div class="input-group">
                                    <label>Tempat / Medium</label>
                                    <input type="text" name="tempat" placeholder="Contoh: Board Room / Zoom Meeting" required>
                                </div>
                                <div class="input-group">
                                    <label>Penerima (Undangan)</label>
                                    <input type="text" name="penerima[]" placeholder="Nama/Unit Penerima (Pisahkan dengan koma)">
                                </div>
                            </div>

                            <div class="input-group" style="margin-bottom: 32px;">
                                <label>Hasil Keputusan / Agenda</label>
                                <textarea name="agenda" rows="8" placeholder="Tuliskan butir-butir hasil rapat dan rencana aksi selanjutnya..." required></textarea>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary">
                                <i data-lucide="save"></i> Simpan Notulen
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DATA TAB -->
                <div id="tab-data" class="tab-content">
                    <div class="premium-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th>Topik</th>
                                    <th>Pemimpin</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($data_rapat && $data_rapat->num_rows > 0): ?>
                                    <?php while ($d = $data_rapat->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?= $d['nomor_rapat'] ?></strong></td>
                                            <td><?= date('d/m/Y', strtotime($d['tanggal_rapat'])) ?></td>
                                            <td><?= h($d['perihal']) ?></td>
                                            <td><?= h($d['pemimpin_rapat']) ?></td>
                                            <td>
                                                <span class="status-tag <?= $d['status'] == 'final' ? 'tag-success' : 'tag-warning' ?>">
                                                    <?= ucfirst($d['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline btn-sm">View</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align:center; opacity:0.5; padding:40px;">Belum ada arsip notulen.</td></tr>
                                <?php endif; ?>
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
