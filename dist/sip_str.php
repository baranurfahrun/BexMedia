<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Compliance SIP & STR

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS data_sip_str (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nama VARCHAR(255),
    nik VARCHAR(50),
    jabatan VARCHAR(255),
    unit_kerja VARCHAR(255),
    no_sip VARCHAR(100),
    masa_berlaku DATE,
    file_upload VARCHAR(255),
    waktu_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $selected_id = intval($_POST['user_id']);
    $userRow = mysqli_fetch_assoc(safe_query("SELECT * FROM users WHERE id = ?", [$selected_id]));
    
    $no_sip = cleanInput($_POST['no_sip']);
    $masa_berlaku = cleanInput($_POST['masa_berlaku']);
    
    // File handling
    $uploadDir = "uploads/sip/";
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    
    $fileName = $_FILES['file_upload']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newName = uniqid() . '.' . $fileExt;
    
    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadDir . $newName)) {
        $res = safe_query("INSERT INTO data_sip_str (user_id, nama, nik, jabatan, unit_kerja, no_sip, masa_berlaku, file_upload) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                           [$selected_id, $userRow['nama_lengkap'], $userRow['nik'], $userRow['jabatan'], $userRow['unit_kerja'], $no_sip, $masa_berlaku, $newName]);
        
        if ($res) {
            write_log("COMPLIANCE_STR_SIP", "Input data SIP/STR untuk {$userRow['nama_lengkap']}");
            $_SESSION['flash_success'] = "Data SIP/STR berhasil disimpan.";
        }
    }
    header("Location: sip_str.php");
    exit;
}

$data_sip = safe_query("SELECT * FROM data_sip_str ORDER BY masa_berlaku ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIP & STR Compliance - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .compliance-grid { margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .pill-safe { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .pill-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .pill-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .pill-expired { background: rgba(255, 255, 255, 0.05); color: #666; text-decoration: line-through; }
        
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
                    <h1>SIP & STR Compliance</h1>
                    <p>Monitoring masa berlaku Surat Izin Praktik dan Surat Tanda Registrasi tenaga medis.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('data')">Monitoring</button>
                        <button class="tab-btn" onclick="showTab('input')">Update Data</button>
                    </div>
                </div>
            </header>

            <div class="compliance-grid">
                <!-- DATA MONITORING -->
                <div id="tab-data" class="tab-content active">
                    <div class="premium-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>No. Dokumen</th>
                                    <th>Masa Berlaku</th>
                                    <th>Sisa Hari</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $today = new DateTime();
                                while ($d = mysqli_fetch_assoc($data_sip)): 
                                    $exp = new DateTime($d['masa_berlaku']);
                                    $diff = $today->diff($exp);
                                    $days = ($exp < $today) ? -$diff->days : $diff->days;
                                    
                                    $status_class = 'pill-safe';
                                    $status_text = 'Secure';
                                    if ($days < 0) { $status_class = 'pill-expired'; $status_text = 'Expired'; }
                                    elseif ($days < 30) { $status_class = 'pill-danger'; $status_text = 'Critical'; }
                                    elseif ($days < 90) { $status_class = 'pill-warning'; $status_text = 'Restricted'; }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($d['nama']) ?></strong><br>
                                            <small style="opacity:0.5"><?= h($d['jabatan']) ?></small>
                                        </td>
                                        <td><code><?= h($d['no_sip']) ?></code></td>
                                        <td><?= date('d/m/Y', strtotime($d['masa_berlaku'])) ?></td>
                                        <td>
                                            <span style="color: <?= $days < 30 ? '#ef4444' : ($days < 90 ? '#f59e0b' : '#10b981') ?>">
                                                <?= $days ?> Hari
                                            </span>
                                        </td>
                                        <td><span class="status-pill <?= $status_class ?>"><?= $status_text ?></span></td>
                                        <td>
                                            <a href="uploads/sip/<?= $d['file_upload'] ?>" target="_blank" class="btn btn-outline btn-sm">PDF</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- INPUT TAB -->
                <div id="tab-input" class="tab-content">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field(); ?>
                        <div class="premium-card" style="max-width: 600px; margin-inline: auto;">
                            <div class="input-group" style="margin-bottom: 20px;">
                                <label>Pilih Pegawai</label>
                                <select name="user_id" required>
                                    <option value="">-- Cari Pegawai --</option>
                                    <?php 
                                    $res_u = safe_query("SELECT id, nama_lengkap, nik FROM users ORDER BY nama_lengkap ASC");
                                    while($u = mysqli_fetch_assoc($res_u)) echo "<option value='{$u['id']}'>{$u['nama_lengkap']} ({$u['nik']})</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="input-group" style="margin-bottom: 20px;">
                                <label>Nomor SIP / STR</label>
                                <input type="text" name="no_sip" placeholder="Contoh: 440/001/SIP/VIII/2026" required>
                            </div>
                            <div class="input-group" style="margin-bottom: 20px;">
                                <label>Masa Berlaku Berakhir</label>
                                <input type="date" name="masa_berlaku" required>
                            </div>
                            <div class="input-group" style="margin-bottom: 32px;">
                                <label>Upload Scan Dokumen (PDF/JPG)</label>
                                <input type="file" name="file_upload" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary" style="width: 100%;">
                                <i data-lucide="upload-cloud"></i> Submit Data Compliance
                            </button>
                        </div>
                    </form>
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
