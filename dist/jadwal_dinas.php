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
  ('PAGI','Pagi','07:00:00','14:00:00','#3B82F6'),
  ('SIANG','Siang','14:00:00','21:00:00','#10B981'),
  ('MALAM','Malam','21:00:00','07:00:00','#8B5CF6'),
  ('MID_P','Middle Pagi','10:00:00','17:00:00','#F59E0B'),
  ('MID_S','Middle Siang','17:00:00','00:00:00','#EF4444'),
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

if ($isAdmin || $unitLogin === '') {
    $userResult = mysqli_query($conn, "SELECT id, nama_lengkap AS nama, unit_kerja FROM users ORDER BY nama_lengkap");
} else {
    $uk = mysqli_real_escape_string($conn, $unitLogin);
    $userResult = mysqli_query($conn, "SELECT id, nama_lengkap AS nama, unit_kerja FROM users WHERE unit_kerja='$uk' ORDER BY nama_lengkap");
}
$userList = [];
while ($u = mysqli_fetch_assoc($userResult)) $userList[] = $u;

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
$savedResult = mysqli_query($conn, "SELECT jd.id, jd.user_id, jd.tanggal, jd.jam_kerja_id,
    u.nama_lengkap AS nama_kary, jk.nama_jam, jk.warna
    FROM jadwal_dinas jd
    JOIN users u ON jd.user_id=u.id
    JOIN jam_kerja jk ON jd.jam_kerja_id=jk.id
    WHERE jd.bulan='$selected_bulan' AND jd.tahun='$selected_tahun'
    ORDER BY u.nama_lengkap, jd.tanggal");

$savedData = []; $savedIds = [];
while ($row = mysqli_fetch_assoc($savedResult)) {
    $tgl = (int)date('j', strtotime($row['tanggal']));
    $savedData[$row['nama_kary']][$tgl] = [
        'nama'  => $row['nama_jam'],
        'warna' => $row['warna'],
        'kid'   => $row['jam_kerja_id'],
    ];
    $savedIds[$row['nama_kary']][$tgl] = $row['id'];
}

// Hitung rekap shift per orang untuk bulan ini
$rekapShift = [];
foreach ($savedData as $karyawan => $tglData) {
    foreach ($tglData as $tgl => $entry) {
        $rekapShift[$karyawan][$entry['kid']] = ($rekapShift[$karyawan][$entry['kid']] ?? 0) + 1;
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
    <script src="https://unpkg.com/lucide@latest"></script>
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

        /* ---- Shift Legend ---- */
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
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.55rem;color:#1E293B;margin:0 0 2px;">Jadwal Dinas Bulanan</h1>
                    <p style="color:#64748B;font-size:0.83rem;margin:0;"><?= h($isAdmin ? 'Semua Unit Kerja' : 'Unit: '.$unitLogin) ?></p>
                </div>
                <div class="no-print" style="display:flex;gap:10px;">
                    <a href="jam_kerja.php" class="btn-ghost"><i data-lucide="clock" size="15"></i> Kelola Shift</a>
                    <a href="cetak_jadwal_dinas.php?bulan=<?= $selected_bulan ?>&tahun=<?= $selected_tahun ?>" target="_blank" class="btn-dark">
                        <i data-lucide="printer" size="15"></i> Cetak
                    </a>
                </div>
            </div>

            <!-- CARD: Input Jadwal -->
            <div class="jd-card no-print">
                <div class="jd-card-title">
                    <i data-lucide="calendar-plus" size="18" style="color:var(--primary)"></i>
                    Input Jadwal Karyawan
                </div>

                <?php if (empty($jamList)): ?>
                <div class="info-box">
                    <i data-lucide="alert-triangle" size="15" style="vertical-align:middle;margin-right:6px;"></i>
                    Belum ada data shift/jam kerja. Silakan tambahkan melalui menu <strong>Kelola Shift</strong> terlebih dahulu.
                </div>
                <?php elseif (empty($userList)): ?>
                <div class="info-box">
                    <i data-lucide="users" size="15" style="vertical-align:middle;margin-right:6px;"></i>
                    Belum ada karyawan terdaftar di unit kerja Anda.
                </div>
                <?php else: ?>
                <form method="POST" id="formJadwal">
                    <?php echo csrf_field(); ?>
                    <div class="jd-row" style="margin-bottom:18px;">
                        <div class="jd-group">
                            <label class="jd-label">Karyawan</label>
                            <select name="user_id" class="jd-select" required id="selKaryawan" style="min-width:220px;">
                                <option value="">— Pilih Karyawan —</option>
                                <?php foreach ($userList as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $selected_emp==$u['id']?'selected':'' ?>>
                                    <?= h($u['nama']) ?><?= $isAdmin ? ' ('.$u['unit_kerja'].')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="jd-group">
                            <label class="jd-label">Bulan</label>
                            <select name="bulan" class="jd-select" id="inpBulan">
                                <?php for ($m=1;$m<=12;$m++): ?>
                                <option value="<?= $m ?>" <?= $m==$selected_bulan?'selected':'' ?>><?= $bulanIndo[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="jd-group">
                            <label class="jd-label">Tahun</label>
                            <select name="tahun" class="jd-select" id="inpTahun">
                                <?php for ($y=date('Y')-2;$y<=date('Y')+2;$y++): ?>
                                <option value="<?= $y ?>" <?= $y==$selected_tahun?'selected':'' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="simpan" class="btn-prim">
                            <i data-lucide="save" size="15"></i> Simpan Jadwal
                        </button>
                    </div>

                    <!-- Shift Legend + Counter -->
                    <div class="shift-legend" id="shiftLegend">
                        <?php foreach ($jamList as $sid => $jdata): ?>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:<?= $jdata['warna'] ?>"></div>
                            <span><?= h($jdata['nama_jam']) ?></span>
                            <span class="legend-count" style="background:<?= $jdata['warna'] ?>20;color:<?= $jdata['warna'] ?>" id="cnt-<?= $sid ?>">0</span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Date Grid -->
                    <div class="tbl-scroll">
                    <table class="tbl-input">
                        <thead>
                            <tr>
                                <?php for($d=1;$d<=$daysInMonth;$d++):
                                    $dow=(int)date('w',strtotime("$selected_tahun-$selected_bulan-$d"));
                                    $isWE=($dow==0||$dow==6);
                                ?>
                                <th class="<?= $isWE?'weekend-h':'' ?>">
                                    <div><?= $d ?></div>
                                    <div style="font-size:0.62rem;opacity:0.6"><?= $hariIndo[$dow] ?></div>
                                </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <?php for($d=1;$d<=$daysInMonth;$d++):
                                $dow=(int)date('w',strtotime("$selected_tahun-$selected_bulan-$d"));
                                $isWE=($dow==0||$dow==6);
                            ?>
                            <td style="<?= $isWE?'background:#FFF5F5;':'' ?>">
                                <button type="button" class="cell-btn" id="cb-<?= $d ?>"
                                    data-day="<?= $d ?>" onclick="openPicker(<?= $d ?>)"
                                    title="Tgl <?= $d ?>">+</button>
                                <input type="hidden" name="jam[<?= $d ?>]" id="jam-<?= $d ?>" value="">
                            </td>
                            <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <!-- CARD: Data Jadwal Tersimpan -->
            <div class="jd-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
                    <div class="jd-card-title" style="margin:0;">
                        <i data-lucide="table-2" size="18" style="color:var(--primary)"></i>
                        Jadwal <?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?>
                    </div>
                    <div class="month-ctrl no-print">
                        <a href="?bulan=<?= $pm ?>&tahun=<?= $py ?>" class="month-btn"><i data-lucide="chevron-left" size="15"></i></a>
                        <span class="month-label"><?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?></span>
                        <a href="?bulan=<?= $nm ?>&tahun=<?= $ny ?>" class="month-btn"><i data-lucide="chevron-right" size="15"></i></a>
                    </div>
                </div>

                <?php if (empty($savedData)): ?>
                <div class="empty-state">
                    <div style="font-size:3rem;opacity:0.15;margin-bottom:10px;">📅</div>
                    <p style="font-weight:700;margin:0 0 6px;">Belum ada jadwal <?= $bulanIndo[$selected_bulan] ?> <?= $selected_tahun ?></p>
                    <p style="font-size:0.85rem;margin:0;">Gunakan form input di atas untuk menambahkan jadwal.</p>
                </div>
                <?php else: ?>
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
                    <?php foreach($savedData as $karyawan => $tglData): ?>
                    <tr>
                        <td class="td-name">
                            <div><?= h($karyawan) ?></div>
                            <?php if(!empty($rekapShift[$karyawan])): ?>
                            <div class="rekap-chips">
                                <?php foreach($rekapShift[$karyawan] as $kid => $cnt): ?>
                                <span class="rekap-chip" style="background:<?= $jamList[$kid]['warna'] ?? '#64748B' ?>"><?= h($jamList[$kid]['nama_jam']??'') ?>: <?= $cnt ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <?php for($d=1;$d<=$daysInMonth;$d++):
                            $dow=(int)date('w',strtotime("$selected_tahun-$selected_bulan-$d"));
                            $isWE=($dow==0||$dow==6);
                            $e = $tglData[$d] ?? null;
                        ?>
                        <td class="<?=$isWE?'weekend-cell':''?>">
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
                            echo "<span style='font-weight:700;color:#1E293B;'>$total</span> <span style='color:#94A3B8;font-size:0.7rem;'>hari</span>";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- end jd-wrap -->
    </main>
</div>

<!-- Picker Modal -->
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
        <div class="picker-clear" onclick="clearShift()"><i data-lucide="x" size="13" style="vertical-align:middle;margin-right:4px;"></i>Hapus shift ini</div>
        <button class="picker-cancel" onclick="closePicker()">Batal</button>
    </div>
</div>

<script>
lucide.createIcons();

var curDay = 0;
var shiftState = {}; // day -> {id, warna, nama}

function openPicker(day) {
    curDay = day;
    document.getElementById('pickerDayLabel').textContent = 'Tanggal ' + day;
    document.querySelectorAll('.picker-opt').forEach(o => {
        o.classList.toggle('active-sel', shiftState[day] && parseInt(o.dataset.id) === shiftState[day].id);
    });
    document.getElementById('pickerOverlay').classList.add('open');
}
function closePicker() {
    document.getElementById('pickerOverlay').classList.remove('open');
    curDay = 0;
}
function selectShift(id, warna, nama) {
    shiftState[curDay] = {id, warna, nama};
    document.getElementById('jam-' + curDay).value = id;
    var btn = document.getElementById('cb-' + curDay);
    btn.classList.add('filled');
    btn.style.background      = warna;
    btn.style.borderColor     = warna;
    btn.style.color           = 'white';
    btn.style.fontSize        = '0.62rem';
    btn.style.padding         = '0 3px';
    btn.textContent           = nama.length>5 ? nama.substring(0,5)+'…' : nama;
    updateCounts();
    closePicker();
}
function clearShift() {
    if (!curDay) return;
    delete shiftState[curDay];
    document.getElementById('jam-' + curDay).value = '';
    var btn = document.getElementById('cb-' + curDay);
    btn.classList.remove('filled');
    btn.style.background = '';
    btn.style.borderColor = '';
    btn.style.color = '';
    btn.style.fontSize = '';
    btn.style.padding = '';
    btn.textContent = '+';
    updateCounts();
    closePicker();
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
        if (el) el.textContent = counts[k];
    }
}

// Flash hide
var ft = document.getElementById('flashToast');
if (ft) setTimeout(() => ft.style.opacity = '0', 3500);

// ESC close
document.addEventListener('keydown', e => { if(e.key==='Escape') closePicker(); });

// Pre-fill input grid jika ada emp param (load existing schedule for employee)
<?php if ($selected_emp && isset($savedData)): ?>
// Find employee in savedData by user_id
var empId = <?= $selected_emp ?>;
// Fill cells from existing saved data for selected employee
<?php
$empNama = '';
foreach ($userList as $ul) {
    if ($ul['id'] == $selected_emp) { $empNama = $ul['nama']; break; }
}
if ($empNama && isset($savedData[$empNama])) {
    foreach ($savedData[$empNama] as $d => $entry) {
        echo "selectShiftSilent($d, {$entry['kid']}, '".addslashes($entry['warna'])."', '".addslashes($entry['nama'])."');\n";
    }
}
?>
<?php endif; ?>
function selectShiftSilent(day, id, warna, nama) {
    shiftState[day] = {id, warna, nama};
    document.getElementById('jam-' + day).value = id;
    var btn = document.getElementById('cb-' + day);
    if (btn) {
        btn.classList.add('filled');
        btn.style.background  = warna;
        btn.style.borderColor = warna;
        btn.style.color       = 'white';
        btn.style.fontSize    = '0.62rem';
        btn.style.padding     = '0 3px';
        btn.textContent       = nama.length > 5 ? nama.substring(0,5)+'…' : nama;
    }
    updateCounts();
}

lucide.createIcons();
</script>
</body>
</html>
