<?php
// sidebar.php - Original BexMedia "Swiss Minimalist" Structure
$current_page = basename($_SERVER['PHP_SELF']);

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
</style>
<aside>
    <div class="logo-section">
        <a href="index.php" style="display: block; text-decoration: none;">
            <img src="<?php echo $logo_path; ?>" alt="BexMedia Logo" style="height: 52px; width: auto; display: block; filter: none;">
        </a>
    </div>

    <nav class="nav-menu">
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i data-lucide="layout-grid"></i> Dashboard Utama
        </a>

        <!-- 1. Insight & Analytics -->
        <?php $insight_files = ['dashboard2.php', 'semua_antrian.php', 'mjkn_antrian.php', 'poli_antrian.php', 'erm.php', 'satu_sehat.php', 'progres_kerja.php', 'slide_pelaporan.php', 'antrian_pertanggal.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($insight_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="bar-chart-3"></i> Insight & Analytics
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="dashboard2.php" class="dropdown-item">Dashboard Direktur</a>
                <a href="semua_antrian.php" class="dropdown-item">Antrian Online (All)</a>
                <a href="mjkn_antrian.php" class="dropdown-item">Monitoring Mobile JKN</a>
                <a href="erm.php" class="dropdown-item">Statistik E-RM</a>
                <a href="satu_sehat.php" class="dropdown-item">Satu Sehat Monitor</a>
                <a href="progres_kerja.php" class="dropdown-item">Progres Kerja SIMRS</a>
                <a href="antrian_pertanggal.php" class="dropdown-item">Antrian Per Tanggal</a>
                <a href="slide_pelaporan.php" class="dropdown-item">Slide Pelaporan Direksi</a>
            </div>
        </div>

        <!-- 2. Technical Support (IT & Sarpras) -->
        <?php $tech_files = ['order_tiket_it_software.php', 'order_tiket_it_hardware.php', 'order_tiket_sarpras.php', 'data_tiket_it_software.php', 'handling_time.php', 'maintenance_rutin.php', 'data_barang_it.php', 'koneksi_bridging.php', 'berita_acara_it.php', 'off_duty.php', 'data_off_duty.php', 'acc_edit_data.php', 'data_permintaan_edit_simrs.php', 'data_permintaan_hapus_simrs.php', 'spo_it.php', 'data_barang_ac.php', 'maintanance_rutin_sarpras.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($tech_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="settings-2"></i> Technical Support
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="order_tiket_it_software.php" class="dropdown-item">Software Service Request</a>
                <a href="order_tiket_it_hardware.php" class="dropdown-item">Hardware Service Request</a>
                <a href="order_tiket_sarpras.php" class="dropdown-item">Sarpras Service Request</a>
                <a href="handling_time.php" class="dropdown-item">Handling Time IT</a>
                <a href="maintenance_rutin.php" class="dropdown-item">Maintenance Rutin</a>
                <a href="data_barang_it.php" class="dropdown-item">Inventaris Aset IT</a>
                <a href="data_barang_ac.php" class="dropdown-item">Inventaris Aset Sarpras</a>
                <a href="berita_acara_it.php" class="dropdown-item">Berita Acara Kerusakan</a>
                <a href="off_duty.php" class="dropdown-item">Off-Duty Request</a>
                <a href="data_off_duty.php" class="dropdown-item">Data Off-Duty</a>
                <a href="acc_edit_data.php" class="dropdown-item">ACC Edit Data</a>
                <a href="spo_it.php" class="dropdown-item">SPO IT Departemen</a>
                <a href="koneksi_bridging.php" class="dropdown-item">Koneksi Bridging</a>
            </div>
        </div>

        <!-- 3. Digital Archive & TTE -->
        <?php $tte_files = ['buat_tte.php', 'bubuhkan_tte.php', 'bubuhkan_stampel.php', 'cek_tte.php', 'dokumen_tte.php', 'surat_masuk.php', 'surat_keluar.php', 'agenda_direktur.php', 'arsip_digital.php', 'rapat_bulanan.php', 'surat_edaran.php', 'dokumen_tte_semua.php', 'cek_stampel.php', 'buat_stampel.php', 'spo.php', 'pemberitahuan.php', 'master_no_surat.php', 'disposisi.php', 'kategori_arsip.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($tte_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="shield-check"></i> Digital Archive & TTE
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="bubuhkan_tte.php" class="dropdown-item">Digital Signature (TTE)</a>
                <a href="bubuhkan_stampel.php" class="dropdown-item">Stempel Digital (E-Stemp)</a>
                <a href="cek_tte.php" class="dropdown-item">Verifikasi Dokumen</a>
                <a href="dokumen_tte.php" class="dropdown-item">Arsip TTE Saya</a>
                <a href="dokumen_tte_semua.php" class="dropdown-item">Semua Dokumen TTE</a>
                <a href="surat_masuk.php" class="dropdown-item">Surat Masuk</a>
                <a href="surat_keluar.php" class="dropdown-item">Surat Keluar</a>
                <a href="disposisi.php" class="dropdown-item">Disposisi Digital</a>
                <a href="rapat_bulanan.php" class="dropdown-item">Notulen Rapat</a>
                <a href="surat_edaran.php" class="dropdown-item">Surat Edaran</a>
                <a href="pemberitahuan.php" class="dropdown-item">Pemberitahuan Resmi</a>
                <a href="spo.php" class="dropdown-item">E-SPO Digital</a>
                <a href="master_no_surat.php" class="dropdown-item">Log Nomor Surat</a>
                <a href="agenda_direktur.php" class="dropdown-item">Agenda Direksi</a>
                <a href="arsip_digital.php" class="dropdown-item">Brankas Digital</a>
            </div>
        </div>

        <!-- 4. Employee Hub (SDM & Payroll) -->
        <?php $sdm_files = ['data_karyawan.php', 'absensi.php', 'jadwal_dinas.php', 'pengajuan_cuti.php', 'izin_keluar.php', 'input_gaji.php', 'data_gaji.php', 'catatan_kerja.php', 'input_kpi.php', 'exit_clearance.php', 'ganti_jadwal_dinas.php', 'data_cuti.php', 'jatah_cuti.php', 'master_cuti.php', 'data_izin_keluar.php', 'acc_keluar_atasan.php', 'acc_keluar_sdm.php', 'masa_kerja.php', 'kesehatan.php', 'fungsional.php', 'struktural.php', 'gaji_pokok.php', 'potongan_bpjs_kes.php', 'potongan_bpjs_jht.php', 'potongan_bpjs_tk_jp.php', 'potongan_dana_sosial.php', 'pph21.php', 'rekap_catatan_kerja.php', 'data_pelamar.php', 'lamaran.php', 'lowongan.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($sdm_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="users"></i> Employee Hub
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="data_karyawan.php" class="dropdown-item">Database Karyawan</a>
                <a href="absensi.php" class="dropdown-item">Absensi GPS & Foto</a>
                <a href="jadwal_dinas.php" class="dropdown-item">Jadwal Dinas</a>
                <a href="ganti_jadwal_dinas.php" class="dropdown-item">Ganti Jadwal Dinas</a>
                <a href="pengajuan_cuti.php" class="dropdown-item">E-Cuti Online</a>
                <a href="data_cuti.php" class="dropdown-item">Data Cuti Karyawan</a>
                <a href="jatah_cuti.php" class="dropdown-item">Jatah Cuti Saya</a>
                <a href="izin_keluar.php" class="dropdown-item">Izin Keluar Kantor</a>
                <a href="acc_keluar_atasan.php" class="dropdown-item">ACC Izin Atasan</a>
                <a href="acc_keluar_sdm.php" class="dropdown-item">ACC Izin SDM</a>
                <a href="exit_clearance.php" class="dropdown-item">Exit Clearance</a>
                <a href="input_gaji.php" class="dropdown-item">Transaksi Payroll</a>
                <a href="data_gaji.php" class="dropdown-item">Slip Gaji Online</a>
                <a href="catatan_kerja.php" class="dropdown-item">Logbook Kerja</a>
                <a href="rekap_catatan_kerja.php" class="dropdown-item">Rekap Kerja SDM</a>
                <a href="input_kpi.php" class="dropdown-item">Indikator KPI</a>
                <a href="lowongan.php" class="dropdown-item">E-Recruitment</a>
            </div>
        </div>

        <!-- 5. Quality Assurance -->
        <?php $qa_files = ['input_harian.php', 'capaian_imut.php', 'praktek.php', 'wawancara.php', 'hasil_kredensial.php', 'sip_str.php', 'data_dokumen.php', 'master_pokja.php', 'master_imut_nasional.php', 'master_indikator.php', 'master_indikator_rs.php', 'master_indikator_unit.php', 'input_soal.php', 'judul_soal.php', 'ujian_tertulis.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($qa_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="award"></i> Quality Assurance
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="input_harian.php" class="dropdown-item">Input IMUT Harian</a>
                <a href="capaian_imut.php" class="dropdown-item">Capaian Mutu RS</a>
                <a href="master_indikator.php" class="dropdown-item">Master Indikator</a>
                <a href="praktek.php" class="dropdown-item">Kredensial Clinical</a>
                <a href="hasil_kredensial.php" class="dropdown-item">Hasil Kredensial</a>
                <a href="ujian_tertulis.php" class="dropdown-item">Ujian Kredensial</a>
                <a href="sip_str.php" class="dropdown-item">Laporan STR/SIP</a>
                <a href="data_dokumen.php" class="dropdown-item">Dokumen Akreditasi</a>
                <a href="master_pokja.php" class="dropdown-item">Master Pokja Audit</a>
            </div>
        </div>

        <!-- 6. Bex Reports & Audit -->
        <?php $report_files = ['laporan_harian.php', 'laporan_bulanan.php', 'laporan_tahunan.php', 'log_login.php', 'activity_log.php', 'scan_malware.php', 'generate_hash_asli.php']; ?>
        <div class="nav-dropdown <?php echo is_group_active($report_files) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="fingerprint"></i> Audit & Security
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="laporan_harian.php" class="dropdown-item">Report Harian</a>
                <a href="laporan_bulanan.php" class="dropdown-item">Report Bulanan</a>
                <a href="log_login.php" class="dropdown-item">Audit Log Login</a>
                <a href="activity_log.php" class="dropdown-item">Universal Activity Log</a>
                <a href="scan_malware.php" class="dropdown-item">Security Scanner</a>
                <a href="generate_hash_asli.php" class="dropdown-item">Console Integrity Audit</a>
            </div>
        </div>

    </nav>

    <div class="sidebar-bottom">
        <a href="settings.php" class="nav-item">
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
</script>







