<?php
// MenuName: Tambah Karyawan
include 'security.php';
date_default_timezone_set('Asia/Jakarta');

// user_id and common data handled by security.php
$user_id = $user_id_logged;
if (!$user_id) { header("Location: ../login.php"); exit; }

// Auto-create tabel pendukung
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS informasi_pribadi (
  id int(11) NOT NULL AUTO_INCREMENT, user_id int(11) NOT NULL,
  jenis_kelamin enum('L','P') DEFAULT NULL, tempat_lahir varchar(100) DEFAULT NULL,
  tanggal_lahir date DEFAULT NULL, alamat text DEFAULT NULL,
  kota varchar(100) DEFAULT NULL, no_ktp varchar(20) DEFAULT NULL,
  no_hp varchar(20) DEFAULT NULL, agama varchar(30) DEFAULT NULL,
  status_pernikahan varchar(30) DEFAULT NULL,
  PRIMARY KEY (id), UNIQUE KEY uid (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ambil data pegawai dari Khanza (hanya 3 kolom yang diminta)
$khanzaList = [];
$khanzaError = '';
if ($conn_sik) {
    $res = mysqli_query($conn_sik, "SELECT id, nik, nama, jbtn FROM pegawai WHERE stts_aktif='AKTIF' ORDER BY nama ASC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $khanzaList[] = $row;
    } else {
        $khanzaError = 'Query Khanza gagal: ' . mysqli_error($conn_sik);
    }
} else {
    $khanzaError = 'Koneksi ke SIMRS Khanza tidak tersedia. Data diisi manual.';
}

// ============ SIMPAN KARYAWAN ============
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    csrf_verify();

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $jabatan      = trim($_POST['jabatan']      ?? '');
    $unit_kerja   = trim($_POST['unit_kerja']   ?? '');
    $email        = trim($_POST['email']        ?? '');
    $nik          = trim($_POST['nik']          ?? '');
    $khanza_id    = (int)($_POST['khanza_id']   ?? 0);
    $status       = $_POST['status'] ?? 'active';

    // Validasi dasar
    if (!$nama_lengkap) $errors[] = 'Nama lengkap wajib diisi.';
    if (!$jabatan)      $errors[] = 'Jabatan wajib diisi.';

    if (empty($errors)) {
        // Generate username dari nik atau khanza_id
        $uname_raw = $nik ?: ($khanza_id ? 'kz' . $khanza_id : 'kry' . time());
        $uname_enc = mysqli_real_escape_string($conn, $uname_raw);

        // Cek duplicate khanza_id atau nik
        if ($khanza_id) {
            $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE khanza_id=$khanza_id LIMIT 1"));
            if ($dup) $errors[] = 'Pegawai Khanza ini sudah pernah ditambahkan (ID #'.$dup['id'].').';
        }

        if (empty($errors)) {
            $defPass = password_hash('BexMedia@2026', PASSWORD_BCRYPT);
            $nikEsc  = mysqli_real_escape_string($conn, $nik);
            $namaEsc = mysqli_real_escape_string($conn, $nama_lengkap);
            $jabtEsc = mysqli_real_escape_string($conn, $jabatan);
            $unitEsc = mysqli_real_escape_string($conn, $unit_kerja);
            $emEsc   = mysqli_real_escape_string($conn, $email);
            $stEsc   = mysqli_real_escape_string($conn, $status);

            // Insert user — username pakai AES_ENCRYPT
            $ins = mysqli_query($conn,
                "INSERT INTO users (username, password, nik, nama_lengkap, jabatan, unit_kerja, email, status, khanza_id, khanza_sync)
                 VALUES (AES_ENCRYPT('$uname_enc','bex'), '$defPass', '$nikEsc', '$namaEsc', '$jabtEsc', '$unitEsc', '$emEsc', '$stEsc',
                         " . ($khanza_id ?: 'NULL') . ", NOW())"
            );

            if ($ins) {
                $new_id = mysqli_insert_id($conn);

                // Info pribadi jika ada
                $jk   = in_array($_POST['jenis_kelamin']??'', ['L','P']) ? $_POST['jenis_kelamin'] : '';
                $tLhr = mysqli_real_escape_string($conn, $_POST['tempat_lahir'] ?? '');
                $dLhr = !empty($_POST['tanggal_lahir']) ? "'".$_POST['tanggal_lahir']."'" : 'NULL';
                $alm  = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
                $kot  = mysqli_real_escape_string($conn, $_POST['kota'] ?? '');
                $noHP = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');

                mysqli_query($conn,
                    "INSERT INTO informasi_pribadi (user_id, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, kota, no_hp)
                     VALUES ($new_id, " . ($jk?"'$jk'":'NULL') . ", '$tLhr', $dLhr, '$alm', '$kot', '$noHP')
                     ON DUPLICATE KEY UPDATE
                     jenis_kelamin=VALUES(jenis_kelamin), tempat_lahir=VALUES(tempat_lahir),
                     tanggal_lahir=VALUES(tanggal_lahir), alamat=VALUES(alamat), kota=VALUES(kota), no_hp=VALUES(no_hp)"
                );

                write_log("TAMBAH_KARYAWAN", "User $nama_user tambah karyawan: $nama_lengkap (khanza_id=$khanza_id)");
                $_SESSION['flash_message'] = "Karyawan \"$nama_lengkap\" berhasil ditambahkan!";
                header("Location: data_karyawan.php"); exit;
            } else {
                $errors[] = 'Gagal menyimpan: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan | BexMedia</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#3B82F6; }
        .tk-wrap { padding:20px 32px; overflow-y:auto; flex:1; }
        .tk-card {
            background:white; border-radius:20px;
            box-shadow:0 4px 24px rgba(0,0,0,0.05);
            padding:28px 32px; margin-bottom:20px;
        }
        .tk-card-title { font-family:'Outfit',sans-serif; font-weight:800; font-size:1rem; color:#1E293B; margin-bottom:18px; display:flex; align-items:center; gap:10px; }

        /* Khanza Search */
        .khanza-search-wrap { position:relative; margin-bottom:16px; }
        .khanza-search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94A3B8; pointer-events:none; }
        .khanza-search-input {
            width:100%; padding:11px 16px 11px 42px;
            border:1.5px solid #E2E8F0; border-radius:12px;
            font-size:0.9rem; background:#F8FAFC; outline:none;
            font-family:'Outfit',sans-serif; transition:all 0.2s; color:#1E293B;
        }
        .khanza-search-input:focus { border-color:var(--primary); background:white; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .khanza-list { max-height:280px; overflow-y:auto; border:1.5px solid #E2E8F0; border-radius:12px; background:white; }
        .khanza-item {
            padding:10px 16px; cursor:pointer; display:flex;
            align-items:center; justify-content:space-between;
            border-bottom:1px solid #F1F5F9; transition:all 0.15s;
            font-size:0.88rem;
        }
        .khanza-item:last-child { border-bottom:none; }
        .khanza-item:hover { background:#EFF6FF; }
        .khanza-item.selected { background:#EFF6FF; border-left:3px solid var(--primary); }
        .khanza-badge { background:#EFF6FF; color:#1D4ED8; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:700; }

        /* Form */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
        .form-row.single { grid-template-columns:1fr; }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-label { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; }
        .form-input, .form-select {
            padding:10px 14px; border:1.5px solid #E2E8F0; border-radius:12px;
            font-size:0.88rem; background:#F8FAFC; outline:none;
            font-family:'Outfit',sans-serif; color:#1E293B; transition:all 0.2s;
        }
        .form-input:focus, .form-select:focus { border-color:var(--primary); background:white; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .form-input.autofilled { background:#F0F9FF; border-color:#BAE6FD; }
        .form-input:read-only { opacity:0.6; cursor:not-allowed; }

        /* Divider */
        .section-divider { display:flex; align-items:center; gap:12px; margin:20px 0 18px; }
        .section-divider span { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#94A3B8; white-space:nowrap; }
        .section-divider::before, .section-divider::after { content:''; flex:1; height:1px; background:#E2E8F0; }

        /* Buttons */
        .btn-prim {
            background:var(--primary); color:white; border:none;
            padding:12px 28px; border-radius:12px; font-weight:700;
            font-size:0.9rem; cursor:pointer; display:inline-flex;
            align-items:center; gap:8px; font-family:'Outfit',sans-serif;
            transition:all 0.2s; text-decoration:none;
        }
        .btn-prim:hover { background:#2563EB; transform:translateY(-1px); }
        .btn-ghost {
            background:#F1F5F9; color:#475569; border:none;
            padding:12px 22px; border-radius:12px; font-weight:600;
            font-size:0.9rem; cursor:pointer; display:inline-flex;
            align-items:center; gap:8px; text-decoration:none;
            font-family:'Outfit',sans-serif; transition:all 0.2s;
        }
        .btn-ghost:hover { background:#E2E8F0; }

        /* Alert */
        .alert { padding:12px 16px; border-radius:12px; margin-bottom:16px; font-size:0.85rem; display:flex; align-items:flex-start; gap:10px; }
        .alert-warning { background:#FEF3C7; color:#92400E; border-left:4px solid #F59E0B; }
        .alert-info    { background:#F0F9FF; color:#0369A1; border-left:4px solid #3B82F6; }
        .alert-error   { background:#FEE2E2; color:#991B1B; border-left:4px solid #EF4444; }

        /* Selected pill */
        .selected-pill {
            background:#EFF6FF; border:1.5px solid #BFDBFE;
            border-radius:12px; padding:10px 14px;
            display:flex; align-items:center; gap:12px;
            margin-bottom:16px; font-size:0.88rem; color:#1E293B;
        }
    </style>
</head>
<body>
<div style="display:flex;height:100vh;overflow:hidden;">
    <?php include 'sidebar.php'; ?>
    <main style="flex:1;display:flex;flex-direction:column;min-width:0;overflow:hidden;">
        <?php 
        $breadcrumb = "Employee Hub / Database Karyawan / <strong>Tambah Karyawan</strong>";
        include "topbar.php"; 
        ?>


        <div class="tk-wrap" style="background:rgba(255,255,255,0.3);backdrop-filter:blur(20px);">

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div>
                    <h1 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.55rem;color:#1E293B;margin:0 0 2px;">Tambah Karyawan</h1>
                    <p style="color:#64748B;font-size:0.83rem;margin:0;">Pilih dari SIMRS Khanza atau isi data manual</p>
                </div>
                <a href="data_karyawan.php" class="btn-ghost"><i data-lucide="arrow-left" size="15"></i> Kembali</a>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle" size="16" style="flex-shrink:0;margin-top:1px;"></i>
                <div><?= implode('<br>', array_map('h', $errors)) ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" id="formTambah">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="simpan" value="1">
                <input type="hidden" name="khanza_id" id="hKhanzaId" value="">

                <!-- CARD: Pilih dari Khanza -->
                <div class="tk-card">
                    <div class="tk-card-title">
                        <i data-lucide="database" size="18" style="color:var(--primary)"></i>
                        Pilih Pegawai dari SIMRS Khanza
                    </div>

                    <?php if ($khanzaError): ?>
                    <div class="alert alert-warning">
                        <i data-lucide="wifi-off" size="16" style="flex-shrink:0;margin-top:1px;"></i>
                        <div><?= h($khanzaError) ?> Isi data secara manual di bawah.</div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i data-lucide="info" size="16" style="flex-shrink:0;margin-top:1px;"></i>
                        <div>Pilih pegawai dari daftar Khanza → data <strong>Nama</strong> &amp; <strong>Jabatan</strong> akan otomatis terisi. Klik dua kali untuk hapus pilihan.</div>
                    </div>

                    <!-- Selected pill -->
                    <div id="selectedPill" style="display:none;" class="selected-pill">
                        <i data-lucide="check-circle-2" size="18" style="color:#3B82F6;flex-shrink:0;"></i>
                        <div>
                            <div style="font-weight:700;" id="pillNama"></div>
                            <div style="font-size:0.78rem;color:#64748B;" id="pillJbtn"></div>
                        </div>
                        <button type="button" onclick="clearKhanza()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#94A3B8;">
                            <i data-lucide="x" size="16"></i>
                        </button>
                    </div>

                    <div id="khanzaPickerWrap">
                        <!-- Search -->
                        <div class="khanza-search-wrap">
                            <i data-lucide="search" size="15" class="khanza-search-icon"></i>
                            <input type="text" id="khanzaSearch" class="khanza-search-input"
                                   placeholder="Cari nama atau jabatan..." oninput="filterKhanza(this.value)">
                        </div>

                        <!-- List -->
                        <div class="khanza-list" id="khanzaList">
                            <?php foreach ($khanzaList as $k): ?>
                            <div class="khanza-item"
                                 data-id="<?= $k['id'] ?>"
                                 data-nama="<?= h($k['nama']) ?>"
                                 data-jbtn="<?= h($k['jbtn']) ?>"
                                 data-nik="<?= h($k['nik'] ?? '') ?>"
                                 onclick="selectKhanza(this)">
                                <div>
                                    <div style="font-weight:600;"><?= h($k['nama']) ?></div>
                                    <div style="font-size:0.78rem;color:#64748B;"><?= h($k['jbtn']) ?></div>
                                </div>
                                <span class="khanza-badge">ID #<?= $k['id'] ?></span>
                            </div>
                            <?php endforeach; ?>

                            <?php if (empty($khanzaList)): ?>
                            <div style="padding:24px;text-align:center;color:#94A3B8;font-size:0.85rem;">
                                Tidak ada data pegawai aktif dari Khanza.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:8px;font-size:0.75rem;color:#94A3B8;text-align:right;">
                            <?= count($khanzaList) ?> pegawai aktif dari SIMRS Khanza
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CARD: Data Karyawan -->
                <div class="tk-card">
                    <div class="tk-card-title">
                        <i data-lucide="user" size="18" style="color:var(--primary)"></i>
                        Data Karyawan
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span style="color:#EF4444">*</span></label>
                            <input type="text" name="nama_lengkap" id="fNama" class="form-input"
                                   value="<?= h($_POST['nama_lengkap'] ?? '') ?>" placeholder="Nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" id="fNik" class="form-input"
                                   value="<?= h($_POST['nik'] ?? '') ?>" placeholder="Nomor Induk Karyawan">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jabatan <span style="color:#EF4444">*</span></label>
                            <input type="text" name="jabatan" id="fJabatan" class="form-input"
                                   value="<?= h($_POST['jabatan'] ?? '') ?>" placeholder="Jabatan/posisi" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit Kerja</label>
                            <input type="text" name="unit_kerja" id="fUnit" class="form-input"
                                   value="<?= h($_POST['unit_kerja'] ?? '') ?>" placeholder="Departemen/unit">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input"
                                   value="<?= h($_POST['email'] ?? '') ?>" placeholder="email@domain.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" class="form-input"
                                   value="<?= h($_POST['no_hp'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($_POST['status']??'active')==='active'?'selected':'' ?>>Aktif</option>
                                <option value="pending" <?= ($_POST['status']??'')==='pending'?'selected':'' ?>>Pending</option>
                                <option value="blocked" <?= ($_POST['status']??'')==='blocked'?'selected':'' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="fJK" class="form-select">
                                <option value="">— Pilih —</option>
                                <option value="L" <?= ($_POST['jenis_kelamin']??'')==='L'?'selected':'' ?>>Laki-laki</option>
                                <option value="P" <?= ($_POST['jenis_kelamin']??'')==='P'?'selected':'' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-divider"><span>Data Pribadi Tambahan (Opsional)</span></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-input" value="<?= h($_POST['tempat_lahir']??'') ?>" placeholder="Kota lahir">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-input" value="<?= h($_POST['tanggal_lahir']??'') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kota</label>
                            <input type="text" name="kota" class="form-input" value="<?= h($_POST['kota']??'') ?>" placeholder="Kota domisili">
                        </div>
                    </div>
                    <div class="form-row single">
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <input type="text" name="alamat" class="form-input" value="<?= h($_POST['alamat']??'') ?>" placeholder="Alamat lengkap">
                        </div>
                    </div>
                </div>

                <!-- Action -->
                <div style="display:flex;gap:12px;justify-content:flex-end;padding-bottom:24px;">
                    <a href="data_karyawan.php" class="btn-ghost"><i data-lucide="x" size="15"></i> Batal</a>
                    <button type="submit" class="btn-prim"><i data-lucide="save" size="15"></i> Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
lucide.createIcons();

var selectedKhanzaId = null;

function filterKhanza(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.khanza-item').forEach(el => {
        var nama = el.dataset.nama.toLowerCase();
        var jbtn = el.dataset.jbtn.toLowerCase();
        el.style.display = (nama.includes(q) || jbtn.includes(q)) ? '' : 'none';
    });
}

function selectKhanza(el) {
    // Deselect if same
    if (el.classList.contains('selected')) {
        clearKhanza(); return;
    }
    document.querySelectorAll('.khanza-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');

    var id   = el.dataset.id;
    var nama = el.dataset.nama;
    var jbtn = el.dataset.jbtn;
    var nik  = el.dataset.nik;

    // Fill form
    document.getElementById('fNama').value    = nama;
    document.getElementById('fJabatan').value = jbtn;
    document.getElementById('fNik').value     = nik;
    document.getElementById('hKhanzaId').value = id;
    selectedKhanzaId = id;

    // Mark as autofilled
    ['fNama','fJabatan','fNik'].forEach(f => {
        var el2 = document.getElementById(f);
        if (el2) el2.classList.add('autofilled');
    });

    // Show pill, hide list
    document.getElementById('pillNama').textContent = nama;
    document.getElementById('pillJbtn').textContent = jbtn + ' · ID #' + id;
    document.getElementById('selectedPill').style.display = 'flex';
    document.getElementById('khanzaPickerWrap').style.display = 'none';

    lucide.createIcons();
}

function clearKhanza() {
    document.querySelectorAll('.khanza-item').forEach(e => e.classList.remove('selected'));
    document.getElementById('fNama').value     = '';
    document.getElementById('fJabatan').value  = '';
    document.getElementById('fNik').value      = '';
    document.getElementById('hKhanzaId').value = '';
    ['fNama','fJabatan','fNik'].forEach(f => {
        var el2 = document.getElementById(f);
        if (el2) el2.classList.remove('autofilled');
    });
    document.getElementById('selectedPill').style.display = 'none';
    document.getElementById('khanzaPickerWrap').style.display = '';
    selectedKhanzaId = null;
}
</script>
</body>
</html>
