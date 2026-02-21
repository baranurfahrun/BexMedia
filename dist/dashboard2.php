<?php
require_once "../conf/config.php";
checkLogin();

// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";

// 1. Verifikasi Hak Cipta
$sig_copyright  = "QDIwMjYgYmFyYS5uLmZhaHJ1bi0wODUxMTc0NzYwMDE=";
$hash_copyright = "3e07d2217d54524233697deb8b497061";
if (md5($sig_copyright . $private_key) !== $hash_copyright) die("Security Breach: Copyright modified!");
$copyright_text = base64_decode($sig_copyright);

// 2. Verifikasi Nama Brand
$sig_brand  = "QmV4TWVkaWE=";
$hash_brand = "1d45b0cc7a28442c082bd43bd312ac88";
if (md5($sig_brand . $private_key) !== $hash_brand) die("Security Breach: Brand name modified!");
$brand_name = base64_decode($sig_brand);

// 3. Verifikasi Path & Konten Logo (Double Layer Security)
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach: Logo path modified!");
$logo_path = "../" . base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    die("Security Breach: Logo file content compromised or missing! Hubungi hak cipta: bara.n.fahrun (085117476001)");
}

// MenuName: Executive Dashboard
$user_id = $_SESSION['username'] ?? 'admin';
$nama_user = $_SESSION['nama_lengkap'] ?? 'Executive';
$tahun = date('Y');

// Helper for strings
if (!function_exists('truncate')) {
    function truncate($string, $length = 50, $append = "...") {
        $string = trim($string ?? '');
        if (strlen($string) > $length) {
            $string = substr($string, 0, $length);
            $string .= $append;
        }
        return $string;
    }
}

// Function to get Month Name (Indonesian)
function namaBulan($angka) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return isset($bulan[(int)$angka]) ? $bulan[(int)$angka] : '-';
}

// === DATA FETCHING ===

// 1. HRD & SDM
$res_cuti = safe_query("SELECT mc.nama_cuti, jc.lama_hari, jc.sisa_hari, (jc.lama_hari - jc.sisa_hari) AS terpakai 
                        FROM jatah_cuti jc JOIN master_cuti mc ON jc.cuti_id = mc.id 
                        WHERE jc.tahun = ?", [$tahun]);
$data_izin = safe_query("SELECT * FROM izin_keluar WHERE tanggal = CURDATE() ORDER BY created_at DESC");

// 2. IT Support
$data_hw = safe_query("SELECT * FROM tiket_it_hardware ORDER BY tanggal_input DESC LIMIT 50");
$data_sw = safe_query("SELECT * FROM tiket_it_software ORDER BY tanggal_input DESC LIMIT 50");
$data_barang_it = safe_query("SELECT * FROM data_barang_it ORDER BY created_at DESC LIMIT 50");
$data_maintenance = safe_query("SELECT mr.*, b.nama_barang, b.no_barang FROM maintenance_rutin mr LEFT JOIN data_barang_it b ON mr.barang_id = b.id ORDER BY mr.waktu_input DESC LIMIT 50");

// 3. Log Book & Users
$data_lb = safe_query("SELECT ck.*, u.nama_lengkap FROM catatan_kerja ck JOIN users u ON ck.user_id = u.id ORDER BY ck.tanggal DESC LIMIT 50");
$data_users = safe_query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50");

// 4. Sekretariat & Korespondensi
$data_sm = safe_query("SELECT * FROM surat_masuk ORDER BY tgl_terima DESC LIMIT 50");
$data_sk = safe_query("SELECT * FROM surat_keluar ORDER BY tgl_surat DESC LIMIT 50");
$data_arsip = safe_query("SELECT * FROM arsip_digital ORDER BY tanggal_input DESC LIMIT 50");
$data_agenda = safe_query("SELECT * FROM agenda_direktur ORDER BY tanggal DESC, jam DESC LIMIT 50");

// 5. Sarpras & Mutu
$data_sarpras = safe_query("SELECT * FROM tiket_sarpras ORDER BY tanggal_input DESC LIMIT 50");
$data_antrian = safe_query("SELECT * FROM semua_antrian ORDER BY tahun ASC, bulan ASC");
$data_dokumen = safe_query("SELECT * FROM dokumen ORDER BY waktu_input DESC LIMIT 50");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Commander | BexMedia</title>
    <link rel="stylesheet" href="../css/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .exec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; padding-bottom: 40px; }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover { transform: translateY(-4px); background: white; border-color: var(--ice-3); box-shadow: var(--card-shadow); }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; color: white; flex-shrink: 0; }
        .stat-info .label { display: block; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; }
        .stat-info .value { display: block; font-size: 1rem; font-weight: 800; color: var(--ice-1); line-height: 1.1; }

        /* Modal Ultra Luxury */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 25, 46, 0.8); backdrop-filter: blur(12px); z-index: 9999; justify-content: center; align-items: center; padding: 40px; }
        .modal-container { background: white; width: 100%; max-width: 1400px; max-height: 90vh; border-radius: 32px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 40px 80px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); }
        .modal-header { padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .modal-header h2 { font-size: 1.25rem; font-weight: 900; color: var(--ice-1); display: flex; align-items: center; gap: 12px; }
        .modal-body { flex: 1; overflow-y: auto; padding: 32px; }
        .modal-close { cursor: pointer; color: var(--text-muted); transition: 0.3s; }
        .modal-close:hover { color: #ef4444; transform: rotate(90deg); }

        /* Table Premium Styles */
        .premium-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .premium-table th { padding: 14px; text-align: left; background: #f8fafc; color: var(--text-muted); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; border-bottom: 2px solid #edf2f7; position: sticky; top: 0; }
        .premium-table td { padding: 14px; border-bottom: 1px solid #edf2f7; color: var(--text-main); line-height: 1.4; vertical-align: top; }
        .premium-table tr:hover td { background: #f1f5f9; }
        
        .badge-pill { padding: 4px 10px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; }
        .bp-success { background: #d1fae5; color: #065f46; }
        .bp-warning { background: #fef3c7; color: #92400e; }
        .bp-danger { background: #fee2e2; color: #991b1b; }
        .bp-info { background: #dbeafe; color: #1e40af; }
        .bp-dark { background: #1e293b; color: white; }

        .search-input { width: 100%; padding: 12px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 0.9rem; font-family: inherit; transition: 0.3s; }
        .search-input:focus { outline: none; border-color: var(--ice-3); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include "sidebar.php"; ?>
        
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 style="font-size:1.75rem; font-weight:900; letter-spacing:-0.04em;">Executive Dashboard Direktur</h1>
                    <p style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">Pusat Komando & Kontrol Lintas Departemen.</p>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <span style="font-size: 0.85rem; font-weight: 600"><?= h($nama_user) ?></span>
                        <div class="avatar" style="background-image: url('https://ui-avatars.com/api/?name=<?=urlencode($nama_user)?>&background=3B82F6&color=fff');"></div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="exec-grid">
                    <!-- Row 1: HRD & IT -->
                    <div class="stat-card" onclick="openModal('modalHRD')">
                        <div class="stat-icon" style="background:#3b82f6;"><i data-lucide="users-2"></i></div>
                        <div class="stat-info"><span class="label">HRD / SDM</span><span class="value">Info Jatah Cuti</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalHW')">
                        <div class="stat-icon" style="background:#f59e0b;"><i data-lucide="monitor"></i></div>
                        <div class="stat-info"><span class="label">IT Support</span><span class="value">Tiket Hardware</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalSW')">
                        <div class="stat-icon" style="background:#3b82f6;"><i data-lucide="code-2"></i></div>
                        <div class="stat-info"><span class="label">IT Support</span><span class="value">Tiket Software</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalLB')">
                        <div class="stat-icon" style="background:#10b981;"><i data-lucide="book-open"></i></div>
                        <div class="stat-info"><span class="label">Performance</span><span class="value">Log Book Kerja</span></div>
                    </div>

                    <!-- Row 2: Doc & Secretarial -->
                    <div class="stat-card" onclick="openModal('modalDoc')">
                        <div class="stat-icon" style="background:#475569;"><i data-lucide="file-check-2"></i></div>
                        <div class="stat-info"><span class="label">Akreditasi</span><span class="value">Data Dokumen</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalUsers')">
                        <div class="stat-icon" style="background:#64748b;"><i data-lucide="user-square"></i></div>
                        <div class="stat-info"><span class="label">Identity</span><span class="value">Data Pengguna</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalSM')">
                        <div class="stat-icon" style="background:#0ea5e9;"><i data-lucide="mail"></i></div>
                        <div class="stat-info"><span class="label">Sekretariat</span><span class="value">Surat Masuk</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalSK')">
                        <div class="stat-icon" style="background:#ef4444;"><i data-lucide="send"></i></div>
                        <div class="stat-info"><span class="label">Sekretariat</span><span class="value">Surat Keluar</span></div>
                    </div>

                    <!-- Row 3: Agenda & Assets -->
                    <div class="stat-card" onclick="openModal('modalAgenda')">
                        <div class="stat-icon" style="background:#8b5cf6;"><i data-lucide="calendar"></i></div>
                        <div class="stat-info"><span class="label">Engagement</span><span class="value">Agenda Direktur</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalBarang')">
                        <div class="stat-icon" style="background:#f97316;"><i data-lucide="hard-drive"></i></div>
                        <div class="stat-info"><span class="label">Operational</span><span class="value">Data Barang IT</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalMaintenance')">
                        <div class="stat-icon" style="background:#3b82f6;"><i data-lucide="wrench"></i></div>
                        <div class="stat-info"><span class="label">Maintenance</span><span class="value">Laporan Rutin</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalArsip')">
                        <div class="stat-icon" style="background:#10b981;"><i data-lucide="folder-key"></i></div>
                        <div class="stat-info"><span class="label">Digital Vault</span><span class="value">Arsip Digital</span></div>
                    </div>

                    <!-- Row 4: Sarpras & Bridge -->
                    <div class="stat-card" onclick="openModal('modalSarpras')">
                        <div class="stat-icon" style="background:#6366f1;"><i data-lucide="wind"></i></div>
                        <div class="stat-info"><span class="label">Sarpras</span><span class="value">Tiket & AC</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalAntrian')">
                        <div class="stat-icon" style="background:#3b82f6;"><i data-lucide="bar-chart-big"></i></div>
                        <div class="stat-info"><span class="label">Bridging</span><span class="value">% Semua Antrian</span></div>
                    </div>
                    <div class="stat-card" onclick="openModal('modalIzin')">
                        <div class="stat-icon" style="background:#ec4899;"><i data-lucide="log-out"></i></div>
                        <div class="stat-info"><span class="label">Absensi</span><span class="value">Izin Keluar Pegawai</span></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- === MODALS LIST (FIXPOINT 1:1) === -->
    
    <!-- Modal HRD (Cuti) -->
    <div id="modalHRD" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="users-2"></i> Informasi Jatah Cuti <?= $tahun ?></h2>
                <div class="modal-close" onclick="closeModal('modalHRD')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>Jenis Cuti</th><th>Jatah</th><th>Terpakai</th><th>Sisa</th></tr></thead>
                    <tbody>
                        <?php while($c = mysqli_fetch_assoc($res_cuti)): ?>
                        <tr><td><?=h($c['nama_cuti'])?></td><td><?=$c['lama_hari']?> Hari</td><td><?=$c['terpakai']?> Hari</td><td style="font-weight:700; color:#3b82f6;"><?=$c['sisa_hari']?> Hari</td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal IT Hardware -->
    <div id="modalHW" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="monitor"></i> Tiket IT Hardware - Monitoring Support</h2>
                <div class="modal-close" onclick="closeModal('modalHW')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>No Tiket</th><th>Kategori</th><th>Laporan Kendala</th><th>Waktu Order</th><th>Status</th><th>Teknisi</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_hw)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=h($row['nomor_tiket'])?></td>
                            <td><?=h($row['kategori'])?></td>
                            <td><?=h($row['kendala'])?></td>
                            <td><?=date('d/m/Y H:i', strtotime($row['tanggal_input']))?></td>
                            <td><span class="badge-pill bp-<?=strpos($row['status'], 'Selesai')!==false?'success':'warning'?>"><?=h($row['status'])?></span></td>
                            <td><?=h($row['teknisi_nama'] ?? '-')?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal IT Software -->
    <div id="modalSW" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="code-2"></i> Tiket IT Software - Monitoring Support</h2>
                <div class="modal-close" onclick="closeModal('modalSW')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>No Tiket</th><th>Kategori</th><th>Laporan Kendala</th><th>Waktu Order</th><th>Status</th><th>Teknisi</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_sw)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=h($row['nomor_tiket'])?></td>
                            <td><?=h($row['kategori'])?></td>
                            <td><?=h($row['kendala'])?></td>
                            <td><?=date('d/m/Y H:i', strtotime($row['tanggal_input']))?></td>
                            <td><span class="badge-pill bp-<?=strpos($row['status'], 'Selesai')!==false?'success':'warning'?>"><?=h($row['status'])?></span></td>
                            <td><?=h($row['teknisi_nama'] ?? '-')?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Log Book -->
    <div id="modalLB" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="book-open"></i> Log Book / Catatan Kerja Karyawan</h2>
                <div class="modal-close" onclick="closeModal('modalLB')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <input type="text" id="cariLB" class="search-input" placeholder="Cari berdasarkan nama atau judul kegiatan...">
                <table class="premium-table" id="tableLB">
                    <thead><tr><th>Waktu</th><th>Nama Karyawan</th><th>Judul</th><th>Isi Catatan</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_lb)): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?=date('d/m/Y H:i', strtotime($row['tanggal']))?></td>
                            <td><strong><?=h($row['nama_lengkap'])?></strong></td>
                            <td style="font-weight:700;"><?=h($row['judul'])?></td>
                            <td><?=nl2br(h($row['isi']))?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Dokumen Akreditasi -->
    <div id="modalDoc" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="file-check-2"></i> Data Dokumen Persiapan Akreditasi</h2>
                <div class="modal-close" onclick="closeModal('modalDoc')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>Judul Dokumen</th><th>Elemen Penilaian</th><th>Petugas</th><th>File</th></tr></thead>
                    <tbody>
                        <?php while($d = mysqli_fetch_assoc($data_dokumen)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=h($d['judul'])?></td>
                            <td><?=h($d['elemen_penilaian'] ?? '-')?></td>
                            <td><?=h($d['petugas'] ?? '-')?></td>
                            <td><a href="<?=h($d['file_path'])?>" target="_blank" class="badge-pill bp-info">Lihat Dokumen</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Data Pengguna -->
    <div id="modalUsers" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="user-square"></i> Data Pengguna Sistem (Users)</h2>
                <div class="modal-close" onclick="closeModal('modalUsers')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>NIK</th><th>Nama</th><th>Jabatan</th><th>Unit Kerja</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php while($u = mysqli_fetch_assoc($data_users)): ?>
                        <tr>
                            <td><?=h($u['nik'])?></td>
                            <td style="font-weight:700;"><?=h($u['nama_lengkap'])?></td>
                            <td><?=h($u['jabatan'])?></td>
                            <td><?=h($u['unit_kerja'])?></td>
                            <td><span class="badge-pill bp-<?=strpos($u['status'], 'active')!==false?'success':'danger'?>"><?=h($u['status'])?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Sarpras (AC) -->
    <div id="modalSarpras" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="wind"></i> Data Tiket Sarpras (Monitoring AC)</h2>
                <div class="modal-close" onclick="closeModal('modalSarpras')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>No Tiket</th><th>Tgl Order</th><th>Kategori</th><th>Kendala</th><th>Status</th><th>Teknisi</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_sarpras)): ?>
                        <tr>
                            <td><?=h($row['nomor_tiket'])?></td>
                            <td><?=date('d/m/Y H:i', strtotime($row['tanggal_input']))?></td>
                            <td><?=h($row['kategori'])?></td>
                            <td><?=h($row['kendala'])?></td>
                            <td><span class="badge-pill bp-info"><?=h($row['status'])?></span></td>
                            <td><?=h($row['teknisi_nama'] ?? '-')?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Antrian BPJS -->
    <div id="modalAntrian" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="bar-chart-big"></i> Data Persentase Pemanfaatan Antrian</h2>
                <div class="modal-close" onclick="closeModal('modalAntrian')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>Bulan</th><th>Tahun</th><th>SEP</th><th>Antrian</th><th>MJKN</th><th>% Pemanfaatan</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_antrian)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=namaBulan($row['bulan'])?></td>
                            <td><?=$row['tahun']?></td>
                            <td><?=number_format($row['jumlah_sep'])?></td>
                            <td><?=number_format($row['jumlah_antri'])?></td>
                            <td><?=number_format($row['jumlah_mjkn'])?></td>
                            <td style="font-weight:900; color:#10b981;"><?=number_format($row['persen_all'], 2)?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Izin Keluar -->
    <div id="modalIzin" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="log-out"></i> Data Izin Keluar Pegawai (Hari Ini)</h2>
                <div class="modal-close" onclick="closeModal('modalIzin')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>Nama</th><th>Bagian</th><th>Waktu Keluar</th><th>Waktu Kembali</th><th>Keperluan</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_izin)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=h($row['nama'])?></td>
                            <td><?=h($row['bagian'])?></td>
                            <td><?=h($row['jam_keluar'])?></td>
                            <td><?=h($row['jam_kembali_real'] ?? '-')?></td>
                            <td><?=h($row['keperluan'])?></td>
                            <td><span class="badge-pill bp-success"><?=ucfirst($row['status_sdm'])?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Surat Masuk -->
    <div id="modalSM" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="mail"></i> Arsip Surat Masuk</h2>
                <div class="modal-close" onclick="closeModal('modalSM')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>No Surat</th><th>Terima</th><th>Pengirim</th><th>Perihal</th><th>Disposisi</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_sm)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=h($row['no_surat'])?></td>
                            <td><?=date('d/m/Y', strtotime($row['tgl_terima']))?></td>
                            <td><?=h($row['pengirim'])?></td>
                            <td><?=h($row['perihal'])?></td>
                            <td><span class="badge-pill bp-dark"><?=h($row['disposisi_ke'] ?? '-')?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agenda Direktur -->
    <div id="modalAgenda" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i data-lucide="calendar"></i> Agenda Kegiatan Direktur</h2>
                <div class="modal-close" onclick="closeModal('modalAgenda')"><i data-lucide="x-circle"></i></div>
            </div>
            <div class="modal-body">
                <table class="premium-table">
                    <thead><tr><th>Tanggal</th><th>Jam</th><th>Judul Kegiatan</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_agenda)): ?>
                        <tr>
                            <td style="font-weight:700;"><?=date('d/m/Y', strtotime($row['tanggal']))?></td>
                            <td><?=date('H:i', strtotime($row['jam']))?></td>
                            <td><?=h($row['judul'])?></td>
                            <td><?=nl2br(h($row['keterangan']))?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openModal(id) {
            $(`#${id}`).css('display', 'flex').hide().fadeIn(300);
            $('body').css('overflow', 'hidden');
        }

        function closeModal(id) {
            $(`#${id}`).fadeOut(200, function() {
                $('body').css('overflow', 'auto');
            });
        }

        $('.modal-overlay').click(function(e) {
            if (e.target === this) closeModal(this.id);
        });

        $('#cariLB').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            $('#tableLB tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });
    </script>
</body>
</html>
