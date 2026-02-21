<?php
require_once "../conf/config.php";
checkLogin();

// MenuName: Exit Clearance System

$user_id = $_SESSION['user_id'] ?? 0;

// Handle Form Submission
if (isset($_POST['simpan'])) {
    csrf_verify();
    
    $id_karyawan = intval($_POST['id_karyawan']);
    $tgl_resign  = cleanInput($_POST['tgl_resign']);
    
    // Get user details
    $uData = mysqli_fetch_assoc(safe_query("SELECT nik, nama_lengkap, jabatan, unit_kerja FROM users WHERE id = ?", [$id_karyawan]));
    
    $nik = $uData['nik'];
    $nama = $uData['nama_lengkap'];
    $jabatan = $uData['jabatan'];
    $unit_kerja = $uData['unit_kerja'];
    
    $aset = json_encode($_POST['aset']);
    $serah_terima = json_encode([
        'checklist' => $_POST['checklist'],
        'dokumen'   => $_POST['dokumen'],
        'penerima'  => $_POST['penerima'],
        'tgl_serah' => $_POST['tgl_serah'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Create table if not exists (Lazy Load)
    safe_query("CREATE TABLE IF NOT EXISTS exit_clearance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        nik VARCHAR(50),
        nama VARCHAR(255),
        jabatan VARCHAR(255),
        unit_kerja VARCHAR(255),
        tgl_resign DATE,
        aset TEXT,
        serah_terima TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $res = safe_query("INSERT INTO exit_clearance (user_id, nik, nama, jabatan, unit_kerja, tgl_resign, aset, serah_terima) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
           [$id_karyawan, $nik, $nama, $jabatan, $unit_kerja, $tgl_resign, $aset, $serah_terima]);

    if ($res) {
        write_log("EXIT_CLEARANCE_CREATE", "Membuat form exit clearance untuk $nama");
        $_SESSION['flash_success'] = "Data Exit Clearance berhasil disimpan.";
    } else {
        $_SESSION['flash_error'] = "Gagal menyimpan data.";
    }
    header("Location: exit_clearance.php");
    exit;
}

// Get employees for dropdown
$karyawanData = [];
$res_emp = safe_query("SELECT id, nik, nama_lengkap as nama, jabatan, unit_kerja FROM users WHERE status='active' ORDER BY nama_lengkap ASC");
while ($row = mysqli_fetch_assoc($res_emp)) {
    $karyawanData[] = $row;
}

// Get existing clearance data
$dataExit = safe_query("SELECT * FROM exit_clearance ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Clearance System - BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .clearance-grid {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .premium-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .aset-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .aset-table th {
            text-align: left;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            font-size: 13px;
            color: var(--text-muted);
        }

        .aset-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .aset-table input, .aset-table select {
            width: 100%;
            padding: 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 6px;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .badge-done { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Exit Clearance</h1>
                    <p>Sistem pengembalian inventaris dan serah terima pekerjaan karyawan resign.</p>
                </div>
                <div class="header-right">
                    <div class="tabs">
                        <button class="tab-btn active" onclick="showTab('input')">Form Input</button>
                        <button class="tab-btn" onclick="showTab('data')">Arsip Data</button>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['flash_success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                    <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <div class="clearance-grid">
                <!-- FORM INPUT -->
                <div id="tab-input" class="tab-content active">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        
                        <div class="premium-card">
                            <div class="section-title">
                                <i data-lucide="user"></i>
                                <h2>Identitas Karyawan</h2>
                            </div>
                            
                            <div class="form-grid">
                                <div class="input-group">
                                    <label>Pilih Karyawan</label>
                                    <select name="id_karyawan" id="id_karyawan" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        <?php foreach ($karyawanData as $row) { ?>
                                            <option value="<?= $row['id']; ?>"
                                                data-nik="<?= $row['nik']; ?>"
                                                data-nama="<?= $row['nama']; ?>"
                                                data-jabatan="<?= $row['jabatan']; ?>"
                                                data-unit="<?= $row['unit_kerja']; ?>">
                                                <?= $row['nik']." - ".$row['nama']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>NIK</label>
                                    <input type="text" id="nik" readonly class="readonly">
                                </div>
                                <div class="input-group">
                                    <label>Jabatan</label>
                                    <input type="text" id="jabatan" readonly class="readonly">
                                </div>
                                <div class="input-group">
                                    <label>Departemen</label>
                                    <input type="text" id="unit_kerja" readonly class="readonly">
                                </div>
                                <div class="input-group">
                                    <label>Tanggal Resign</label>
                                    <input type="date" name="tgl_resign" required>
                                </div>
                            </div>

                            <div class="section-title">
                                <i data-lucide="package"></i>
                                <h2>Pengembalian Aset</h2>
                            </div>
                            
                            <table class="aset-table">
                                <thead>
                                    <tr>
                                        <th>Jenis Aset</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                        <th>Penerima</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $aset_list = [
                                        ["Laptop / PC", "Merk, Serial Number"],
                                        ["ID Card / Access", "Nomor ID"],
                                        ["Seragam", "Ukuran, jumlah"],
                                        ["HP / SIM Card", "Provider, nomor"],
                                        ["Kendaraan", "Plat nomor"],
                                    ];
                                    foreach ($aset_list as $i => $a) { ?>
                                        <tr>
                                            <td><?= $a[0] ?></td>
                                            <td><input type="text" name="aset[<?= $i ?>][keterangan]" placeholder="<?= $a[1] ?>"></td>
                                            <td>
                                                <select name="aset[<?= $i ?>][status]">
                                                    <option value="Belum">Belum</option>
                                                    <option value="Sudah">Sudah</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="aset[<?= $i ?>][penerima]">
                                                    <option value="">-- Penerima --</option>
                                                    <?php foreach ($karyawanData as $k) { ?>
                                                        <option value="<?= $k['nama'] ?>"><?= $k['nama'] ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <input type="hidden" name="aset[<?= $i ?>][jenis]" value="<?= $a[0] ?>">
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>

                            <div class="section-title" style="margin-top: 32px;">
                                <i data-lucide="clipboard-list"></i>
                                <h2>Handover Pekerjaan</h2>
                            </div>
                            
                            <div style="display: grid; gap: 20px;">
                                <div class="input-group">
                                    <label>Checklist Tugas Selesai</label>
                                    <textarea name="checklist" placeholder="Sebutkan tugas/proyek yang sudah difinalisasi..."></textarea>
                                </div>
                                <div class="input-group">
                                    <label>Dokumen / File Diserahkan</label>
                                    <textarea name="dokumen" placeholder="Daftar file atau folder fisik yang dipindah-tangankan..."></textarea>
                                </div>
                                <div class="form-grid">
                                    <div class="input-group">
                                        <label>Penerima Tugas</label>
                                        <select name="penerima" required>
                                            <option value="">-- Pilih Penerima --</option>
                                            <?php foreach ($karyawanData as $k) { ?>
                                                <option value="<?= $k['nama']; ?>"><?= $k['nama']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Tanggal Handover</label>
                                        <input type="date" name="tgl_serah" required>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 32px;">
                                <button type="submit" name="simpan" class="btn btn-primary" style="width: 200px;">
                                    <i data-lucide="check-circle"></i> Simpan Clearance
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- DATA TAB -->
                <div id="tab-data" class="tab-content">
                    <div class="premium-card">
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Resign</th>
                                        <th>Progress Aset</th>
                                        <th>Penerima</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($dataExit && $dataExit->num_rows > 0): ?>
                                        <?php while ($d = $dataExit->fetch_assoc()): 
                                            $aset = json_decode($d['aset'], true);
                                            $sudah = 0;
                                            if ($aset) {
                                                foreach($aset as $a) if($a['status'] == 'Sudah') $sudah++;
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $d['nama'] ?></strong><br>
                                                    <small style="opacity:0.6;"><?= $d['nik'] ?></small>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($d['tgl_resign'])) ?></td>
                                                <td><?= $sudah ?> / 5 Item</td>
                                                <td><?= json_decode($d['serah_terima'], true)['penerima'] ?></td>
                                                <td>
                                                    <span class="status-tag <?= $sudah == 5 ? 'tag-success' : 'tag-warning' ?>">
                                                        <?= $sudah == 5 ? 'Complete' : 'Pending' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-outline btn-sm">Preview</button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" style="text-align:center; opacity:0.5; padding:40px;">Belum ada arsip clearance.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

        $('#id_karyawan').on('change', function() {
            var opt = $(this).find(':selected');
            $('#nik').val(opt.data('nik'));
            $('#jabatan').val(opt.data('jabatan'));
            $('#unit_kerja').val(opt.data('unit'));
        });
    </script>
</body>
</html>
