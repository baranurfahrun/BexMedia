<?php
include 'security.php';
include 'koneksi.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

// user_id and common data already handled by security.php
$user_id = $user_id_logged;
if (!$user_id) {
    header("Location: ../login.php"); exit;
}



// Auto-create supporting tables if not exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS informasi_pribadi (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  jenis_kelamin enum('L','P') DEFAULT NULL,
  tempat_lahir varchar(100) DEFAULT NULL,
  tanggal_lahir date DEFAULT NULL,
  alamat text DEFAULT NULL,
  kota varchar(100) DEFAULT NULL,
  no_ktp varchar(20) DEFAULT NULL,
  no_hp varchar(20) DEFAULT NULL,
  agama varchar(30) DEFAULT NULL,
  status_pernikahan varchar(30) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS riwayat_pekerjaan (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  nama_perusahaan varchar(150) DEFAULT NULL,
  posisi varchar(100) DEFAULT NULL,
  tanggal_mulai date DEFAULT NULL,
  tanggal_selesai date DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS riwayat_pendidikan (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  pendidikan_terakhir varchar(50) DEFAULT NULL,
  jurusan varchar(100) DEFAULT NULL,
  kampus varchar(150) DEFAULT NULL,
  tgl_lulus year(4) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS riwayat_kesehatan (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  gol_darah varchar(5) DEFAULT NULL,
  status_vaksinasi varchar(50) DEFAULT NULL,
  no_bpjs_kesehatan varchar(30) DEFAULT NULL,
  no_bpjs_kerja varchar(30) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dokumen_pendukung (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  file_ktp varchar(255) DEFAULT NULL,
  file_ijazah varchar(255) DEFAULT NULL,
  file_foto varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Search & Pagination
$search  = trim($_GET['search'] ?? '');
$limit   = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $limit;

$where = '';
$params = [];
$types  = '';
if ($search !== '') {
    $where = "WHERE (u.nama_lengkap LIKE ? OR u.nik LIKE ? OR u.jabatan LIKE ? OR u.unit_kerja LIKE ? OR u.email LIKE ?)";
    $s = "%$search%";
    $params = [$s,$s,$s,$s,$s];
    $types  = 'sssss';
}

// Count total
$cntSql = "SELECT COUNT(*) AS total FROM users u $where";
$cntStmt = $conn->prepare($cntSql);
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$totalData  = $cntStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalData / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// Fetch users
$sql = "SELECT u.id, u.nik, u.nama_lengkap AS nama, u.jabatan, u.unit_kerja, u.email, u.status,
               ip.jenis_kelamin, ip.tempat_lahir, ip.tanggal_lahir, ip.alamat, ip.kota, ip.no_ktp, ip.no_hp
        FROM users u
        LEFT JOIN informasi_pribadi ip ON ip.user_id = u.id
        $where
        ORDER BY u.nama_lengkap ASC
        LIMIT ? OFFSET ?";
$usrStmt = $conn->prepare($sql);
if (!$usrStmt) {
    // Jika prepare gagal (misal tabel tidak ada), fallback query sederhana
    $fbSql = "SELECT id, nik, nama_lengkap AS nama, jabatan, unit_kerja, email, status,
               NULL AS jenis_kelamin, NULL AS tempat_lahir, NULL AS tanggal_lahir,
               NULL AS alamat, NULL AS kota, NULL AS no_ktp, NULL AS no_hp
               FROM users ORDER BY nama_lengkap ASC LIMIT $limit OFFSET $offset";
    $users = [];
    $fbRes = mysqli_query($conn, $fbSql);
    if ($fbRes) while ($row = mysqli_fetch_assoc($fbRes)) $users[] = $row;
} else {
    if ($types) {
        $allTypes = $types . 'ii';
        $allParams = array_merge($params, [$limit, $offset]);
        $usrStmt->bind_param($allTypes, ...$allParams);
    } else {
        $usrStmt->bind_param("ii", $limit, $offset);
    }
    $usrStmt->execute();
    $users = $usrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan | BexMedia</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3B82F6; }

        .dk-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            padding: 28px 32px;
        }

        /* Search bar */
        .search-wrap { display:flex; align-items:center; gap:12px; flex-wrap:nowrap; }
        .search-input-wrap {
            position: relative;
            flex: 1 1 0;
            min-width: 0;
        }
        .search-input-wrap input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 16px 10px 42px;
            border: 1.5px solid #E2E8F0;
            border-radius: 12px;
            font-size: 0.9rem;
            background: #F8FAFC;
            outline: none;
            transition: all 0.2s;
            font-family: 'Outfit', sans-serif;
            color: #1E293B;
            display: block;
        }
        .search-input-wrap input:focus { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .search-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94A3B8; pointer-events: none;
        }

        /* Table */
        .dk-table-wrap { overflow-x: auto; border-radius: 12px; margin-top: 20px; }
        .dk-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
        .dk-table thead th {
            background: #F8FAFC;
            color: #64748B;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 14px;
            border-bottom: 2px solid #E2E8F0;
            white-space: nowrap;
            text-align: left;
        }
        .dk-table thead th.center { text-align: center; }
        .dk-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
            color: #1E293B;
            vertical-align: middle;
            white-space: nowrap;
        }
        .dk-table tbody tr:hover td { background: #F8FAFC; }
        .dk-table tbody tr:last-child td { border-bottom: none; }
        .dk-table td.center { text-align: center; }

        /* Badges */
        .badge-aktif  { background:#D1FAE5; color:#065F46; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-nonaktif { background:#FEE2E2; color:#991B1B; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-pending  { background:#FEF3C7; color:#92400E; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-unit   { background:#EFF6FF; color:#1D4ED8; padding:3px 9px; border-radius:6px; font-size:0.72rem; font-weight:600; }

        /* Action buttons */
        .act-btn {
            width: 32px; height: 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }
        .act-edit  { background:#EFF6FF; color:#0284c7; }
        .act-edit:hover  { background:#0284c7; color:white; transform:scale(1.1); }
        .act-print { background:#F0FDF4; color:#16A34A; }
        .act-print:hover { background:#16A34A; color:white; }

        /* Pagination */
        .pagination-wrap { display:flex; align-items:center; justify-content:space-between; margin-top:20px; flex-wrap:wrap; gap:12px; }
        .pagination-info { font-size:0.83rem; color:#64748B; }
        .pages { display:flex; gap:6px; }
        .page-btn {
            width:34px; height:34px;
            border-radius:8px;
            border: 1.5px solid #E2E8F0;
            background: white;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        .page-btn:hover { border-color:var(--primary); color:var(--primary); background:#EFF6FF; }
        .page-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
        .page-btn.disabled { opacity:0.4; pointer-events:none; }

        /* Empty state */
        .empty-state { text-align:center; padding:60px 20px; color:#94A3B8; }

        /* Buttons */
        .btn-prim {
            background:var(--primary); color:white; border:none;
            padding:10px 20px; border-radius:12px; font-weight:700; font-size:0.88rem;
            cursor:pointer; display:inline-flex; align-items:center; gap:8px;
            font-family:'Outfit',sans-serif; transition:all 0.2s;
            text-decoration:none;
        }
        .btn-prim:hover { background:#2563EB; transform:translateY(-1px); }
        .btn-ghost  {
            background:#F1F5F9; color:#475569; border:none;
            padding:10px 18px; border-radius:12px; font-weight:600; font-size:0.88rem;
            cursor:pointer; display:inline-flex; align-items:center; gap:8px;
            font-family:'Outfit',sans-serif; transition:all 0.2s; text-decoration:none;
        }
        .btn-ghost:hover { background:#E2E8F0; }

        /* Detail Modal */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(15,23,42,0.5); backdrop-filter:blur(6px);
            z-index:9999; align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box {
            background:white; border-radius:24px;
            box-shadow:0 32px 80px rgba(0,0,0,0.2);
            padding:32px; width:90%; max-width:720px;
            max-height:88vh; overflow-y:auto;
            animation:modalIn 0.25s ease;
        }
        @keyframes modalIn { from{transform:scale(0.92);opacity:0} to{transform:scale(1);opacity:1} }
        .modal-header-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
        .modal-title { font-family:'Outfit',sans-serif; font-weight:800; font-size:1.15rem; color:#1E293B; }
        .modal-close {
            width:36px; height:36px; border-radius:10px; border:1.5px solid #E2E8F0;
            background:white; color:#64748B; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
        }
        .modal-close:hover { background:#F1F5F9; }

        .info-section { margin-bottom:24px; }
        .info-section h4 { font-family:'Outfit',sans-serif; font-weight:700; font-size:0.85rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; }
        .info-row { display:flex; flex-direction:column; gap:2px; }
        .info-label { font-size:0.72rem; color:#94A3B8; font-weight:600; text-transform:uppercase; }
        .info-val { font-size:0.88rem; color:#1E293B; font-weight:500; }

        /* Flash */
        .flash-toast {
            position:fixed; top:20px; right:20px; z-index:99999;
            background:#10B981; color:white; padding:14px 22px;
            border-radius:14px; font-weight:600; font-size:0.9rem;
            display:flex; align-items:center; gap:10px;
            box-shadow:0 8px 30px rgba(16,185,129,0.3);
            animation:slideInR 0.3s ease;
        }
        @keyframes slideInR { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }

        @media print {
            .no-print { display:none!important; }
        }
    </style>
</head>
<body>
<div style="display:flex;height:100vh;overflow:hidden;">
    <?php include 'sidebar.php'; ?>
    <main style="flex:1;display:flex;flex-direction:column;min-width:0;height:100%;overflow:hidden;">
        <?php 
        $breadcrumb = "Employee Hub / <strong>Database Karyawan</strong>";
        include "topbar.php"; 
        ?>


        <div style="padding:20px 32px;overflow-y:auto;flex:1;background:rgba(255,255,255,0.3);backdrop-filter:blur(20px);">

            <?php if($flash): ?>
            <div class="flash-toast no-print" id="flashToast"><i data-lucide="check-circle" size="18"></i><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>

            <!-- Header -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.6rem;color:#1E293B;margin:0 0 2px;">Data Karyawan</h1>
                    <p style="color:#64748B;font-size:0.85rem;margin:0;">Total <strong><?= $totalData ?></strong> karyawan terdaftar</p>
                </div>
                <div class="no-print" style="display:flex;gap:10px;">
                    <a href="tambah_karyawan.php" class="btn-prim">
                        <i data-lucide="user-plus" size="16"></i> Tambah Karyawan
                    </a>
                </div>
            </div>

            <div class="dk-card">
                <!-- Search & Filter -->
                <form method="GET" class="search-wrap no-print">
                    <div class="search-input-wrap">
                        <i data-lucide="search" size="16" class="search-icon"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NIK, jabatan, unit kerja...">
                    </div>
                    <button type="submit" class="btn-prim"><i data-lucide="search" size="16"></i> Cari</button>
                    <?php if($search): ?>
                    <a href="data_karyawan.php" class="btn-ghost"><i data-lucide="x" size="16"></i> Reset</a>
                    <?php endif; ?>
                </form>

                <!-- Table -->
                <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i data-lucide="users" size="48" style="opacity:0.2;display:block;margin:0 auto 12px;"></i>
                    <p style="font-weight:600;margin:0 0 4px;"><?= $search ? "Tidak ada hasil untuk \"$search\"" : "Belum ada data karyawan" ?></p>
                    <p style="font-size:0.85rem;margin:0;">Tambahkan karyawan baru untuk memulai.</p>
                </div>
                <?php else: ?>
                <div class="dk-table-wrap">
                    <table class="dk-table">
                        <thead>
                            <tr>
                                <th class="center" style="width:44px;">No</th>
                                <th class="center no-print" style="width:80px;">Aksi</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Unit Kerja</th>
                                <th>Email</th>
                                <th>No HP</th>
                                <th>Jenis Kel.</th>
                                <th>Tgl. Lahir</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = $offset + 1; foreach ($users as $u): ?>
                        <tr>
                            <td class="center" style="color:#94A3B8;font-weight:600;"><?= $no++ ?></td>
                            <td class="center no-print">
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <button class="act-btn act-edit" onclick="openEdit(<?= $u['id'] ?>)" title="Edit Data Karyawan">
                                        <i data-lucide="pencil" size="14"></i>
                                    </button>
                                    <a href="cetak_karyawan.php?id=<?= $u['id'] ?>" target="_blank" class="act-btn act-print" title="Cetak">
                                        <i data-lucide="printer" size="14"></i>
                                    </a>
                                </div>
                            </td>
                            <td style="color:#64748B;font-family:monospace;"><?= htmlspecialchars($u['nik'] ?? '-') ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <strong><?= htmlspecialchars($u['nama'] ?? '-') ?></strong>
                                    <?php if (!empty($u['khanza_id'])): ?>
                                    <span title="Data dari SIMRS Khanza" style="background:#F0FDF4;color:#166534;padding:1px 6px;border-radius:5px;font-size:0.62rem;font-weight:700;border:1px solid #BBF7D0;flex-shrink:0;">SIK</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="color:#475569;"><?= htmlspecialchars(!empty($u['jabatan']) ? $u['jabatan'] : '-') ?></td>
                            <td>
                                <?php $uk = trim($u['unit_kerja'] ?? ''); ?>
                                <?php if ($uk && $uk !== '-'): ?>
                                <span class="badge-unit"><?= htmlspecialchars($uk) ?></span>
                                <?php else: ?><span style="color:#CBD5E1;">-</span><?php endif; ?>
                            </td>
                            <td style="color:#64748B;"><?= htmlspecialchars(!empty($u['email']) ? $u['email'] : '-') ?></td>
                            <td style="color:#64748B;"><?= htmlspecialchars(!empty($u['no_hp']) ? $u['no_hp'] : '-') ?></td>
                            <td style="color:#64748B;">
                                <?php $jk = $u['jenis_kelamin'] ?? '';
                                echo $jk === 'L' ? 'Laki-laki' : ($jk === 'P' ? 'Perempuan' : '<span style="color:#CBD5E1;">-</span>'); ?>
                            </td>
                            <td style="color:#64748B;"><?= !empty($u['tanggal_lahir']) ? date('d/m/Y', strtotime($u['tanggal_lahir'])) : '<span style="color:#CBD5E1;">-</span>' ?></td>
                            <td>
                                <?php
                                $st = strtolower($u['status'] ?? '');
                                $badgeClass = 'badge-nonaktif'; $stLabel = 'Nonaktif';
                                if ($st === 'active')  { $badgeClass = 'badge-aktif';    $stLabel = 'Aktif'; }
                                elseif ($st === 'pending') { $badgeClass = 'badge-pending'; $stLabel = 'Pending'; }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= $stLabel ?></span>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-wrap no-print">
                    <div class="pagination-info">
                        Menampilkan <?= $offset+1 ?>–<?= min($offset+$limit, $totalData) ?> dari <?= $totalData ?> karyawan
                    </div>
                    <div class="pages">
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">
                            <i data-lucide="chevron-left" size="14"></i>
                        </a>
                        <?php
                        $s = max(1,$page-2); $e = min($totalPages,$page+2);
                        if($s>1) echo '<a href="?page=1&search='.urlencode($search).'" class="page-btn">1</a>';
                        if($s>2) echo '<span class="page-btn disabled">…</span>';
                        for($i=$s;$i<=$e;$i++) echo '<a href="?page='.$i.'&search='.urlencode($search).'" class="page-btn '.($i==$page?'active':'').'">'.$i.'</a>';
                        if($e<$totalPages-1) echo '<span class="page-btn disabled">…</span>';
                        if($e<$totalPages) echo '<a href="?page='.$totalPages.'&search='.urlencode($search).'" class="page-btn">'.$totalPages.'</a>';
                        ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">
                            <i data-lucide="chevron-right" size="14"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Edit Karyawan Modal -->
<div class="modal-overlay" id="editModalOverlay">
    <div class="modal-box" id="editModalBox" style="max-width:780px;">
        <div class="modal-header-row">
            <div class="modal-title" id="editModalTitle">✏️ Edit Data Karyawan</div>
            <button class="modal-close" onclick="closeEditModal()"><i data-lucide="x" size="16"></i></button>
        </div>
        <div id="editModalContent">
            <div style="text-align:center;padding:40px;color:#94A3B8;">
                <div style="width:32px;height:32px;border:3px solid #E2E8F0;border-top-color:#0284c7;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px;"></div>
                Memuat data...
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

/* 🔑 SweetAlert2 harus di atas semua modal */
.swal2-container { z-index: 999999 !important; }

.form-field-label { font-size:0.72rem; color:#64748B; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px; display:block; }
.form-field-input {
    width:100%; padding:9px 12px; border-radius:10px;
    border:1.5px solid #E2E8F0; font-size:0.875rem;
    font-family:'Outfit',sans-serif; color:#1E293B;
    background:#F8FAFC; outline:none; box-sizing:border-box;
    transition: all 0.2s;
}
.form-field-input:focus { border-color:#0284c7; background:white; box-shadow:0 0 0 3px rgba(2,132,199,0.1); }
select.form-field-input { cursor:pointer; }
.form-section-title {
    font-size:0.78rem; font-weight:800; color:#0284c7;
    text-transform:uppercase; letter-spacing:0.06em;
    margin:20px 0 12px; padding-bottom:6px;
    border-bottom: 2px solid #BAE6FD;
}
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 20px; }
.form-grid.cols-3 { grid-template-columns:1fr 1fr 1fr; }
.form-grid.cols-1 { grid-template-columns:1fr; }
.btn-save-edit {
    background:linear-gradient(135deg,#0ea5e9,#0284c7);
    color:white; border:none; padding:11px 28px;
    border-radius:12px; font-weight:700; font-size:0.88rem;
    cursor:pointer; display:inline-flex; align-items:center; gap:8px;
    font-family:'Outfit',sans-serif; transition:all 0.2s;
}
.btn-save-edit:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(2,132,199,0.3); }
.swal-ice-popup { border-radius:20px!important; border:1px solid rgba(186,230,253,0.5)!important; }
.swal2-title { color:#0c4a6e!important; font-weight:800!important; }
.swal2-confirm { border-radius:10px!important; font-weight:700!important; }
.swal2-timer-progress-bar { background:linear-gradient(to right,#0ea5e9,#0284c7)!important; }
</style>

<script>
lucide.createIcons();

var ft = document.getElementById('flashToast');
if(ft) setTimeout(()=>ft.style.opacity='0', 3500);

// === Edit Modal ===
function openEdit(uid) {
    document.getElementById('editModalOverlay').classList.add('open');
    document.getElementById('editModalContent').innerHTML = `<div style="text-align:center;padding:40px;color:#94A3B8;"><div style="width:32px;height:32px;border:3px solid #E2E8F0;border-top-color:#0284c7;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px;"></div>Memuat data karyawan...</div>`;
    
    fetch('ajax_get_karyawan.php?id='+uid)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text(); // text dulu, baru parse
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    document.getElementById('editModalContent').innerHTML = `<p style="color:#EF4444;text-align:center;padding:30px;"><b>Error:</b> ${data.msg || data.error}</p>`;
                    return;
                }
                renderEditModal(data);
            } catch(e) {
                // JSON parse gagal — tampilkan raw response untuk debug
                document.getElementById('editModalContent').innerHTML = `<pre style="color:#EF4444;font-size:0.75rem;padding:16px;overflow:auto;max-height:300px;">${text.substring(0,1000)}</pre>`;
            }
        })
        .catch(err => {
            document.getElementById('editModalContent').innerHTML = `<p style="color:#EF4444;text-align:center;padding:30px;">Gagal menghubungi server: ${err.message}</p>`;
        });
}

function renderEditModal(d) {
    const u = d.user || {};
    const ip = d.info_pribadi || {};
    const k = d.kesehatan || {};

    document.getElementById('editModalTitle').innerHTML = `✏️ Edit: <span style="color:#0284c7">${u.nama||'Karyawan'}</span>`;

    const html = `
    <form id="formEditKaryawan" onsubmit="submitEditKaryawan(event, ${u.id||0})">
        <div class="form-section-title">📋 Informasi Utama</div>
        <div class="form-grid">
            <div>
                <label class="form-field-label">NIK</label>
                <input type="text" name="nik" class="form-field-input" value="${escHtml(u.nik||'')}" placeholder="NIK Karyawan">
            </div>
            <div>
                <label class="form-field-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-field-input" value="${escHtml(u.nama||'')}" required placeholder="Nama Lengkap">
            </div>
            <div>
                <label class="form-field-label">Jabatan</label>
                <input type="text" name="jabatan" class="form-field-input" value="${escHtml(u.jabatan||'')}" placeholder="Jabatan">
            </div>
            <div>
                <label class="form-field-label">Unit Kerja</label>
                <input type="text" name="unit_kerja" class="form-field-input" value="${escHtml(u.unit_kerja||'')}" placeholder="Unit Kerja">
            </div>
            <div>
                <label class="form-field-label">Email</label>
                <input type="email" name="email" class="form-field-input" value="${escHtml(u.email||'')}" placeholder="Kosongkan jika tidak ingin mengubah email">
                ${!u.email ? '<small style="color:#94A3B8;font-size:0.72rem;">ℹ️ Belum ada email — isi untuk menambahkan</small>' : ''}
            </div>
            <div>
                <label class="form-field-label">No. HP</label>
                <input type="text" name="no_hp" class="form-field-input" value="${escHtml(u.no_hp||ip.no_hp||'')}" placeholder="0812xxxxxxxx">
            </div>
            <div>
                <label class="form-field-label">Status Akun</label>
                <select name="status" class="form-field-input">
                    <option value="active" ${u.status==='active'?'selected':''}>Aktif</option>
                    <option value="pending" ${u.status==='pending'?'selected':''}>Pending</option>
                    <option value="blocked" ${u.status==='blocked'?'selected':''}>Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="form-section-title">👤 Data Pribadi</div>
        <div class="form-grid cols-3">
            <div>
                <label class="form-field-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-field-input">
                    <option value="">-- Pilih --</option>
                    <option value="L" ${ip.jenis_kelamin==='L'?'selected':''}>Laki-laki</option>
                    <option value="P" ${ip.jenis_kelamin==='P'?'selected':''}>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="form-field-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-field-input" value="${escHtml(ip.tempat_lahir||'')}" placeholder="Kota lahir">
            </div>
            <div>
                <label class="form-field-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-field-input" value="${escHtml(ip.tanggal_lahir||'')}">
            </div>
            <div>
                <label class="form-field-label">No. KTP</label>
                <input type="text" name="no_ktp" class="form-field-input" value="${escHtml(ip.no_ktp||'')}" placeholder="16 digit NIK KTP">
            </div>
            <div>
                <label class="form-field-label">Agama</label>
                <select name="agama" class="form-field-input">
                    <option value="">-- Pilih --</option>
                    ${['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'].map(a=>`<option value="${a}" ${ip.agama===a?'selected':''}>${a}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="form-field-label">Status Nikah</label>
                <select name="status_pernikahan" class="form-field-input">
                    <option value="">-- Pilih --</option>
                    ${['Belum Menikah','Menikah','Cerai'].map(s=>`<option value="${s}" ${ip.status_pernikahan===s?'selected':''}>${s}</option>`).join('')}
                </select>
            </div>
        </div>
        <div class="form-grid cols-1" style="margin-top:8px;">
            <div>
                <label class="form-field-label">Alamat</label>
                <textarea name="alamat" class="form-field-input" rows="2" placeholder="Alamat lengkap...">${escHtml(ip.alamat||'')}</textarea>
            </div>
        </div>

        <div class="form-section-title">🏥 Data Kesehatan</div>
        <div class="form-grid">
            <div>
                <label class="form-field-label">Golongan Darah</label>
                <select name="gol_darah" class="form-field-input">
                    <option value="">-- Pilih --</option>
                    ${['A','B','AB','O'].map(g=>`<option value="${g}" ${k.gol_darah===g?'selected':''}>${g}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="form-field-label">Status Vaksinasi</label>
                <select name="status_vaksinasi" class="form-field-input">
                    <option value="">-- Pilih --</option>
                    ${['Belum','Vaksin 1','Vaksin 2','Booster'].map(v=>`<option value="${v}" ${k.status_vaksinasi===v?'selected':''}>${v}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="form-field-label">No. BPJS Kesehatan</label>
                <input type="text" name="no_bpjs_kesehatan" class="form-field-input" value="${escHtml(k.no_bpjs_kesehatan||'')}" placeholder="No. BPJS">
            </div>
            <div>
                <label class="form-field-label">No. BPJS Ketenagakerjaan</label>
                <input type="text" name="no_bpjs_kerja" class="form-field-input" value="${escHtml(k.no_bpjs_kerja||'')}" placeholder="No. BPJS TK">
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
            <button type="button" onclick="closeEditModal()" style="padding:11px 22px;border-radius:12px;border:1.5px solid #E2E8F0;background:white;color:#64748B;font-weight:600;font-size:0.88rem;cursor:pointer;font-family:'Outfit',sans-serif;">Batal</button>
            <button type="submit" class="btn-save-edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>`;

    document.getElementById('editModalContent').innerHTML = html;
    lucide.createIcons();
}

function submitEditKaryawan(e, uid) {
    e.preventDefault();
    const form = document.getElementById('formEditKaryawan');
    const data = new FormData(form);
    data.append('id', uid);

    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '⟳ Menyimpan...';

    fetch('update_karyawan.php', { method:'POST', body:data })
        .then(r => r.text())
        .then(text => {
            btn.disabled = false;
            let res;
            try {
                res = JSON.parse(text);
            } catch(e) {
                // Server returned non-JSON — tampilkan raw output
                Swal.fire({
                    icon:'error', title:'Server Error',
                    html: `<pre style="text-align:left;font-size:0.75rem;max-height:200px;overflow:auto;">${text.substring(0,500)}</pre>`,
                    confirmButtonColor:'#0284c7'
                });
                btn.innerHTML = 'Simpan Perubahan';
                return;
            }

            btn.innerHTML = res.success ? '✓ Tersimpan!' : 'Simpan Perubahan';

            if (res.success) {
                closeEditModal();
                Swal.fire({
                    icon:'success', title:'Berhasil! ✅',
                    text: res.message || 'Data karyawan berhasil diperbarui.',
                    confirmButtonColor:'#0284c7',
                    customClass:{popup:'swal-ice-popup'},
                    timer:3000, timerProgressBar:true
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon:'error', title:'Gagal Menyimpan',
                    text: res.message || 'Terjadi kesalahan saat menyimpan data.',
                    confirmButtonColor:'#0284c7',
                    customClass:{popup:'swal-ice-popup'}
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = 'Simpan Perubahan';
            Swal.fire({icon:'error', title:'Koneksi Bermasalah', text:'Gagal menghubungi server: ' + err.message, confirmButtonColor:'#0284c7'});
        });
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('open');
}

document.getElementById('editModalOverlay').addEventListener('click', e => {
    if(e.target === document.getElementById('editModalOverlay')) closeEditModal();
});

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => { if(e.key==='Escape') closeEditModal(); });
</script>
</body>
</html>
