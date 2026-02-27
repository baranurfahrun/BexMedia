<?php
// sidebar.php - Professional BexMedia Dynamic Structure
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

// === DYNAMIC ACCESS CONTROL (RBAC) ===
$allowed_files = [];
$query_akses = "SELECT menu.file_menu FROM akses_menu 
                JOIN menu ON akses_menu.menu_id = menu.id 
                WHERE akses_menu.user_id = '$user_id'";
$result_akses = mysqli_query($conn, $query_akses);
while ($row_akses = mysqli_fetch_assoc($result_akses)) {
    $allowed_files[] = $row_akses['file_menu'];
}

// === DYNAMIC LOGO PATH RESOLVER ===
if (!isset($logo_path) || empty($logo_path)) {
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $is_in_dist = (strpos($script_path, '/dist/') !== false);
    $logo_path = ($is_in_dist ? "../" : "") . "images/logo_final.png";
}

// Helper function to check if any file in an array is the current page
function is_group_active($files) {
    global $current_page;
    return in_array($current_page, $files);
}
?>
<!-- BexMedia Design System Core -->
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="../css/index.css">
<style>
    /* FixPoint to BexMedia Adapter (The "Swiss Bridge") */
    body {
        background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 50%, #BAE6FD 100%) !important;
        background-attachment: fixed !important;
        display: flex !important;
        flex-direction: column !important;
        height: 100vh !important;
        margin: 0 !important;
        overflow: hidden !important;
        padding: 16px 16px 85px 16px !important;
        gap: 12px !important;
    }

    #app, .main-wrapper, .main-wrapper-1 {
        display: flex !important;
        flex: 1 !important;
        gap: 24px !important;
        min-height: 0 !important;
        width: 100% !important;
        background: transparent !important;
    }

    .main-content {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        min-width: 0 !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    .section {
        padding: 40px 60px !important;
        background-color: rgba(255, 255, 255, 0.3) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        flex: 1 !important;
        overflow-y: auto !important;
        border-radius: 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05) !important;
    }

    .card {
        border-radius: 16px !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        box-shadow: 0 10px 30px rgba(0, 97, 255, 0.08) !important;
        background: white !important;
    }

    .card .card-header {
        border-bottom: 1px solid #f9f9f9 !important;
        background-color: transparent !important;
        padding: 20px 25px !important;
    }

    .card .card-header h4 {
        color: var(--ice-1) !important;
        font-weight: 700 !important;
        font-family: 'Outfit', sans-serif !important;
    }

    /* Hide Stisla Default Navbar and Sidebar since we use BexMedia's */
    .navbar, .main-sidebar, .navbar-bg, .section-header {
        display: none !important;
    }

    /* Ensure Lucide icons look good */
    .nav-item i, .nav-dropdown-trigger i {
        width: 18px;
        height: 18px;
        stroke-width: 2px;
    }

    /* Search Menu Styling */
    .sidebar-search {
        padding: 0 16px 12px 16px !important;
    }
    .sidebar-search input {
        width: 100%;
        background: rgba(255, 255, 255, 0.5) !important;
        border: 1px solid rgba(52, 152, 219, 0.2) !important;
        border-radius: 12px !important;
        padding: 10px 15px 10px 35px !important;
        font-size: 13px !important;
        color: #2c3e50 !important;
        transition: all 0.3s ease;
    }
    .sidebar-search input:focus {
        background: white !important;
        border-color: #3498db !important;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1) !important;
        outline: none;
    }
    .sidebar-search-wrapper {
        position: relative;
    }
    .sidebar-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        width: 16px !important;
        height: 16px !important;
    }

    /* Sidebar Footer Buttons */
    .sidebar-bottom {
        padding: 16px !important;
        border-top: 1px solid rgba(52, 152, 219, 0.1) !important;
        margin-top: auto !important;
    }
    .sidebar-footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 12px;
    }
    .btn-sidebar-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .btn-bio { background: #e0f2fe; color: #0369a1; }
    .btn-bio:hover { background: #bae6fd; transform: translateY(-2px); }
    .btn-tte-info { background: #f0fdf4; color: #15803d; }
    .btn-tte-info:hover { background: #dcfce7; transform: translateY(-2px); }
</style>
<aside>
    <div class="logo-section">
        <a href="index.php" style="display: block; text-decoration: none;">
            <img src="<?php echo $logo_path; ?>" alt="BexMedia Logo" style="height: 52px; width: auto; display: block; filter: none;">
        </a>
    </div>

    <nav class="nav-menu">
        <!-- 🔍 SEARCH MENU -->
        <div class="sidebar-search">
            <div class="sidebar-search-wrapper">
                <i data-lucide="search" class="sidebar-search-icon"></i>
                <input type="text" id="menuSearchInput" placeholder="Cari menu...">
            </div>
        </div>

        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i data-lucide="layout-grid"></i> Dashboard Utama
        </a>

        <!-- 1. STRATEGIC DATA & INSIGHTS -->
        <?php $insight_files = ['dashboard2.php', 'semua_antrian.php', 'mjkn_antrian.php', 'poli_antrian.php', 'erm.php', 'satu_sehat.php', 'progres_kerja.php', 'slide_pelaporan.php', 'antrian_pertanggal.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($insight_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="line-chart"></i> STRATEGIC DATA & INSIGHTS
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('dashboard2.php', $allowed_files)): ?><a href="dashboard2.php" class="dropdown-item">Dashboard Direktur</a><?php endif; ?>
                <?php if (in_array('semua_antrian.php', $allowed_files)): ?><a href="semua_antrian.php" class="dropdown-item">Antrian Online (All)</a><?php endif; ?>
                <?php if (in_array('mjkn_antrian.php', $allowed_files)): ?><a href="mjkn_antrian.php" class="dropdown-item">Monitoring Mobile JKN</a><?php endif; ?>
                <?php if (in_array('poli_antrian.php', $allowed_files)): ?><a href="poli_antrian.php" class="dropdown-item">Antrian Per Poli</a><?php endif; ?>
                <?php if (in_array('erm.php', $allowed_files)): ?><a href="erm.php" class="dropdown-item">Statistik E-RM</a><?php endif; ?>
                <?php if (in_array('satu_sehat.php', $allowed_files)): ?><a href="satu_sehat.php" class="dropdown-item">Satu Sehat Monitor</a><?php endif; ?>
                <?php if (in_array('progres_kerja.php', $allowed_files)): ?><a href="progres_kerja.php" class="dropdown-item">Progres Kerja SIMRS</a><?php endif; ?>
                <?php if (in_array('antrian_pertanggal.php', $allowed_files)): ?><a href="antrian_pertanggal.php" class="dropdown-item">Antrian Per Tanggal</a><?php endif; ?>
                <?php if (in_array('slide_pelaporan.php', $allowed_files)): ?><a href="slide_pelaporan.php" class="dropdown-item">Slide Laporan Direksi</a><?php endif; ?>
            </div>
        </div>

        <!-- 2. OPERATIONAL SERVICES (INPUT) -->
        <?php $input_files = ['order_tiket_it_software.php', 'order_tiket_it_hardware.php', 'order_tiket_sarpras.php', 'off_duty.php', 'lembur.php', 'izin_keluar.php', 'izin_pulang_cepat.php', 'pengajuan_cuti.php', 'ganti_jadwal_dinas.php', 'edit_data_simrs.php', 'hapus_data.php', 'catatan_kerja.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($input_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="file-edit"></i> OPERATIONAL SERVICES (INPUT)
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('order_tiket_it_software.php', $allowed_files)): ?><a href="order_tiket_it_software.php" class="dropdown-item">Request IT (Software)</a><?php endif; ?>
                <?php if (in_array('order_tiket_it_hardware.php', $allowed_files)): ?><a href="order_tiket_it_hardware.php" class="dropdown-item">Request IT (Hardware)</a><?php endif; ?>
                <?php if (in_array('order_tiket_sarpras.php', $allowed_files)): ?><a href="order_tiket_sarpras.php" class="dropdown-item">Request Sarpras Service</a><?php endif; ?>
                <?php if (in_array('pengajuan_cuti.php', $allowed_files)): ?><a href="pengajuan_cuti.php" class="dropdown-item">Pengajuan E-Cuti Online</a><?php endif; ?>
                <?php if (in_array('izin_keluar.php', $allowed_files)): ?><a href="izin_keluar.php" class="dropdown-item">Izin Keluar Kantor</a><?php endif; ?>
                <?php if (in_array('izin_pulang_cepat.php', $allowed_files)): ?><a href="izin_pulang_cepat.php" class="dropdown-item">Izin Pulang Cepat</a><?php endif; ?>
                <?php if (in_array('ganti_jadwal_dinas.php', $allowed_files)): ?><a href="ganti_jadwal_dinas.php" class="dropdown-item">Ganti Jadwal Dinas</a><?php endif; ?>
                <?php if (in_array('off_duty.php', $allowed_files)): ?><a href="off_duty.php" class="dropdown-item">Off-Duty Request</a><?php endif; ?>
                <?php if (in_array('lembur.php', $allowed_files)): ?><a href="lembur.php" class="dropdown-item">Lembur Request</a><?php endif; ?>
                <?php if (in_array('edit_data_simrs.php', $allowed_files)): ?><a href="edit_data_simrs.php" class="dropdown-item">Request Edit SIMRS</a><?php endif; ?>
                <?php if (in_array('hapus_data.php', $allowed_files)): ?><a href="hapus_data.php" class="dropdown-item">Request Hapus SIMRS</a><?php endif; ?>
                <?php if (in_array('catatan_kerja.php', $allowed_files)): ?><a href="catatan_kerja.php" class="dropdown-item">Logbook Kerja Harian</a><?php endif; ?>
            </div>
        </div>

        <!-- 3. COMMAND CENTER (APPROVAL) -->
        <?php $approval_files = ['data_tiket_it_software.php', 'data_tiket_it_hardware.php', 'data_tiket_sarpras.php', 'data_off_duty.php', 'handling_time.php', 'data_cuti_atasan.php', 'data_cuti_hrd.php', 'data_cuti_delegasi.php', 'acc_keluar_atasan.php', 'acc_keluar_sdm.php', 'acc_lembur_atasan.php', 'acc_lembur_sdm.php', 'acc_edit_data.php', 'data_permintaan_edit_simrs.php', 'data_permintaan_hapus_simrs.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($approval_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="check-square"></i> COMMAND CENTER (APPROVAL)
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('data_tiket_it_software.php', $allowed_files)): ?><a href="data_tiket_it_software.php" class="dropdown-item">Monitoring Tiket Soft</a><?php endif; ?>
                <?php if (in_array('data_tiket_it_hardware.php', $allowed_files)): ?><a href="data_tiket_it_hardware.php" class="dropdown-item">Monitoring Tiket Hard</a><?php endif; ?>
                <?php if (in_array('data_tiket_sarpras.php', $allowed_files)): ?><a href="data_tiket_sarpras.php" class="dropdown-item">Monitoring Tiket Sarpras</a><?php endif; ?>
                <?php if (in_array('handling_time.php', $allowed_files)): ?><a href="handling_time.php" class="dropdown-item">Handling Time Service</a><?php endif; ?>
                <?php if (in_array('data_off_duty.php', $allowed_files)): ?><a href="data_off_duty.php" class="dropdown-item">Data Off-Duty Monitor</a><?php endif; ?>
                <?php if (in_array('data_cuti_atasan.php', $allowed_files)): ?><a href="data_cuti_atasan.php" class="dropdown-item">ACC Cuti Atasan</a><?php endif; ?>
                <?php if (in_array('data_cuti_hrd.php', $allowed_files)): ?><a href="data_cuti_hrd.php" class="dropdown-item">ACC Cuti HRD</a><?php endif; ?>
                <?php if (in_array('data_cuti_delegasi.php', $allowed_files)): ?><a href="data_cuti_delegasi.php" class="dropdown-item">ACC Cuti Delegasi</a><?php endif; ?>
                <?php if (in_array('acc_keluar_atasan.php', $allowed_files)): ?><a href="acc_keluar_atasan.php" class="dropdown-item">ACC Izin Atasan</a><?php endif; ?>
                <?php if (in_array('acc_keluar_sdm.php', $allowed_files)): ?><a href="acc_keluar_sdm.php" class="dropdown-item">ACC Izin SDM</a><?php endif; ?>
                <?php if (in_array('acc_lembur_atasan.php', $allowed_files)): ?><a href="acc_lembur_atasan.php" class="dropdown-item">ACC Lembur Atasan</a><?php endif; ?>
                <?php if (in_array('acc_lembur_sdm.php', $allowed_files)): ?><a href="acc_lembur_sdm.php" class="dropdown-item">ACC Lembur SDM</a><?php endif; ?>
                <?php if (in_array('acc_edit_data.php', $allowed_files)): ?><a href="acc_edit_data.php" class="dropdown-item">ACC Edit SIMRS</a><?php endif; ?>
                <?php if (in_array('data_permintaan_hapus_simrs.php', $allowed_files)): ?><a href="data_permintaan_hapus_simrs.php" class="dropdown-item">ACC Hapus SIMRS</a><?php endif; ?>
            </div>
        </div>

        <!-- 4. DIGITAL GOVERNANCE & TTE -->
        <?php $tte_files = ['bubuhkan_tte.php', 'bubuhkan_stampel.php', 'cek_tte.php', 'dokumen_tte.php', 'surat_masuk.php', 'surat_keluar.php', 'disposisi.php', 'spo.php', 'rapat_bulanan.php', 'surat_edaran.php', 'pemberitahuan.php', 'arsip_digital.php', 'master_no_surat.php', 'kategori_arsip.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($tte_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="shield-check"></i> DIGITAL GOVERNANCE & TTE
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('bubuhkan_tte.php', $allowed_files)): ?><a href="bubuhkan_tte.php" class="dropdown-item">Digital Signature (TTE)</a><?php endif; ?>
                <?php if (in_array('bubuhkan_stampel.php', $allowed_files)): ?><a href="bubuhkan_stampel.php" class="dropdown-item">Stempel Digital (E-Stemp)</a><?php endif; ?>
                <?php if (in_array('cek_tte.php', $allowed_files)): ?><a href="cek_tte.php" class="dropdown-item">Verifikasi Dokumen</a><?php endif; ?>
                <?php if (in_array('dokumen_tte.php', $allowed_files)): ?><a href="dokumen_tte.php" class="dropdown-item">Arsip TTE Saya</a><?php endif; ?>
                <?php if (in_array('surat_masuk.php', $allowed_files)): ?><a href="surat_masuk.php" class="dropdown-item">Surat Masuk & Disposisi</a><?php endif; ?>
                <?php if (in_array('surat_keluar.php', $allowed_files)): ?><a href="surat_keluar.php" class="dropdown-item">Surat Keluar Resmi</a><?php endif; ?>
                <?php if (in_array('spo.php', $allowed_files)): ?><a href="spo.php" class="dropdown-item">E-SPO Digital</a><?php endif; ?>
                <?php if (in_array('rapat_bulanan.php', $allowed_files)): ?><a href="rapat_bulanan.php" class="dropdown-item">Notulen Rapat Bulanan</a><?php endif; ?>
                <?php if (in_array('surat_edaran.php', $allowed_files)): ?><a href="surat_edaran.php" class="dropdown-item">Surat Edaran / Memo</a><?php endif; ?>
                <?php if (in_array('arsip_digital.php', $allowed_files)): ?><a href="arsip_digital.php" class="dropdown-item">Brankas Digital (Arsip)</a><?php endif; ?>
            </div>
        </div>

        <!-- 5. MASTER REPOSITORY & ASSETS -->
        <?php $master_files = ['data_barang_it.php', 'data_barang_ac.php', 'data_karyawan.php', 'unit_kerja.php', 'jabatan.php', 'master_poliklinik.php', 'maintenance_rutin.php', 'koneksi_bridging.php', 'spo_it.php', 'berita_acara_it.php', 'master_cuti.php', 'jatah_cuti.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($master_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="database"></i> MASTER REPOSITORY & ASSETS
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('data_barang_it.php', $allowed_files)): ?><a href="data_barang_it.php" class="dropdown-item">Inventaris Aset IT</a><?php endif; ?>
                <?php if (in_array('data_barang_ac.php', $allowed_files)): ?><a href="data_barang_ac.php" class="dropdown-item">Inventaris Aset Sarpras</a><?php endif; ?>
                <?php if (in_array('data_karyawan.php', $allowed_files)): ?><a href="data_karyawan.php" class="dropdown-item">Database Pegawai / SDM</a><?php endif; ?>
                <?php if (in_array('maintenance_rutin.php', $allowed_files)): ?><a href="maintenance_rutin.php" class="dropdown-item">Maintenance Periodik</a><?php endif; ?>
                <?php if (in_array('spo_it.php', $allowed_files)): ?><a href="spo_it.php" class="dropdown-item">SPO IT Departemen</a><?php endif; ?>
                <?php if (in_array('berita_acara_it.php', $allowed_files)): ?><a href="berita_acara_it.php" class="dropdown-item">Berita Acara Kerusakan</a><?php endif; ?>
                <?php if (in_array('unit_kerja.php', $allowed_files)): ?><a href="unit_kerja.php" class="dropdown-item">Master Unit & Jabatan</a><?php endif; ?>
                <?php if (in_array('master_cuti.php', $allowed_files)): ?><a href="master_cuti.php" class="dropdown-item">Master Jenis Cuti</a><?php endif; ?>
                <?php if (in_array('jatah_cuti.php', $allowed_files)): ?><a href="jatah_cuti.php" class="dropdown-item">Master Jatah Cuti</a><?php endif; ?>
            </div>
        </div>

        <!-- 6. SYSTEM INTEGRITY & SECURITY -->
        <?php $audit_files = ['log_login.php', 'activity_log.php', 'scan_malware.php', 'generate_hash_asli.php', 'laporan_harian.php', 'laporan_bulanan.php', 'laporan_tahunan.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($audit_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="fingerprint"></i> SYSTEM INTEGRITY & SECURITY
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <?php if (in_array('log_login.php', $allowed_files)): ?><a href="log_login.php" class="dropdown-item">Audit Log Pelaporan</a><?php endif; ?>
                <?php if (in_array('activity_log.php', $allowed_files)): ?><a href="activity_log.php" class="dropdown-item">Universal Activity Log</a><?php endif; ?>
                <?php if (in_array('scan_malware.php', $allowed_files)): ?><a href="scan_malware.php" class="dropdown-item">Security Scanner Audit</a><?php endif; ?>
                <?php if (in_array('generate_hash_asli.php', $allowed_files)): ?><a href="generate_hash_asli.php" class="dropdown-item">Integrity Audit Audit</a><?php endif; ?>
                <?php if (in_array('laporan_harian.php', $allowed_files)): ?><a href="laporan_harian.php" class="dropdown-item">Periodic Work Report</a><?php endif; ?>
            </div>
        </div>

    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-footer-grid">
            <a href="#" class="btn-sidebar-footer btn-bio" onclick="alert('BexMedia - Swiss Minimalist Architecture v2.5\nDevelopment by DeepMind Team.')">
                <i data-lucide="user"></i> BIO
            </a>
            <a href="#" class="btn-sidebar-footer btn-tte-info" onclick="alert('Sertifikat Elektronik Aktif\nStatus: Secure Content Valid.')">
                <i data-lucide="qr-code"></i> TTE
            </a>
        </div>
        <a href="settings.php" class="nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i data-lucide="settings"></i> System Settings
        </a>
        <a href="logout.php" class="nav-item">
            <i data-lucide="log-out"></i> Logout
        </a>
    </div>
</aside>

<script>
    lucide.createIcons();
    
    // Sidebar Dropdown Toggle Script
    document.querySelectorAll('.nav-dropdown-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const parent = trigger.parentElement;
            
            // Close other dropdowns
            document.querySelectorAll('.nav-dropdown').forEach(item => {
                if (item !== parent) item.classList.remove('open');
            });

            parent.classList.toggle('open');
        });
    });

    // Auto-open active groups based on children's active state
    document.querySelectorAll('.dropdown-item.active').forEach(item => {
        const dropdown = item.closest('.nav-dropdown');
        if (dropdown) dropdown.classList.add('open');
    });

    // 🔍 SEARCH MENU LOGIC (REAL-TIME)
    const menuSearchInput = document.getElementById('menuSearchInput');
    menuSearchInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const navItems = document.querySelectorAll('.nav-item, .dropdown-item');
        
        document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
            let hasMatch = false;
            const items = dropdown.querySelectorAll('.dropdown-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = 'block';
                    hasMatch = true;
                } else {
                    item.style.display = 'none';
                }
            });

            if (hasMatch && query !== '') {
                dropdown.style.display = 'block';
                dropdown.classList.add('open');
            } else if (query !== '') {
                // Check if trigger itself matches
                const trigger = dropdown.querySelector('.nav-dropdown-trigger');
                if (trigger.textContent.toLowerCase().includes(query)) {
                    dropdown.style.display = 'block';
                } else {
                    dropdown.style.display = 'none';
                }
            } else {
                // Reset to default
                dropdown.style.display = 'block';
                // Only keep open if it has an active child
                if (!dropdown.querySelector('.dropdown-item.active')) {
                    dropdown.classList.remove('open');
                }
            }
        });

        // Search for top-level nav-items (like Dashboard)
        document.querySelectorAll('a.nav-item').forEach(item => {
            if (!item.closest('.nav-dropdown')) {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            }
        });
    });
</script>







