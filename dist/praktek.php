<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Kredensial Clinical Perawat

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS jenis_kredensial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(100) UNIQUE
)");

safe_query("CREATE TABLE IF NOT EXISTS master_komponen_praktek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_set VARCHAR(255) UNIQUE
)");

safe_query("CREATE TABLE IF NOT EXISTS nilai_praktek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perawat_id INT,
    kategori VARCHAR(100),
    nilai_akhir FLOAT,
    status ENUM('Lulus', 'Remedial'),
    tanggal_input DATETIME
)");

safe_query("CREATE TABLE IF NOT EXISTS nilai_praktek_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nilai_id INT,
    komponen_id INT,
    nilai FLOAT
)");

// Seed default components if empty
$check_comp = safe_query("SELECT id FROM master_komponen_praktek LIMIT 1");
if (mysqli_num_rows($check_comp) == 0) {
    mysqli_query($conn, "INSERT INTO master_komponen_praktek (nama_set) VALUES 
        ('Ketepatan Prosedur'), ('Kecepatan Tindakan'), ('Sterilitas'), 
        ('Interaksi Pasien'), ('Dokumentasi'), ('Penggunaan Alat')");
}

$check_types = safe_query("SELECT id FROM jenis_kredensial LIMIT 1");
if (mysqli_num_rows($check_types) == 0) {
    mysqli_query($conn, "INSERT INTO jenis_kredensial (nama_jenis) VALUES 
        ('PK 1 (Novice)'), ('PK 2 (Advanced Beginner)'), ('PK 3 (Competent)'), ('PK 4 (Proficient)')");
}

// Ambil daftar komponen
$komponen = [];
$qKomponen = safe_query("SELECT id, nama_set FROM master_komponen_praktek ORDER BY id ASC");
while($k = mysqli_fetch_assoc($qKomponen)){
    $komponen[$k['id']] = $k['nama_set'];
}

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $perawat_id = intval($_POST['perawat_id']);
    $kategori_id = intval($_POST['kategori_id']);
    
    $katRow = mysqli_fetch_assoc(safe_query("SELECT nama_jenis FROM jenis_kredensial WHERE id = ?", [$kategori_id]));
    $kategori = $katRow['nama_jenis'];
    
    $total = 0;
    $nilai_input = [];
    foreach($komponen as $id_kom => $nama_kom){
        $val = floatval($_POST['kom_'.$id_kom] ?? 0);
        $nilai_input[$id_kom] = $val;
        $total += $val;
    }
    
    $nilai_akhir = count($komponen) > 0 ? $total / count($komponen) : 0;
    $status = ($nilai_akhir >= 75) ? 'Lulus' : 'Remedial';
    
    $res = safe_query("INSERT INTO nilai_praktek (perawat_id, kategori, nilai_akhir, status, tanggal_input) VALUES (?, ?, ?, ?, NOW())", 
           [$perawat_id, $kategori, $nilai_akhir, $status]);
           
    if($res){
        $id_nilai = mysqli_insert_id($conn);
        foreach($nilai_input as $id_kom => $v){
            safe_query("INSERT INTO nilai_praktek_detail (nilai_id, komponen_id, nilai) VALUES (?, ?, ?)", [$id_nilai, $id_kom, $v]);
        }
        write_log("CREDENTIAL_LOG", "Input nilai praktek untuk perawat ID $perawat_id - Status: $status");
        $_SESSION['flash_success'] = "Penilaian berhasil disimpan.";
    }
    header("Location: praktek.php");
    exit;
}

// Filter data
$keyword = $_GET['keyword'] ?? '';
$where = $keyword ? "WHERE u.nama_lengkap LIKE '%$keyword%'" : "";
$result = safe_query("SELECT n.*, u.nama_lengkap as perawat_nama 
                     FROM nilai_praktek n 
                     JOIN users u ON n.perawat_id = u.id 
                     $where ORDER BY n.tanggal_input DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredensial Clinical Perawat - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .credential-container { margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .modal-overlay {
            position: fixed;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-box {
            background: #1a1a1a;
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            padding: 32px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .btn-lulus { color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 4px 12px; border-radius: 6px; }
        .btn-remed { color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 4px 12px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Clinical Privilege Perawat</h1>
                    <p>Evaluasi kompetensi perawat melalui ujian praktek klinis.</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="$('#modalInput').css('display','flex')">
                        <i data-lucide="plus-circle"></i> Input Penilaian
                    </button>
                </div>
            </header>

            <div class="credential-container">
                <div class="premium-card">
                    <div style="margin-bottom: 24px;">
                        <form method="GET">
                            <input type="text" name="keyword" value="<?= h($keyword) ?>" placeholder="Cari nama perawat..." style="width:300px;">
                            <button type="submit" class="btn btn-outline" style="padding: 10px 20px;">Cari</button>
                        </form>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Perawat</th>
                                    <th>Kategori</th>
                                    <th>Nilai Akhir</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_input'])) ?></td>
                                        <td><strong><?= h($row['perawat_nama']) ?></strong></td>
                                        <td><?= h($row['kategori']) ?></td>
                                        <td><strong style="color:var(--primary-color)"><?= $row['nilai_akhir'] ?></strong></td>
                                        <td>
                                            <span class="<?= $row['status'] == 'Lulus' ? 'btn-lulus' : 'btn-remed' ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm">PDF</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL INPUT -->
    <div class="modal-overlay" id="modalInput">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="color: var(--primary-color);">Form Penilaian Praktek</h2>
                <span onclick="$('#modalInput').hide()" style="cursor:pointer; opacity:0.5;">
                    <i data-lucide="x"></i>
                </span>
            </div>
            
            <form method="POST">
                <?= csrf_field(); ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="input-group">
                        <label>Pilih Perawat</label>
                        <select name="perawat_id" required>
                            <option value="">-- Pilih --</option>
                            <?php 
                            $res_p = safe_query("SELECT id, nama_lengkap FROM users WHERE jabatan LIKE '%Perawat%'");
                            while($p = mysqli_fetch_assoc($res_p)) echo "<option value='{$p['id']}'>{$p['nama_lengkap']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Level Kredensial</label>
                        <select name="kategori_id" required>
                            <?php 
                            $res_k = safe_query("SELECT * FROM jenis_kredensial");
                            while($k = mysqli_fetch_assoc($res_k)) echo "<option value='{$k['id']}'>{$k['nama_jenis']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <h3 style="margin-bottom: 16px; font-size: 1rem; opacity: 0.8;">Skor Komponen (0 - 100)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px;">
                    <?php foreach($komponen as $id => $nama): ?>
                        <div class="input-group">
                            <label><?= $nama ?></label>
                            <input type="number" name="kom_<?= $id ?>" min="0" max="100" required>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="$('#modalInput').hide()">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-primary" style="width: 150px;">Simpan Nilai</button>
                </div>
            </form>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
