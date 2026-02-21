<?php
// sidebar.php - BexMedia Luxury Sidebar with Dropdowns
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside>
    <div class="logo-section">
        <a href="index.php" style="display: block; text-decoration: none;">
            <img src="<?php echo $logo_path; ?>" alt="BexMedia Logo" style="height: 52px; width: auto; display: block; filter: none;">
        </a>
    </div>

    <nav class="nav-menu">
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i data-lucide="layout-grid"></i> Dashboard
        </a>

        <!-- 1. Insight & Analytics -->
        <div class="nav-dropdown <?php echo in_array($current_page, ['antrian.php', 'stats.php']) ? 'open' : ''; ?>">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="layers"></i> Insight & Analytics
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" class="dropdown-item">Monitor Antrian</a>
                <a href="#" class="dropdown-item">Statistik ERM</a>
                <a href="#" class="dropdown-item">Satu Sehat Monitor</a>
            </div>
        </div>

        <!-- 2. Quality Assurance -->
        <div class="nav-dropdown">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="megaphone"></i> Quality Assurance
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" class="dropdown-item">Indikator Mutu</a>
                <a href="#" class="dropdown-item">Komite Keperawatan</a>
                <a href="#" class="dropdown-item">Digital SPO</a>
            </div>
        </div>

        <!-- 3. Digital Archive -->
        <div class="nav-dropdown">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="image"></i> Digital Archive
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" class="dropdown-item">TTE Dokumen</a>
                <a href="#" class="dropdown-item">Surat Masuk/Keluar</a>
                <a href="#" class="dropdown-item">Arsip Digital</a>
            </div>
        </div>

        <!-- 4. Technical Support -->
        <div class="nav-dropdown">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="activity"></i> Technical Support
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" class="dropdown-item">IT Helpdesk</a>
                <a href="#" class="dropdown-item">Sarpras Support</a>
                <a href="#" class="dropdown-item">Handling Time</a>
            </div>
        </div>

        <!-- 5. Employee Hub -->
        <div class="nav-dropdown">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="users"></i> Employee Hub
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="clients.php" class="dropdown-item <?php echo ($current_page == 'clients.php') ? 'active' : ''; ?>">Data Karyawan</a>
                <a href="#" class="dropdown-item">Absensi & Jadwal</a>
                <a href="#" class="dropdown-item">Payroll / Slip Gaji</a>
            </div>
        </div>

        <!-- 6. Bex Reports -->
        <div class="nav-dropdown">
            <div class="nav-dropdown-trigger nav-item">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="file-text"></i> Bex Reports
                </span>
                <i data-lucide="chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" class="dropdown-item">Laporan Harian</a>
                <a href="#" class="dropdown-item">Laporan Bulanan</a>
                <a href="#" class="dropdown-item">KPI Performance</a>
            </div>
        </div>
    </nav>

    <div class="sidebar-bottom">
        <a href="settings.php" class="nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i data-lucide="settings"></i> System Center
        </a>
        <a href="logout.php" class="nav-item">
            <i data-lucide="log-out"></i> Logout
        </a>
    </div>
</aside>

<script>
    // Sidebar Dropdown Toggle Script
    document.querySelectorAll('.nav-dropdown-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const parent = trigger.parentElement;
            
            // Close other dropdowns (Optional - but keeps it clean)
            document.querySelectorAll('.nav-dropdown').forEach(item => {
                if (item !== parent) item.classList.remove('open');
            });

            parent.classList.toggle('open');
        });
    });
</script>
