<?php
require_once "conf/config.php";
checkLogin();

$brand_name = get_setting('app_name', 'BexMedia');
$logo_path  = "images/bm.png"; // Default

$status_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    csrf_verify();
    
    foreach ($_POST as $key => $value) {
        if ($key != 'csrf_token' && $key != 'save_settings') {
            $safe_val = mysqli_real_escape_string($conn, $value);
            mysqli_query($conn, "UPDATE web_settings SET setting_value = '$safe_val' WHERE setting_key = '$key'");
        }
    }
    
    write_log("UPDATE_SETTINGS", "User " . $_SESSION['username'] . " memperbarui pengaturan sistem.");
    $status_msg = "Pengaturan berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?php echo h($brand_name); ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/index.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .settings-nav {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            height: fit-content;
        }

        .settings-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 5px;
        }

        .settings-nav-item:hover, .settings-nav-item.active {
            background: #F0F7FF;
            color: var(--primary);
        }

        .settings-content {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            margin-bottom: 20px;
            font-size: 1.1rem;
            color: var(--text-dark);
            border-bottom: 1px solid #EEE;
            padding-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .status-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10B981;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease;
            z-index: 1000;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .log-table th, .log-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #F1F5F9;
        }

        .log-table th {
            color: var(--text-muted);
            font-weight: 600;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: #DCFCE7; color: #166534; }
        .badge-info { background: #E0F2FE; color: #075985; }
        .badge-warn { background: #FEF3C7; color: #92400E; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside>
            <div class="logo-section" style="-webkit-text-fill-color: initial; background: initial; background-clip: initial; color: var(--ice-2); display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 1.6rem; margin-bottom: 48px;">
                <img src="<?php echo $logo_path; ?>" alt="Logo" class="brand-logo" style="filter: none; height: 40px; width: auto; object-fit: contain;"> 
                <span style="background: var(--grad-premium); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo h($brand_name); ?></span>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
                <a href="#" class="nav-item"><i data-lucide="layers"></i> Overview</a>
                <a href="#" class="nav-item"><i data-lucide="megaphone"></i> Campaign</a>
                <a href="#" class="nav-item"><i data-lucide="bar-chart-3"></i> Analytics</a>
                <a href="#" class="nav-item"><i data-lucide="image"></i> Media Assets</a>
                <a href="#" class="nav-item"><i data-lucide="activity"></i> Performance</a>
                <a href="#" class="nav-item"><i data-lucide="users"></i> Clients</a>
                <a href="#" class="nav-item"><i data-lucide="file-text"></i> Report</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="settings.php" class="nav-item active"><i data-lucide="settings"></i> Settings</a>
                <a href="logout.php" class="nav-item"><i data-lucide="log-out"></i> Logout</a>
            </div>
        </aside>

        <main>
            <header>
                <div class="breadcrumb" style="color: var(--text-muted); font-size: 0.9rem">
                    Pages / <strong>Settings</strong>
                </div>
                <div class="user-profile">
                    <span><?php echo h($_SESSION['username']); ?></span>
                    <div class="avatar" style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=3B82F6&color=fff');"></div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>System Settings</h1>
                    <p>Configure your hybrid server and application parameters.</p>
                </div>

                <?php if ($status_msg): ?>
                    <div class="status-toast" id="statusToast">
                        <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px"></i>
                        <?php echo $status_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <div class="settings-nav">
                        <div class="settings-nav-item active" onclick="showTab('general')">
                            <i data-lucide="box"></i> General
                        </div>
                        <div class="settings-nav-item" onclick="showTab('connection')">
                            <i data-lucide="database"></i> Database
                        </div>
                        <div class="settings-nav-item" onclick="showTab('logs')">
                            <i data-lucide="activity"></i> Audit Logs
                        </div>
                    </div>

                    <form method="POST" class="settings-content">
                        <?php echo csrf_field(); ?>
                        
                        <!-- TAB: GENERAL -->
                        <div id="general" class="tab-content active">
                            <div class="form-section">
                                <h3>Application Info</h3>
                                <div class="form-group">
                                    <label>Application Name (Brand)</label>
                                    <input type="text" name="app_name" value="<?php echo h(get_setting('app_name', 'BexMedia')); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- TAB: CONNECTION -->
                        <div id="connection" class="tab-content">
                            <div class="form-section">
                                <h3>Database Khanza (Remote Server)</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Hostname / IP</label>
                                        <input type="text" name="host_khanza" value="<?php echo h(get_setting('host_khanza')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Database Name</label>
                                        <input type="text" name="name_khanza" value="<?php echo h(get_setting('name_khanza')); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="user_khanza" value="<?php echo h(get_setting('user_khanza')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="pass_khanza" value="<?php echo h(get_setting('pass_khanza')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Database BexMedia (Local Server)</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Hostname</label>
                                        <input type="text" name="host_bex" value="<?php echo h(get_setting('host_bex')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Database Name</label>
                                        <input type="text" name="name_bex" value="<?php echo h(get_setting('name_bex')); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="user_bex" value="<?php echo h(get_setting('user_bex')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="pass_bex" value="<?php echo h(get_setting('pass_bex')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: LOGS -->
                        <div id="logs" class="tab-content">
                            <div class="form-section">
                                <h3>Recent Audit Logs</h3>
                                <table class="log-table">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $logs = mysqli_query($conn, "SELECT * FROM web_dokter_audit_log ORDER BY created_at DESC LIMIT 10");
                                        while ($log = mysqli_fetch_assoc($logs)):
                                            $badge_class = "badge-info";
                                            if (strpos($log['action'], 'FAILED') !== false || strpos($log['action'], 'FAILURE') !== false) $badge_class = "badge-warn";
                                            if (strpos($log['action'], 'SUCCESS') !== false) $badge_class = "badge-success";
                                        ?>
                                        <tr>
                                            <td style="color: var(--text-muted)"><?php echo $log['created_at']; ?></td>
                                            <td><strong><?php echo h($log['user_id']); ?></strong></td>
                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo h($log['action']); ?></span></td>
                                            <td><?php echo h($log['description']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="saveContainer" style="margin-top: 20px;">
                            <button type="submit" name="save_settings" class="btn-save">
                                <i data-lucide="save" size="18" style="vertical-align: middle; margin-right: 8px"></i>
                                Save All Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function showTab(tabId) {
            // Update nav
            document.querySelectorAll('.settings-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Show content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');

            // Hide save button on logs tab
            const saveBtn = document.getElementById('saveContainer');
            if (tabId === 'logs') {
                saveBtn.style.display = 'none';
            } else {
                saveBtn.style.display = 'block';
            }
        }

        // Auto hide toast
        setTimeout(() => {
            const toast = document.getElementById('statusToast');
            if (toast) toast.style.display = 'none';
        }, 3000);
    </script>
</body>
</html>
