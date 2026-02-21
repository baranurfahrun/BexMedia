<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Outgoing Letters Registry

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS surat_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_surat VARCHAR(100),
    tgl_surat DATE,
    tujuan VARCHAR(255),
    perihal TEXT,
    isi_ringkas TEXT,
    file_path VARCHAR(255),
    balasan_untuk_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $no_surat = cleanInput($_POST['no_surat']);
    $tgl_surat = cleanInput($_POST['tgl_surat']);
    $tujuan = cleanInput($_POST['tujuan']);
    $perihal = cleanInput($_POST['perihal']);
    $isi = cleanInput($_POST['isi']);
    $balasan_id = empty($_POST['balasan_id']) ? null : intval($_POST['balasan_id']);
    
    // File handling
    $uploadDir = "uploads/surat_keluar/";
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    
    $newName = null;
    if ($_FILES['file_surat']['name']) {
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $newName = uniqid('SK_') . '.' . $ext;
        move_uploaded_file($_FILES['file_surat']['tmp_name'], $uploadDir . $newName);
    }
    
    $res = safe_query("INSERT INTO surat_keluar (no_surat, tgl_surat, tujuan, perihal, isi_ringkas, file_path, balasan_untuk_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)", 
                       [$no_surat, $tgl_surat, $tujuan, $perihal, $isi, $newName, $balasan_id]);
    
    if($res) {
        write_log("LETTER_OUT_CREATE", "Input surat keluar: $no_surat kepada $tujuan");
        $_SESSION['flash_success'] = "Surat keluar berhasil diregistrasikan.";
    }
    header("Location: surat_keluar.php");
    exit;
}

$data_surat = safe_query("SELECT sk.*, sm.no_surat as ref_no FROM surat_keluar sk LEFT JOIN surat_masuk sm ON sk.balasan_untuk_id = sm.id ORDER BY sk.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keluar - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .letter-grid { margin-top: 24px; }
        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;
        }
        .tab-btn {
            background: none; border: none; color: var(--text-muted); padding: 12px 24px; cursor: pointer; font-weight: 600; border-bottom: 2px solid transparent; transition: 0.3s;
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
                    <h1>Outgoing Letters Registry</h1>
                    <p>Dokumentasi korespondensi keluar untuk pelacakan arsip yang akurat.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('data')">Arsip</button>
                        <button class="tab-btn" onclick="showTab('input')">Registrasi Baru</button>
                    </div>
                </div>
            </header>

            <div class="letter-grid">
                <!-- DATA TAB -->
                <div id="tab-data" class="tab-content active">
                    <div class="premium-card">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No. Surat</th>
                                    <th>Tujuan</th>
                                    <th>Tanggal</th>
                                    <th>Ref. Masuk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($data_surat)): ?>
                                    <tr>
                                        <td><strong><?= h($d['no_surat']) ?></strong><br><small style="opacity:0.5"><?= h($d['perihal']) ?></small></td>
                                        <td><?= h($d['tujuan']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($d['tgl_surat'])) ?></td>
                                        <td><?= h($d['ref_no'] ?: '-') ?></td>
                                        <td>
                                            <?php if($d['file_path']): ?>
                                                <a href="uploads/surat_keluar/<?= $d['file_path'] ?>" target="_blank" class="btn btn-outline btn-sm">PDF</a>
                                            <?php else: ?>
                                                <span style="opacity:0.3">-</span>
                                            <?php endif; ?>
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
                        <div class="premium-card">
                            <div class="form-grid">
                                <div class="input-group">
                                    <label>Nomor Surat</label>
                                    <input type="text" name="no_surat" placeholder="Contoh: 021/BM/DIR/II/2026" required>
                                </div>
                                <div class="input-group">
                                    <label>Tanggal Surat</label>
                                    <input type="date" name="tgl_surat" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="input-group">
                                    <label>Tujuan / Penerima</label>
                                    <input type="text" name="tujuan" placeholder="Nama Instansi/Pejabat Tujuan" required>
                                </div>
                                <div class="input-group">
                                    <label>Balasan Dari (Optional)</label>
                                    <select name="balasan_id">
                                        <option value="">-- Bukan Surat Balasan --</option>
                                        <?php 
                                        $res_sm = safe_query("SELECT id, no_surat FROM surat_masuk ORDER BY created_at DESC LIMIT 20");
                                        while($sm = mysqli_fetch_assoc($res_sm)) echo "<option value='{$sm['id']}'>[{$sm['no_surat']}]</option>";
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="input-group" style="margin-bottom: 20px;">
                                <label>Perihal</label>
                                <input type="text" name="perihal" placeholder="Subjek surat" required>
                            </div>

                            <div class="input-group" style="margin-bottom: 24px;">
                                <label>Ringkasan Isi</label>
                                <textarea name="isi" rows="4" placeholder="Tuliskan intisari surat keluar..." required></textarea>
                            </div>

                            <div class="input-group" style="margin-bottom: 32px;">
                                <label>Upload Scan Surat (PDF)</label>
                                <input type="file" name="file_surat" accept=".pdf">
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary" style="padding: 16px 48px;">
                                <i data-lucide="send"></i> Registrasi Surat Keluar
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
