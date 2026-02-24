<?php
// MenuName: Jadwal Dinas
include 'security.php';
date_default_timezone_set('Asia/Jakarta');

// Resolve user identifiers from security.php
$user_id = $user_id_logged;

if (!$user_id) {
    header("Location: ../login.php"); exit;
}

// Auto-create tables if not exist (safety net)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `jam_kerja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama_jam` varchar(50) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `warna` varchar(10) DEFAULT '#3B82F6',
  PRIMARY KEY (`id`), UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "INSERT IGNORE INTO `jam_kerja` (`kode`,`nama_jam`,`jam_mulai`,`jam_selesai`,`warna`) VALUES
  ('PAGI','Pagi','09:00:00','14:00:00','#3B82F6'),
  ('SIANG','Siang','14:00:00','21:00:00','#10B981'),
  ('MALAM','Malam','21:00:00','09:00:00','#8B5CF6'),
  ('LEPAS','Lepas Malam','00:00:00','00:00:00','#64748B'),
  ('LIBUR','Libur','00:00:00','00:00:00','#94A3B8')");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `jadwal_dinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `bulan` tinyint(2) NOT NULL,
  `tahun` smallint(4) NOT NULL,
  `jam_kerja_id` int(11) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_jadwal` (`user_id`,`tanggal`),
  KEY `user_id` (`user_id`), KEY `jam_kerja_id` (`jam_kerja_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$bulanIndo = [
    1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",5=>"Mei",6=>"Juni",
    7=>"Juli",8=>"Agustus",9=>"September",10=>"Oktober",11=>"November",12=>"Desember"
];
$hariIndo = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

// Ambil unit kerja user login
$unitRow   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT unit_kerja FROM users WHERE id='$user_id'"));
$unitLogin = $unitRow['unit_kerja'] ?? '';

// Ambil daftar karyawan (semua jika admin, filter unit_kerja jika bukan)
$username = $_SESSION['username'] ?? '';
$isAdmin  = ($username === 'admin' || $username === 'bara');

// Ambil filter unit dari GET
$view_unit = $_GET['unit'] ?? ($isAdmin ? '' : $unitLogin);

if ($view_unit !== '') {
    $uk = mysqli_real_escape_string($conn, $view_unit);
    $userResult = mysqli_query($conn, "SELECT id, nama_lengkap AS nama, unit_kerja FROM users WHERE unit_kerja='$uk' ORDER BY nama_lengkap");
} else if ($isAdmin) {
    $userResult = mysqli_query($conn, "SELECT id, nama_lengkap AS nama, unit_kerja FROM users ORDER BY nama_lengkap");
} else {
    $uk = mysqli_real_escape_string($conn, $unitLogin);
    $userResult = mysqli_query($conn, "SELECT id, nama_lengkap AS nama, unit_kerja FROM users WHERE unit_kerja='$uk' ORDER BY nama_lengkap");
}
$userList = [];
while ($u = mysqli_fetch_assoc($userResult)) $userList[] = $u;

// Ambil daftar unit untuk filter generator (Modal)
$unitOptionsResult = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
$unitOptions = [];
while ($uo = mysqli_fetch_assoc($unitOptionsResult)) $unitOptions[] = $uo['nama_unit'];

// Ambil daftar jam kerja
$jamResult = mysqli_query($conn, "SELECT * FROM jam_kerja ORDER BY jam_mulai");
$jamList     = [];
$jamNamaOnly = [];
$shiftColors = [];
while ($j = mysqli_fetch_assoc($jamResult)) {
    $jamList[$j['id']]     = $j;
    $jamNamaOnly[$j['id']] = $j['nama_jam'];
    $shiftColors[$j['id']] = $j['warna'] ?? '#64748B';
}

// ============ SIMPAN JADWAL ============
if (isset($_POST['simpan'])) {
    csrf_verify();
    $uid_post  = (int)$_POST['user_id'];
    $bulanPost = (int)$_POST['bulan'];
    $tahunPost = (int)$_POST['tahun'];
    $hari_kerja = $_POST['jam'] ?? [];

    $inserted = 0;
    foreach ($hari_kerja as $tanggal => $jam_id) {
        if ($jam_id != '') {
            $tgl = sprintf("%04d-%02d-%02d", $tahunPost, $bulanPost, (int)$tanggal);
            $jam_id_int = (int)$jam_id;
            $stmt = $conn->prepare("INSERT INTO jadwal_dinas (user_id, tanggal, bulan, tahun, jam_kerja_id, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE jam_kerja_id=VALUES(jam_kerja_id), created_by=VALUES(created_by), created_at=NOW()");
            $stmt->bind_param("isiiis", $uid_post, $tgl, $bulanPost, $tahunPost, $jam_id_int, $nama_user);
            $stmt->execute(); $stmt->close();
            $inserted++;
        }
    }
    // Hapus entry jika jam tidak dipilih (keluarkan dari jadwal)
    // (hanya hari yang dikirim via POST)
    write_log("JADWAL_SIMPAN", "User $nama_user input jadwal user_id=$uid_post bulan=$bulanPost/$tahunPost ($inserted entries)");
    $_SESSION['flash_message'] = "Jadwal berhasil disimpan! ($inserted hari)";
    header("Location: jadwal_dinas.php?bulan=$bulanPost&tahun=$tahunPost&emp=$uid_post");
    exit;
}

// ============ HAPUS SATU ENTRI ============
if (isset($_POST['hapus'])) {
    csrf_verify();
    $hapus_id = (int)$_POST['hapus_id'];
    mysqli_query($conn, "DELETE FROM jadwal_dinas WHERE id=$hapus_id");
    $_SESSION['flash_message'] = "Entri jadwal dihapus.";
    header("Location: jadwal_dinas.php?bulan={$_GET['bulan']}&tahun={$_GET['tahun']}");
    exit;
}

// ============ PARAMETER DISPLAY ============
$selected_bulan = max(1, min(12, (int)($_GET['bulan'] ?? date('n'))));
$selected_tahun = (int)($_GET['tahun'] ?? date('Y'));
$selected_emp   = (int)($_GET['emp']   ?? 0);
$daysInMonth    = (int)date('t', strtotime("$selected_tahun-$selected_bulan-01"));

// Ambil data jadwal tersimpan bulan ini
$filterUnitSql = "";
if ($view_unit !== '') {
    $uk_esc = mysqli_real_escape_string($conn, $view_unit);
    $filterUnitSql = "AND u.unit_kerja = '$uk_esc'";
}

$savedResult = mysqli_query($conn, "SELECT jd.id, jd.user_id, jd.tanggal, jd.jam_kerja_id,
    u.nama_lengkap AS nama_kary, jk.nama_jam, jk.warna
    FROM jadwal_dinas jd
    JOIN users u ON jd.user_id=u.id
    JOIN jam_kerja jk ON jd.jam_kerja_id=jk.id
    WHERE jd.bulan='$selected_bulan' AND jd.tahun='$selected_tahun' $filterUnitSql
    ORDER BY u.nama_lengkap, jd.tanggal");

$savedData = []; $savedIds = []; $savedNames = [];
while ($row = mysqli_fetch_assoc($savedResult)) {
    $tgl = (int)date('j', strtotime($row['tanggal']));
    $uid = $row['user_id'];
    $savedData[$uid][$tgl] = [
        'nama'  => $row['nama_jam'],
        'warna' => $row['warna'],
        'kid'   => $row['jam_kerja_id'],
    ];
    $savedIds[$uid][$tgl] = $row['id'];
    $savedNames[$uid] = $row['nama_kary'];
}

// ============ HAPUS JADWAL PER KARYAWAN ============
if (isset($_POST['hapus_karyawan'])) {
    csrf_verify();
    $uid = (int)$_POST['user_id'];
    $bln = (int)$_POST['bulan'];
    $thn = (int)$_POST['tahun'];
    mysqli_query($conn, "DELETE FROM jadwal_dinas WHERE user_id=$uid AND bulan=$bln AND tahun=$thn");
    write_log("JADWAL_HAPUS_KARYAWAN", "User $nama_user menghapus jadwal user_id=$uid bulan=$bln/$thn");
    $_SESSION['flash_message'] = "Jadwal karyawan berhasil dikosongkan.";
    header("Location: jadwal_dinas.php?bulan=$bln&tahun=$thn");
    exit;
}

// ============ HAPUS SEMUA JADWAL BULAN INI ============
if (isset($_POST['hapus_semua'])) {
    csrf_verify();
    $bln = (int)$_POST['bulan'];
    $thn = (int)$_POST['tahun'];
    
    if ($isAdmin) {
        mysqli_query($conn, "DELETE FROM jadwal_dinas WHERE bulan=$bln AND tahun=$thn");
    } else {
        $uk = mysqli_real_escape_string($conn, $unitLogin);
        mysqli_query($conn, "DELETE FROM jadwal_dinas WHERE bulan=$bln AND tahun=$thn AND user_id IN (SELECT id FROM users WHERE unit_kerja='$uk')");
    }
    
    write_log("JADWAL_HAPUS_SEMUA", "User $nama_user menghapus SEMUA jadwal bulan=$bln/$thn");
    $_SESSION['flash_message'] = "Semua jadwal bulan ini berhasil dikosongkan.";
    header("Location: jadwal_dinas.php?bulan=$bln&tahun=$thn");
    exit;
}

// Hitung rekap shift per orang & global untuk bulan ini
$rekapShift = [];
$globalRekapShift = [];
foreach ($savedData as $uid => $tglData) {
    foreach ($tglData as $tgl => $entry) {
        $kid = $entry['kid'];
        $rekapShift[$uid][$kid] = ($rekapShift[$uid][$kid] ?? 0) + 1;
        $globalRekapShift[$kid] = ($globalRekapShift[$kid] ?? 0) + 1;
    }
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Navigasi bulan
$pm = $selected_bulan-1; $py = $selected_tahun;
if ($pm < 1) { $pm = 12; $py--; }
$nm = $selected_bulan+1; $ny = $selected_tahun;
if ($nm > 12) { $nm = 1; $ny++; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dinas | BexMedia</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3B82F6; --primary-hover: #2563EB; }

        /* ---- Layout ---- */
        .jd-wrap { padding: 20px 32px; overflow-y: auto; flex: 1; }

        /* ---- Cards ---- */
        .jd-card {
            background: white; border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.05);
            padding: 24px 28px; margin-bottom: 20px;
        }
        .jd-card-title {
            font-family: 'Outfit', sans-serif; font-weight: 700;
            font-size: 1rem; color: #1E293B;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 18px;
        }

        /* ---- Form controls ---- */
        .jd-row { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
        .jd-group { display: flex; flex-direction: column; gap: 5px; }
        .jd-label { font-size: 0.75rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.04em; }
        .jd-select, .jd-input {
            padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 12px;
            font-size: 0.88rem; background: #F8FAFC; outline: none;
            font-family: 'Outfit', sans-serif; color: #1E293B; transition: all 0.2s;
        }
        .jd-select:focus, .jd-input:focus {
            border-color: var(--primary); background: white;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* ---- Flash Toast ---- */
        .flash-toast {
            position: fixed; top: 24px; right: 24px; z-index: 10000;
            background: #10B981; color: white; padding: 14px 24px;
            border-radius: 14px; display: flex; align-items: center; gap: 12px;
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.2);
            font-weight: 700; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.2);
            animation: slideInDown 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideInDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ---- SweetAlert2 Styles (Premium) ---- */
        .swal2-popup.bex-swal {
            border-radius: 24px !important;
            padding: 2em !important;
            font-family: 'Outfit', sans-serif !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }
        .swal2-container { z-index: 10001 !important; }
        .swal2-title { color: #1E293B !important; font-weight: 800 !important; }
        .swal2-html-container { color: #64748B !important; font-size: 0.95rem !important; line-height: 1.6 !important; }
        .swal2-confirm { border-radius: 12px !important; font-weight: 700 !important; padding: 12px 32px !important; }
        .swal2-cancel { border-radius: 12px !important; font-weight: 700 !important; padding: 12px 32px !important; }
        .swal2-icon { border-width: 3px !important; }
        .shift-legend { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .legend-item { display: flex; align-items: center; gap: 7px; font-size: 0.82rem; color: #475569; }
        .legend-dot { width: 11px; height: 11px; border-radius: 4px; flex-shrink: 0; }
        .legend-count { font-weight: 700; font-size: 0.78rem; padding: 2px 8px; border-radius: 20px; }

        /* ---- Input table ---- */
        .tbl-scroll { overflow-x: auto; border-radius: 12px; }
        .tbl-input { border-collapse: collapse; min-width: 900px; font-size: 0.8rem; }
        .tbl-input th {
            background: #1E293B; color: #94A3B8;
            padding: 10px 6px; text-align: center; font-weight: 600;
            white-space: nowrap; min-width: 44px;
        }
        .tbl-input th.weekend-h { color: #F87171; }
        .tbl-input td { text-align: center; padding: 6px 3px; border-right: 1px solid #F1F5F9; }

        .cell-btn {
            width: 38px; height: 34px;
            border: 1.5px dashed #CBD5E1; border-radius: 8px;
            background: #F8FAFC; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            color: #CBD5E1; font-size: 0.72rem; font-weight: 700;
            transition: all 0.2s; white-space: nowrap;
        }
        .cell-btn:hover { border-color: var(--primary); color: var(--primary); background: #EFF6FF; }
        .cell-btn.filled { border-style: solid; color: white; border-color: transparent; overflow: hidden; }

        /* ---- Saved table ---- */
        .tbl-saved { border-collapse: collapse; min-width: 900px; font-size: 0.78rem; width: 100%; }
        .tbl-saved thead th {
            background: #F1F5F9; color: #64748B;
            padding: 10px 6px; text-align: center;
            font-weight: 700; font-size: 0.72rem;
            text-transform: uppercase; letter-spacing: 0.04em;
            border-bottom: 2px solid #E2E8F0; white-space: nowrap;
        }
        .tbl-saved thead th.th-name { text-align: left; padding-left: 14px; min-width: 160px; }
        .tbl-saved thead th.weekend-h { color: #EF4444; }
        .tbl-saved tbody td { padding: 8px 4px; border-bottom: 1px solid #F8FAFC; text-align: center; vertical-align: middle; }
        .tbl-saved tbody td.td-name { text-align: left; padding-left: 14px; font-weight: 600; color: #1E293B; white-space: nowrap; }
        .tbl-saved tbody tr:hover td { background: #F8FAFC; }
        .tbl-saved tbody td.weekend-cell { background: #FFF5F5; }
        .tbl-saved tfoot td { background: #F8FAFC; padding: 6px 4px; text-align: center; font-weight: 600; font-size: 0.72rem; }

        .cell-interactive { 
            cursor: pointer; 
            transition: all 0.2s; 
            position: relative;
        }
        .cell-interactive:hover { 
            background: #F0F9FF !important; 
            box-shadow: inset 0 0 0 1px var(--primary);
        }
        .cell-interactive:active { transform: scale(0.95); }
        .cell-loading { opacity: 0.4; pointer-events: none; }
        .cell-loading::after {
            content: ""; position: absolute; inset: 0;
            background: rgba(255,255,255,0.5);
            display: flex; align-items: center; justify-content: center;
        }

        .shift-pill {
            display: inline-block; padding: 3px 7px; border-radius: 6px;
            font-size: 0.68rem; font-weight: 800; color: white; white-space: nowrap;
        }
        .shift-empty { color: #E2E8F0; font-size: 0.9rem; }

        /* ---- Rekap row ---- */
        .rekap-name { font-size: 0.75rem; color: #64748B; }
        .rekap-chips { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 4px; }
        .rekap-chip { padding: 2px 8px; border-radius: 10px; font-size: 0.68rem; font-weight: 700; color: white; }

        /* ---- Picker modal ---- */
        .picker-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.45); backdrop-filter: blur(5px);
            z-index: 9999; align-items: center; justify-content: center;
        }
        .picker-overlay.open { display: flex; }
        .picker-box {
            background: white; border-radius: 22px; padding: 28px;
            min-width: 300px; max-width: 360px; width: 90%;
            box-shadow: 0 30px 70px rgba(0,0,0,0.2);
            animation: popIn 0.22s ease;
        }
        @keyframes popIn { from{transform:scale(0.88);opacity:0} to{transform:scale(1);opacity:1} }
        .picker-title { font-family:'Outfit',sans-serif; font-weight:800; font-size:1rem; color:#1E293B; margin-bottom:16px; }
        .picker-opts { display:flex; flex-direction:column; gap:8px; }
        .picker-opt {
            padding: 12px 16px; border-radius: 12px; border: 1.5px solid #E2E8F0;
            cursor: pointer; display: flex; align-items: center; justify-content: space-between;
            transition: all 0.18s; font-size: 0.88rem;
        }
        .picker-opt:hover { border-color: var(--primary); background: #EFF6FF; }
        .picker-opt.active-sel { border-color: var(--primary); background: #EFF6FF; }
        .picker-clear {
            margin-top: 8px; padding: 10px; border-radius: 11px;
            border: 1.5px dashed #E2E8F0; text-align: center;
            cursor: pointer; color: #94A3B8; font-size: 0.82rem;
            transition: all 0.2s;
        }
        .picker-clear:hover { background: #FEF2F2; border-color: #FCA5A5; color: #EF4444; }
        .picker-cancel {
            margin-top: 10px; width: 100%; padding: 10px; border-radius: 10px;
            border: none; background: #F1F5F9; color: #64748B;
            font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: 0.2s;
        }
        .picker-cancel:hover { background: #E2E8F0; }

        /* ---- Buttons ---- */
        .btn-prim {
            background: var(--primary); color: white; border: none;
            padding: 11px 22px; border-radius: 12px; font-weight: 700;
            font-size: 0.88rem; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; font-family: 'Outfit', sans-serif;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-prim:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-ghost {
            background: #F1F5F9; color: #475569; border: none;
            padding: 11px 18px; border-radius: 12px; font-weight: 600;
            font-size: 0.88rem; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; text-decoration: none;
            font-family: 'Outfit', sans-serif; transition: all 0.2s;
        }
        .btn-ghost:hover { background: #E2E8F0; }
        .btn-dark {
            background: #0F172A; color: white; border: none;
            padding: 11px 18px; border-radius: 12px; font-weight: 600;
            font-size: 0.88rem; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; text-decoration: none;
            font-family: 'Outfit', sans-serif; transition: all 0.2s;
        }
        .btn-dark:hover { background: #1E293B; }

        /* ---- Month nav ---- */
        .month-ctrl { display: flex; align-items: center; gap: 10px; }
        .month-btn {
            width: 32px; height: 32px; border-radius: 9px; border: 1.5px solid #E2E8F0;
            background: white; cursor: pointer; display: flex; align-items: center;
            justify-content: center; color: #475569; transition: all 0.2s;
        }
        .month-btn:hover { border-color: var(--primary); color: var(--primary); background: #EFF6FF; }
        .month-label { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.95rem; color: #1E293B; min-width: 155px; text-align: center; }

        /* ---- Empty state ---- */
        .empty-state { text-align: center; padding: 48px 20px; color: #94A3B8; }

        /* ---- Flash toast ---- */
        .flash-toast {
            position: fixed; top: 20px; right: 20px; z-index: 99999;
            background: #10B981; color: white; padding: 14px 22px;
            border-radius: 14px; font-weight: 600; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 30px rgba(16,185,129,0.3);
            animation: slideR 0.3s ease; transition: opacity 0.4s;
        }
        @keyframes slideR { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }

        /* ---- Empty info box ---- */
        .info-box {
            background: #F0F9FF; border-left: 4px solid var(--primary);
            border-radius: 0 12px 12px 0; padding: 12px 16px;
            font-size: 0.85rem; color: #0369A1; margin-bottom: 16px;
        }

        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        @media print {
            .no-print { display: none !important; }
            .jd-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
<div style="display:flex;height:100vh;overflow:hidden;">
    <?php include 'sidebar.php'; ?>
    <main style="flex:1;display:flex;flex-direction:column;min-width:0;overflow:hidden;">
        <?php 
        $breadcrumb = "Employee Hub / <strong>Jadwal Dinas</strong>";
        include "topbar.php"; 
        ?>


        <div class="jd-wrap" style="background:rgba(255,255,255,0.3);backdrop-filter:blur(20px);">

            <?php if ($flash): ?>
            <div class="flash-toast no-print" id="flashToast">
                <i data-lucide="check-circle" size="18"></i> <?= h($flash) ?>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.6rem;color:#1E293B;margin:0 0 2px;letter-spacing:-0.01em;">Jadwal Dinas Bulanan</h1>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:#10B981;"></span>
                        <p style="color:#64748B;font-size:0.85rem;margin:0;font-weight:500;"><?= h($isAdmin ? 'Semua Unit Kerja' : 'Unit: '.$unitLogin) ?></p>
                    </div>
                </div>
                <div class="no-print" style="display:flex;gap:12px;">
                    <button type="button" class="btn-ghost" onclick="openMagicGenerator()" style="background:#F0F9FF;color:#0369A1;border-color:#BAE6FD;">
                        <i data-lucide="sparkles" size="15"></i> Magic Generator
                    </button>
                    <button type="button" class="btn-ghost" onclick="openShiftSettings()"><i data-lucide="settings-2" size="15"></i> Setting Jam</button>
                    <button type="button" class="btn-ghost" 
                            onclick="confirmHapusSemua()"
                            style="color:#EF4444; border:1px solid #FEE2E2; background:#FEF2F2;">
                        <i data-lucide="trash-2" size="15"></i> Kosongkan
                    </button>
                    <a href="cetak_jadwal_dinas.php?bulan=<?= $selected_bulan ?>&tahun=<?= $selected_tahun ?>" target="_blank" class="btn-dark" style="padding:11px 24px;">
                        <i data-lucide="printer" size="16"></i> Cetak
                    </a>
                </div>
            </div>

            <form method="POST" id="formHapusSemua" style="display:none;">
                <?= csrf_field() ?>
                <input type="hidden" name="bulan" value="<?= $selected_bulan ?>">
                <input type="hidden" name="tahun" value="<?= $selected_tahun ?>">
                <input type="hidden" name="hapus_semua" value="1">
            </form>


            <!-- CARD: Data Jadwal Tersimpan -->
            <div class="jd-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
                    <div class="jd-card-title" style="margin:0;">
                        <i data-lucide="table-2" size="18" style="color:var(--primary)"></i>
                        Jadwal <?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?>
                    </div>
                    <!-- Filter & Navigation -->
                    <div class="jd-card" style="padding:16px 20px; border-radius:16px;">
                        <form method="GET" class="jd-row" style="align-items: center; gap:16px;">
                            <div class="jd-group" style="min-width:180px;">
                                <label class="jd-label">Filter Unit Kerja</label>
                                <select name="unit" class="jd-select" onchange="this.form.submit()">
                                    <?php if($isAdmin): ?>
                                    <option value="">— Tampilkan Semua —</option>
                                    <?php endif; ?>
                                    <?php foreach($unitOptions as $uo): ?>
                                    <option value="<?= h($uo) ?>" <?= $view_unit == $uo ? 'selected' : '' ?>><?= h($uo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="month-ctrl" style="margin-left:auto;">
                                <a href="?bulan=<?= $pm ?>&tahun=<?= $py ?>&unit=<?= h($view_unit) ?>" class="month-btn"><i data-lucide="chevron-left" size="15"></i></a>
                                <span class="month-label"><?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?></span>
                                <a href="?bulan=<?= $nm ?>&tahun=<?= $ny ?>&unit=<?= h($view_unit) ?>" class="month-btn"><i data-lucide="chevron-right" size="15"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($savedData)): ?>
                <div class="empty-state">
                    <div style="font-size:3rem;opacity:0.15;margin-bottom:10px;">📅</div>
                    <p style="font-weight:700;margin:0 0 6px;">Belum ada jadwal <?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?></p>
                    <p style="font-size:0.85rem;margin:0;">Gunakan form input di atas untuk menambahkan jadwal.</p>
                </div>
                <?php else: ?>
                <!-- Global Summary Summary (Beautified) -->
                <div class="shift-legend" style="margin-bottom:24px; padding:16px 20px; background:linear-gradient(to right, #F8FAFC, #FFFFFF); border-radius:16px; border:1px solid #E2E8F0; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                    <div style="font-size:0.7rem; font-weight:800; color:#64748B; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                        <div style="width:20px; height:20px; border-radius:6px; background:#E0F2FE; display:flex; align-items:center; justify-content:center;">
                            <i data-lucide="bar-chart-3" size="12" style="color:#0EA5E9;"></i>
                        </div>
                        ESTIMASI TOTAL SHIFT BULAN INI
                    </div>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                    <?php foreach ($jamList as $sid => $jdata): 
                         $count = $globalRekapShift[$sid] ?? 0;
                    ?>
                        <div class="legend-item" style="background:white; border:1.5px solid #F1F5F9; padding:6px 16px; border-radius:12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                            <div class="legend-dot" style="background:<?= $jdata['warna'] ?>; width:10px; height:10px;"></div>
                            <span style="font-weight:700; color:#334155; font-size:0.85rem;"><?= h($jdata['nama_jam']) ?></span>
                            <span class="legend-count" style="background:<?= $jdata['warna'] ?>15; color:<?= $jdata['warna'] ?>; min-width:32px; font-size:0.85rem; padding:2px 10px; border-radius:10px; font-weight:800;">
                                <?= $count ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="tbl-scroll">
                <table class="tbl-saved">
                    <thead>
                        <tr>
                            <th class="th-name">Karyawan</th>
                            <?php for($d=1;$d<=$daysInMonth;$d++):
                                $dow=(int)date('w',strtotime("$selected_tahun-$selected_bulan-$d"));
                                $isWE=($dow==0||$dow==6);
                            ?>
                            <th class="<?=$isWE?'weekend-h':''?>" title="<?=$hariIndo[$dow]?>, <?=$d?>">
                                <?=$d?><div style="font-size:0.6rem;font-weight:400;opacity:0.7"><?=$hariIndo[$dow]?></div>
                            </th>
                            <?php endfor; ?>
                            <th style="min-width:120px;text-align:left;padding-left:10px;">Rekap</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($savedData as $uid => $tglData): 
                        $karyawan = $savedNames[$uid] ?? 'Unknown';
                    ?>
                    <tr>
                        <td class="td-name">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span><?= h($karyawan) ?></span>
                                <form method="POST" id="formHapusKaryawan_<?= $uid ?>" style="display:none;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                                    <input type="hidden" name="bulan" value="<?= $selected_bulan ?>">
                                    <input type="hidden" name="tahun" value="<?= $selected_tahun ?>">
                                    <input type="hidden" name="hapus_karyawan" value="1">
                                </form>
                                <button type="button" class="no-print" 
                                        onclick="confirmHapusKaryawan('<?= $uid ?>', '<?= addslashes(h($karyawan)) ?>')"
                                        style="background:none; border:none; color:#94A3B8; cursor:pointer;" title="Hapus jadwal orang ini">
                                    <i data-lucide="trash-2" size="14"></i>
                                </button>
                            </div>
                            <div class="rekap-chips" id="rekap-row-<?= $uid ?>">
                                <?php if(!empty($rekapShift[$uid])): ?>
                                    <?php foreach($rekapShift[$uid] as $kid => $cnt): ?>
                                    <span class="rekap-chip" data-kid="<?= $kid ?>" style="background:<?= $jamList[$kid]['warna'] ?? '#64748B' ?>"><?= h($jamList[$kid]['nama_jam']??'') ?>: <?= $cnt ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php for($d=1;$d<=$daysInMonth;$d++):
                            $dow=(int)date('w',strtotime("$selected_tahun-$selected_bulan-$d"));
                            $isWE=($dow==0||$dow==6);
                            $e = $tglData[$d] ?? null;
                        ?>
                        <td class="<?=$isWE?'weekend-cell':''?> cell-interactive" 
                            data-uid="<?= $uid ?>" 
                            data-tgl="<?= $selected_tahun . '-' . sprintf('%02d', $selected_bulan) . '-' . sprintf('%02d', $d) ?>"
                            data-day="<?= $d ?>"
                            onclick="openPickerQuick(this)">
                            <?php if($e): ?>
                            <span class="shift-pill" style="background:<?= $e['warna'] ?>" title="<?= h($e['nama']) ?>">
                                <?= h($e['nama']) ?>
                            </span>
                            <?php else: ?>
                            <span class="shift-empty">·</span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                        <td style="text-align:left;padding-left:10px;vertical-align:middle;">
                            <?php
                            $total = array_sum($rekapShift[$karyawan] ?? []);
                            ?>
                            <span id="total-<?= $savedUids[$karyawan] ?>" style='font-weight:700;color:#1E293B;'><?= $total ?></span> <span style='color:#94A3B8;font-size:0.7rem;'>hari</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
                
                <div style="margin-top:20px; padding:15px; background:#F8FAFC; border-radius:12px; border:1px dashed #CBD5E1;">
                    <h5 style="font-size:0.9rem; font-weight:700; color:#1E293B; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="info" size="16"></i> Info Logika Magic
                    </h5>
                    <p style="font-size:0.8rem; color:#64748B; margin:0; line-height:1.5;">
                        Urutan otomatis 5 Hari: <strong>Pagi &rarr; Siang &rarr; Malam &rarr; Libur (Lepas) &rarr; Libur</strong>. 
                        Sistem secara pintar memberikan istirahat 2 hari berturut-turut setelah tugas Malam agar kondisi prima.
                    </p>
                </div>
            </div>

        </div><!-- end jd-wrap -->
    </main>
</div>

<!-- Magic Generator Modal -->
<div class="picker-overlay" id="magicOverlay" onclick="if(event.target===this)closeMagicGenerator()">
    <div class="picker-box" style="max-width:500px; width:95%; overflow:hidden; display:flex; flex-direction:column; max-height:90vh;">
        <div class="picker-title"><i data-lucide="sparkles" size="18"></i> Magic Schedule Generator</div>
        <div style="padding:15px; max-height:70vh; overflow-y:auto;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:15px;">
                <div class="jd-group">
                    <label class="jd-label">Target Bulan</label>
                    <select id="genBulan" class="jd-select" style="width:100%;">
                        <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $m==$selected_bulan?'selected':'' ?>><?= $bulanIndo[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="jd-group">
                    <label class="jd-label">Target Tahun</label>
                    <select id="genTahun" class="jd-select" style="width:100%;">
                        <?php for ($y=date('Y');$y<=date('Y')+1;$y++): ?>
                        <option value="<?= $y ?>" <?= $y==$selected_tahun?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="jd-group" style="margin-bottom:15px;">
                <label class="jd-label">Unit Kerja</label>
                <select id="genUnit" class="jd-select" style="width:100%;" onchange="loadEmployeesByUnit(this.value)">
                    <option value="">— Pilih Unit Kerja —</option>
                    <?php if ($isAdmin): ?>
                        <?php foreach ($unitOptions as $uo): ?>
                        <option value="<?= h($uo) ?>" <?= $uo == $unitLogin ? 'selected' : '' ?>><?= h($uo) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="<?= h($unitLogin) ?>" selected><?= h($unitLogin) ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Removed PILIH MINGGU as requested -->

            <div class="jd-label" style="margin-bottom:10px;">Quota per Shift (Orang)</div>
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:10px; margin-bottom:20px;">
                <?php foreach($jamList as $sid => $jdata): 
                    $k = strtoupper($jdata['kode']);
                    if($k == 'LIBUR' || $k == 'LEPAS') continue;
                ?>
                <div class="jd-group" style="border:1px solid #E2E8F0; padding:8px; border-radius:8px;">
                    <label style="font-size:0.75rem; font-weight:700; color:<?= $jdata['warna'] ?>;"><?= h($jdata['nama_jam']) ?></label>
                    <input type="number" class="gen-quota jd-select" data-id="<?= $sid ?>" value="0" min="0" style="width:100%; height:32px;">
                </div>
                <?php endforeach; ?>
            </div>

            <div class="jd-label" style="margin-bottom:10px;">Pilih Pegawai yang DIJADWALKAN (DICEKLIS = MASUK JADWAL)</div>
            <div id="excludeListWrap" style="background:white; border:1px solid #E2E8F0; border-radius:12px; overflow:auto; max-height:350px; min-height:60px; margin-bottom:10px;">
                <!-- Pegawai dipopulasi lewat JS Matrix Table -->
                <div style="text-align:center; color:#94A3B8; font-size:12px; padding:20px;">Pilih unit untuk melihat daftar pegawai.</div>
            </div>
        </div>
        <div style="padding:15px; border-top:1px solid #E2E8F0; display:flex; gap:10px;">
            <button class="btn-prim" style="flex:1;" onclick="processGeneration()">
                <i data-lucide="zap" size="15"></i> Jalankan Pengacakan
            </button>
            <button class="btn-ghost" onclick="closeMagicGenerator()">Batal</button>
        </div>
    </div>
</div>
<div class="picker-overlay" id="pickerOverlay" onclick="if(event.target===this)closePicker()">
    <div class="picker-box">
        <div class="picker-title">Pilih Shift — <span id="pickerDayLabel"></span></div>
        <div class="picker-opts" id="pickerOpts">
            <?php foreach($jamList as $sid => $jdata): ?>
            <div class="picker-opt" data-id="<?= $sid ?>" data-warna="<?= $jdata['warna'] ?>" data-nama="<?= h($jdata['nama_jam']) ?>"
                 onclick="selectShift(<?= $sid ?>,'<?= $jdata['warna'] ?>','<?= addslashes($jdata['nama_jam']) ?>')">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:10px;height:10px;border-radius:4px;background:<?= $jdata['warna'] ?>;flex-shrink:0;"></div>
                    <strong><?= h($jdata['nama_jam']) ?></strong>
                </div>
                <span style="font-size:0.75rem;color:#94A3B8;"><?= substr($jdata['jam_mulai'],0,5) ?>–<?= substr($jdata['jam_selesai'],0,5) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="padding:15px; border-top:1px solid #E2E8F0; text-align:right;">
            <button class="btn-ghost" onclick="closePicker()">Tutup</button>
        </div>
    </div>
</div>

<!-- Shift Settings Modal -->
<div class="picker-overlay" id="shiftSettingsOverlay" onclick="if(event.target===this)closeShiftSettings()">
    <div class="picker-box" style="max-width:650px; width:95%; padding:0; overflow:hidden; box-sizing:border-box;">
        <div class="picker-title" style="display:flex; align-items:center; gap:12px; padding:24px 30px; border-bottom:1px solid #F1F5F9; margin-bottom:0; background:#fff;">
            <i data-lucide="settings-2" size="22" style="color:var(--primary);"></i> 
            <span style="font-size:1.1rem; letter-spacing:-0.01em; font-weight:800;">Pengaturan Jam Jaga</span>
        </div>
        <div style="padding:25px; max-height:65vh; overflow-y:auto; background:#F8FAFC; box-sizing:border-box;">
            <div id="shiftSettingsList">
                <?php foreach($jamList as $sid => $j): 
                    $k = strtoupper($j['kode']);
                    if($k == 'LIBUR' || $k == 'LEPAS') continue;
                ?>
                <div class="jd-card" style="margin-bottom:20px; padding:24px; border:none; background:#ffffff; box-shadow:0 2px 12px rgba(0,0,0,0.04); box-sizing:border-box; width:100%;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #F8FAFC; padding-bottom:15px; flex-wrap:nowrap;">
                        <span style="font-weight:800; color:<?= $j['warna'] ?>; font-family:'Outfit',sans-serif; font-size:1.05rem; display:flex; align-items:center; gap:10px; min-width:0; overflow:hidden; text-overflow:ellipsis;">
                            <span style="width:12px; height:12px; border-radius:3px; background:<?= $j['warna'] ?>; flex-shrink:0;"></span>
                            <?= h($j['nama_jam']) ?>
                        </span>
                        <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
                            <label style="font-size:0.65rem; color:#94A3B8; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; white-space:nowrap;">PILIH WARNA</label>
                            <input type="color" class="shift-color-input" data-id="<?= $sid ?>" value="<?= $j['warna'] ?>" 
                                   style="border:none; width:36px; height:36px; cursor:pointer; background:none; padding:0; display:block; border-radius:6px; overflow:hidden;">
                        </div>
                    </div>
                    
                    <div style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap:20px; width:100%; box-sizing:border-box;">
                        <div style="display:flex; flex-direction:column; gap:8px; min-width:0;">
                            <label class="jd-label" style="color:#64748B; font-size:0.7rem; letter-spacing:0.05em; font-weight:700;">JAM MULAI</label>
                            <input type="time" class="jd-select shift-start-input" data-id="<?= $sid ?>" value="<?= substr($j['jam_mulai'],0,5) ?>" 
                                   style="width:100%; height:48px; border-radius:12px; padding:0 12px; border:1px solid #E2E8F0; font-weight:600; font-size:0.95rem; background:#fff; box-sizing:border-box; display:block;">
                        </div>
                        <div style="display:flex; flex-direction:column; gap:8px; min-width:0;">
                            <label class="jd-label" style="color:#64748B; font-size:0.7rem; letter-spacing:0.05em; font-weight:700;">JAM SELESAI</label>
                            <input type="time" class="jd-select shift-end-input" data-id="<?= $sid ?>" value="<?= substr($j['jam_selesai'],0,5) ?>" 
                                   style="width:100%; height:48px; border-radius:12px; padding:0 12px; border:1px solid #E2E8F0; font-weight:600; font-size:0.95rem; background:#fff; box-sizing:border-box; display:block;">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="padding:24px 30px; border-top:1px solid #F1F5F9; display:flex; gap:15px; background:#ffffff; box-sizing:border-box;">
            <button class="btn-prim" style="flex:2; height:54px; border-radius:15px; font-size:1rem; justify-content:center; box-sizing:border-box;" onclick="saveShiftSettings()">
                <i data-lucide="save" size="20"></i> Simpan Perubahan
            </button>
            <button class="btn-ghost" style="flex:1; height:54px; border-radius:15px; color:#64748B; justify-content:center; background:#F1F5F9; box-sizing:border-box;" onclick="closeShiftSettings()">Batal</button>
        </div>
    </div>
</div>

<script>
// --- Global Debug Hook ---
console.log("BexMedia Schedule System: Script Initializing...");

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    console.log("BexMedia Schedule System: Lucide Icons Ready.");
});

// --- Magic Generator Logic ---
window.processGeneration = function() {
    console.log("Process Generation Triggered");
    
    // 1. Collect Quota
    var quota = {};
    document.querySelectorAll('.gen-quota').forEach(function(input) {
        var val = parseInt(input.value) || 0;
        if (val > 0) quota[input.dataset.id] = val;
    });

    // 2. Get Unit
    var unitEl = document.getElementById('genUnit');
    if(!unitEl) {
        console.error("Critical: genUnit element not found!");
        return;
    }
    var unit = unitEl.value;

    // 3. Validation
    if(!unit) {
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Unit Belum Dipilih',
                text: 'Silakan pilih Unit Kerja terlebih dahulu!',
                icon: 'warning',
                confirmButtonColor: '#3B82F6',
                customClass: { popup: 'bex-swal' }
            });
        } else {
            alert("Silakan pilih Unit Kerja terlebih dahulu!");
        }
        return;
    }

    if (Object.keys(quota).length === 0) {
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Quota Kosong',
                text: 'Silakan isi jumlah quota minimal untuk setidaknya satu shift!',
                icon: 'warning',
                confirmButtonColor: '#3B82F6',
                customClass: { popup: 'bex-swal' }
            });
        } else {
            alert("Silakan isi jumlah quota minimal!");
        }
        return;
    }

    // 4. Confirmation
    if(typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Konfirmasi Pengacakan',
            html: 'Sistem akan mengacak jadwal untuk <strong>SELURUH BULAN</strong> (Pekan 1-5) berdasarkan matriks pengecualian.<br><br><span style="color:#EF4444; font-size:0.8rem;">Data jadwal lama di bulan target akan ditumpuk.</span>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#F1F5F9',
            confirmButtonText: 'Ya, Jalankan!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: { popup: 'bex-swal' }
        }).then(function(result) {
            if (result.isConfirmed) {
                runFullMonthProcess(unit, quota);
            }
        });
    } else {
        if(confirm("Jalankan pengacakan jadwal satu bulan penuh?")) {
            runFullMonthProcess(unit, quota);
        }
    }
};

async function runFullMonthProcess(unit, quota) {
    if(typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Sedang Memproses...',
            html: 'Mohon tunggu, sistem sedang membangun jadwal terbaik...',
            allowOutsideClick: false,
            didOpen: function() { Swal.showLoading(); },
            customClass: { popup: 'bex-swal' }
        });
    }

    var bEl = document.getElementById('genBulan');
    var tEl = document.getElementById('genTahun');
    var genBulan = bEl ? bEl.value : 0;
    var genTahun = tEl ? tEl.value : 0;
    var bNama = bEl ? bEl.options[bEl.selectedIndex].text : '';
    
    for (var m = 1; m <= 5; m++) {
        var exclude = [];
        document.querySelectorAll('.gen-exclude[data-week="'+m+'"]:checked').forEach(function(cb) {
            exclude.push(cb.value);
        });

        try {
            console.log("Processing Week " + m + " for Unit: " + unit + ", Selected IDs:", exclude);
            var res = await $.ajax({
                url: 'ajax_generate_jadwal.php',
                method: 'POST',
                data: {
                    unit: unit,
                    bulan: genBulan,
                    tahun: genTahun,
                    minggu: m,
                    quota: quota,
                    exclude_ids: exclude
                }
            });
            if (res && !res.success) {
                console.warn("Pekan " + m + " warning:", res.message);
            }
        } catch (e) {
            console.error("Pekan " + m + " failed", e);
        }
    }

    if(typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Jadwal untuk ' + bNama + ' ' + genTahun + ' telah diperbarui sepenuhnya.',
            icon: 'success',
            confirmButtonColor: '#10B981',
            customClass: { popup: 'bex-swal' }
        }).then(function() {
            location.href = "jadwal_dinas.php?bulan=" + genBulan + "&tahun=" + genTahun + "&unit=" + encodeURIComponent(unit);
        });
    } else {
        alert("Selesai! Jadwal diperbarui.");
        location.href = "jadwal_dinas.php?bulan=" + genBulan + "&tahun=" + genTahun + "&unit=" + encodeURIComponent(unit);
    }
}

// --- Navigation & Matrix ---
function openMagicGenerator() {
    document.getElementById('magicOverlay').classList.add('open');
    let unit = document.getElementById('genUnit').value;
    if(unit) loadEmployeesByUnit(unit);
}

function loadEmployeesByUnit(unit) {
    let wrap = document.getElementById('excludeListWrap');
    if(!unit) {
        wrap.innerHTML = '<div style="text-align:center; color:#94A3B8; font-size:12px; padding:20px;">Pilih unit untuk melihat daftar pegawai.</div>';
        return;
    }
    wrap.innerHTML = '<div style="text-align:center; padding:20px; color:#64748B;"><i data-lucide="loader-2" class="spin" style="margin-right:8px;"></i> Memuat daftar pegawai...</div>';
    lucide.createIcons();
    
    $.ajax({
        url: 'ajax_get_employees_by_unit.php',
        method: 'POST',
        data: { unit: unit },
        success: function(html) {
            wrap.innerHTML = html;
            lucide.createIcons();
        }
    });
}

function toggleMatrixCol(week, cb) {
    document.querySelectorAll('.gen-exclude[data-week="'+week+'"]').forEach(c => c.checked = cb.checked);
}
function toggleMatrixRow(uid, cb) {
    document.querySelectorAll('.gen-exclude[data-uid="'+uid+'"]').forEach(c => c.checked = cb.checked);
}
function toggleMatrixAll(cb) {
    document.querySelectorAll('.gen-exclude').forEach(c => c.checked = cb.checked);
    document.querySelectorAll('.row-trigger').forEach(c => c.checked = cb.checked);
}
function closeMagicGenerator() {
    document.getElementById('magicOverlay').classList.remove('open');
}

// --- Schedule Quick Interaction ---
var curDay = 0;
var shiftState = {}; 
var activeMode = 'draft';
var activeCell = null;

const BexToast = (typeof Swal !== 'undefined') ? Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, 
    timerProgressBar: true, customClass: { popup: 'bex-toast-premium' }
}) : null;

function openPickerDraft(day) {
    curDay = day;
    activeMode = 'draft';
    document.getElementById('pickerDayLabel').textContent = 'Pilih Shift Tgl: ' + day;
    document.getElementById('pickerOverlay').classList.add('open');
}

function openPickerQuick(el) {
    activeCell = el;
    activeMode = 'quick';
    document.getElementById('pickerDayLabel').textContent = 'Pilih Shift: ' + el.dataset.tgl;
    document.getElementById('pickerOverlay').classList.add('open');
}

function closePicker() {
    document.getElementById('pickerOverlay').classList.remove('open');
    curDay = 0;
    activeCell = null;
}

function selectShift(id, warna, nama) {
    if (activeMode === 'draft') {
        if (!curDay) return;
        shiftState[curDay] = {id, warna, nama};
        document.getElementById('jam-' + curDay).value = id;
        var btn = document.getElementById('cb-' + curDay);
        btn.classList.add('filled');
        btn.style.background = warna;
        btn.style.borderColor = warna;
        btn.style.color = 'white';
        btn.style.fontSize = '0.62rem';
        btn.style.padding = '0 3px';
        btn.textContent = nama.length > 5 ? nama.substring(0,5)+'…' : nama;
        updateCounts();
        closePicker();
    } else {
        if (!activeCell) return;
        let uid = activeCell.dataset.uid;
        let tgl = activeCell.dataset.tgl;
        activeCell.classList.add('cell-loading');
        closePicker();
        $.ajax({
            url: 'ajax_save_jadwal_v2.php',
            method: 'POST',
            data: { csrf_token: '<?= $_SESSION['csrf_token'] ?>', user_id: uid, tanggal: tgl, shift_id: id },
            dataType: 'json',
            success: function(res) {
                activeCell.classList.remove('cell-loading');
                if (res.success) {
                    activeCell.innerHTML = `<span class="shift-pill" style="background:${res.warna}" title="${res.nama}">${res.nama}</span>`;
                    refreshRekapRow(uid);
                    if(BexToast) BexToast.fire({ icon: 'success', title: 'Jadwal diperbarui' });
                }
            }
        });
    }
}

function clearShift() {
    if (activeMode === 'draft') {
        if (!curDay) return;
        delete shiftState[curDay];
        document.getElementById('jam-' + curDay).value = '';
        var btn = document.getElementById('cb-' + curDay);
        btn.classList.remove('filled');
        btn.style.background = ''; btn.style.borderColor = ''; btn.style.color = '';
        btn.style.fontSize = ''; btn.style.padding = ''; btn.textContent = '+';
        updateCounts();
        closePicker();
    } else {
        if (!activeCell) return;
        let uid = activeCell.dataset.uid;
        let tgl = activeCell.dataset.tgl;
        activeCell.classList.add('cell-loading');
        closePicker();
        $.ajax({
            url: 'ajax_save_jadwal_v2.php',
            method: 'POST',
            data: { csrf_token: '<?= $_SESSION['csrf_token'] ?>', user_id: uid, tanggal: tgl, shift_id: 0 },
            dataType: 'json',
            success: function(res) {
                activeCell.classList.remove('cell-loading');
                if (res.success) {
                    activeCell.innerHTML = `<span class="shift-empty">·</span>`;
                    refreshRekapRow(uid);
                    if(BexToast) BexToast.fire({ icon: 'success', title: 'Shift dihapus' });
                }
            }
        });
    }
}

function updateCounts() {
    var counts = {};
    <?php foreach($jamList as $sid => $j): ?>
    counts[<?= $sid ?>] = 0;
    <?php endforeach; ?>
    for (var d in shiftState) {
        var sid = shiftState[d].id;
        if (counts[sid] !== undefined) counts[sid]++;
    }
    for (var k in counts) {
        var el = document.getElementById('cnt-' + k);
        if (el) {
            el.textContent = counts[k];
            if (counts[k] > 0) {
                el.parentElement.style.borderColor = el.style.backgroundColor;
                el.parentElement.style.background = '#F8FAFC';
            } else {
                el.parentElement.style.borderColor = '#F1F5F9';
                el.parentElement.style.background = 'white';
            }
        }
    }
}

function refreshRekapRow(uid) {
    let rowEl = document.querySelector(`td[data-uid="${uid}"]`);
    if(!rowEl) return;
    let row = rowEl.closest('tr');
    let cells = row.querySelectorAll('.cell-interactive .shift-pill');
    let counts = {};
    let total = 0;
    
    cells.forEach(p => {
        let name = p.textContent.trim();
        let color = p.style.backgroundColor;
        if (!counts[name]) counts[name] = { count: 0, color: color };
        counts[name].count++;
        total++;
    });
    
    let rekapDiv = document.getElementById('rekap-row-' + uid);
    if(rekapDiv) {
        rekapDiv.innerHTML = '';
        for (let name in counts) {
            rekapDiv.innerHTML += `<span class="rekap-chip" style="background:${counts[name].color}">${name}: ${counts[name].count}</span> `;
        }
    }
    
    let totalEl = document.getElementById('total-' + uid);
    if(totalEl) totalEl.textContent = total;
    refreshGlobalRekap();
}

function refreshGlobalRekap() {
    let allCells = document.querySelectorAll('.cell-interactive .shift-pill');
    let globalCounts = {};
    allCells.forEach(p => {
        let name = p.textContent.trim();
        globalCounts[name] = (globalCounts[name] || 0) + 1;
    });
    document.querySelectorAll('.shift-legend .legend-item').forEach(item => {
        let label = item.querySelector('span:not(.legend-count)').textContent.trim();
        let countEl = item.querySelector('.legend-count');
        if(countEl) countEl.textContent = globalCounts[label] || 0;
    });
}

function openShiftSettings() {
    document.getElementById('shiftSettingsOverlay').classList.add('open');
}

function closeShiftSettings() {
    document.getElementById('shiftSettingsOverlay').classList.remove('open');
}

function saveShiftSettings() {
    let shifts = [];
    document.querySelectorAll('#shiftSettingsList .jd-card').forEach(card => {
        let id = card.querySelector('.shift-color-input').dataset.id;
        shifts.push({
            id: id,
            start: card.querySelector('.shift-start-input').value,
            end: card.querySelector('.shift-end-input').value,
            color: card.querySelector('.shift-color-input').value
        });
    });

    if(BexToast) BexToast.fire({ title: 'Menyimpan...', didOpen: () => { Swal.showLoading(); } });

    $.ajax({
        url: 'ajax_update_shifts.php',
        method: 'POST',
        data: { shifts: shifts },
        success: function(res) {
            if (res.success) {
                if(BexToast) BexToast.fire({ icon: 'success', title: 'Pengaturan jam disimpan' });
                setTimeout(() => { location.reload(); }, 1000);
            }
        }
    });
}

function confirmHapusSemua() {
    if(typeof Swal === 'undefined') { if(confirm("Kosongkan semua jadwal bulan ini?")) document.getElementById('formHapusSemua').submit(); return; }
    Swal.fire({
        title: 'Kosongkan Jadwal?',
        text: "Seluruh data jadwal di bulan ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#F1F5F9',
        confirmButtonText: 'Ya, Kosongkan!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: { popup: 'bex-swal' }
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('formHapusSemua').submit();
    });
}

function confirmHapusKaryawan(uid, nama) {
    if(typeof Swal === 'undefined') { if(confirm("Hapus jadwal untuk " + nama + "?")) document.getElementById('formHapusKaryawan_' + uid).submit(); return; }
    Swal.fire({
        title: 'Hapus Jadwal?',
        html: `Apakah Anda yakin ingin menghapus seluruh jadwal bulan ini untuk <strong>${nama}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#F1F5F9',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: { popup: 'bex-swal' }
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('formHapusKaryawan_' + uid).submit();
    });
}

lucide.createIcons();
</script>
<style>
/* Additional Toast Polish */
.bex-toast-premium {
    border-radius: 16px !important;
    background: #0F172A !important;
    color: #fff !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4) !important;
}
.swal2-timer-progress-bar { background: var(--primary) !important; }
</style>
</body>
</html>
