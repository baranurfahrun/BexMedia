<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Incoming Letters Archive

$user_id = $_SESSION['user_id'] ?? 0;

// Lazy Load Tables
safe_query("CREATE TABLE IF NOT EXISTS surat_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_surat VARCHAR(100),
    tgl_surat DATE,
    tgl_terima DATE,
    pengirim VARCHAR(255),
    perihal TEXT,
    sifat ENUM('biasa', 'penting', 'rahasia') DEFAULT 'biasa',
    file_path VARCHAR(255),
    disposisi_ke VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Save
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $no_surat = cleanInput($_POST['no_surat']);
    $tgl_surat = cleanInput($_POST['tgl_surat']);
    $tgl_terima = cleanInput($_POST['tgl_terima']);
    $pengirim = cleanInput($_POST['pengirim']);
    $perihal = cleanInput($_POST['perihal']);
    $sifat = cleanInput($_POST['sifat']);
    $disposisi = cleanInput($_POST['disposisi']);
    
    // File handling
    $uploadDir = "uploads/surat_masuk/";
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    
    $newName = null;
    if ($_FILES['file_surat']['name']) {
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $newName = uniqid('SM_') . '.' . $ext;
        move_uploaded_file($_FILES['file_surat']['tmp_name'], $uploadDir . $newName);
    }
    
    $res = safe_query("INSERT INTO surat_masuk (no_surat, tgl_surat, tgl_terima, pengirim, perihal, sifat, file_path, disposisi_ke) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                       [$no_surat, $tgl_surat, $tgl_terima, $pengirim, $perihal, $sifat, $newName, $disposisi]);
    
    if($res) {
        write_log("LETTER_IN_CREATE", "Input surat masuk: $no_surat dari $pengirim");
        $_SESSION['flash_success'] = "Surat masuk berhasil diarsipkan.";
    }
    header("Location: surat_masuk.php");
    exit;
}

$data_surat = safe_query("SELECT * FROM surat_masuk ORDER BY tgl_terima DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Masuk - BexMedia</title>
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
            background: none; border: none; color: var(--text-muted);
            padding: 12px 24px; cursor: pointer; font-weight: 600;
            border-bottom: 2px solid transparent; transition: 0.3s;
        }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .sifat-tag { padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .tag-biasa { background: rgba(255,255,255,0.1); color: #fff; }
        .tag-penting { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .tag-rahasia { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Incoming Letters Archive</h1>
                    <p>Pencatatan dan digitalisasi surat masuk untuk pengelolaan disposisi yang efisien.</p>
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
                                    <th>Asal / Pengirim</th>
                                    <th>Tanggal</th>
                                    <th>Sifat</th>
                                    <th>Disposisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($data_surat)): ?>
                                    <tr>
                                        <td><strong><?= h($d['no_surat']) ?></strong><br><small style="opacity:0.5"><?= h($d['perihal']) ?></small></td>
                                        <td><?= h($d['pengirim']) ?></td>
                                        <td>
                                            <small style="display:block; opacity:0.5">Surat: <?= date('d/m/Y', strtotime($d['tgl_surat'])) ?></small>
                                            <small style="display:block">Terima: <?= date('d/m/Y', strtotime($d['tgl_terima'])) ?></small>
                                        </td>
                                        <td><span class="sifat-tag tag-<?= $d['sifat'] ?>"><?= $d['sifat'] ?></span></td>
                                        <td><?= h($d['disposisi_ke'] ?: '-') ?></td>
                                        <td>
                                            <?php if($d['file_path']): ?>
                                                <a href="uploads/surat_masuk/<?= $d['file_path'] ?>" target="_blank" class="btn btn-outline btn-sm">PDF</a>
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
                                    <input type="text" name="no_surat" placeholder="Nomor surat resmi" required>
                                </div>
                                <div class="input-group">
                                    <label>Sifat Surat</label>
                                    <select name="sifat">
                                        <option value="biasa">Biasa</option>
                                        <option value="penting">Penting</option>
                                        <option value="rahasia">Rahasia</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>Tanggal Surat</label>
                                    <input type="date" name="tgl_surat" required>
                                </div>
                                <div class="input-group">
                                    <label>Tanggal Terima</label>
                                    <input type="date" name="tgl_terima" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="input-group">
                                    <label>Pengirim / Instansi Asal</label>
                                    <input type="text" name="pengirim" placeholder="Contoh: PT. Sumber Makmur atau Dinas Kesehatan" required>
                                </div>
                                <div class="input-group">
                                    <label>Disposisi Ke</label>
                                    <input type="text" name="disposisi" placeholder="Penerima instruksi">
                                </div>
                            </div>
                            
                            <div class="input-group" style="margin-bottom: 24px;">
                                <label>Perihal / Ringkasan Isi</label>
                                <textarea name="perihal" rows="3" placeholder="Tuliskan poin utama surat..." required></textarea>
                            </div>

                            <div class="input-group" style="margin-bottom: 32px;">
                                <label>Upload Scan Surat (PDF)</label>
                                <input type="file" name="file_surat" accept=".pdf">
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary" style="padding: 16px 48px;">
                                <i data-lucide="save"></i> Registrasi Surat Masuk
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
