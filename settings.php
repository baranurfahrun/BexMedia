<?php
require_once "conf/config.php";
checkLogin();

$brand_name = get_setting('app_name', 'BexMedia');

// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach: Logo path modified!");
$logo_path = base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    die("Security Breach: Logo file content compromised or missing! Hubungi hak cipta: bara.n.fahrun (085117476001)");
}

$status_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    csrf_verify();
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['csrf_token', 'save_settings'])) {
            $safe_val = mysqli_real_escape_string($conn, $value);
            mysqli_query($conn, "UPDATE web_settings SET setting_value = '$safe_val' WHERE setting_key = '$key'");
        }
    }
    write_log("UPDATE_SETTINGS", "User " . $_SESSION['username'] . " memperbarui pengaturan sistem.");
    $status_msg = "Pengaturan berhasil disimpan!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    csrf_verify();
    $new_nama = cleanInput($_POST['nama_lengkap']);
    $new_pass = $_POST['new_password'];
    $source   = $_SESSION['login_source'] ?? 'BEXMEDIA';

    if ($source === 'BEXMEDIA') {
        $photo_query = "";
        $params = [$new_nama];

        // Handle Photo Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $base_dir = __DIR__ . DIRECTORY_SEPARATOR;
                $target_dir = $base_dir . "images" . DIRECTORY_SEPARATOR;
                
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                
                // --- LOGIKA WEB_DOKTER: HAPUS SEMUA FOTO DENGAN NAMA USER INI (APAPUN EKSTENSINYA) ---
                $user_clean = preg_replace("/[^a-zA-Z0-9]/", "_", $_SESSION['username']); // Sanitasi nama file
                $existing_files = glob($target_dir . $user_clean . ".*");
                if ($existing_files) {
                    foreach ($existing_files as $efile) {
                        if (is_file($efile)) unlink($efile);
                    }
                }

                $new_filename = $user_clean . "." . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $new_filename)) {
                    $photo_query = ", photo = ?";
                    $params[] = $new_filename;
                    $_SESSION['user_photo'] = $new_filename; 
                }
            }
        }

        if (!empty($new_pass)) {
            $sql = "UPDATE users SET nama_lengkap = ?" . $photo_query . ", password = AES_ENCRYPT(?, 'bara') WHERE username = AES_ENCRYPT(?, 'bex')";
            $params[] = $new_pass;
            $params[] = $_SESSION['username'];
            safe_query($sql, $params);
        } else {
            $sql = "UPDATE users SET nama_lengkap = ?" . $photo_query . " WHERE username = AES_ENCRYPT(?, 'bex')";
            $params[] = $_SESSION['username'];
            safe_query($sql, $params);
        }
        write_log("UPDATE_PROFILE", "User " . $_SESSION['username'] . " memperbarui profil (Web_dokter Style Purge).");
        $status_msg = "Profil & Foto berhasil diperbarui!";
    } else {
        $status_msg = "Akun KHANZA hanya bisa mengubah profil melalui SIMRS utama.";
    }
}

// Ambil Data Profil
$current_user = $_SESSION['username'];
$user_full_name = "User " . $current_user;
$user_photo = "";

if (($_SESSION['login_source'] ?? 'BEXMEDIA') === 'BEXMEDIA') {
    $res_u = safe_query("SELECT nama_lengkap, photo FROM users WHERE username = AES_ENCRYPT(?, 'bex')", [$current_user]);
    if ($row_u = mysqli_fetch_assoc($res_u)) {
        $user_full_name = $row_u['nama_lengkap'];
        $user_photo = $row_u['photo'];
        $_SESSION['user_photo'] = $user_photo;
    }
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
        :root {
            --primary: #3B82F6;
            --primary-hover: #2563EB;
        }

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
            position: relative;
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
            background: var(--primary-hover);
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
            <div class="logo-section">
                <a href="index.php" style="display: block; text-decoration: none;">
                    <img src="<?php echo $logo_path; ?>" alt="BexMedia Logo" style="height: 52px; width: auto; display: block; filter: none;">
                </a>
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
                    <?php 
                        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=3B82F6&color=fff";
                        if (!empty($user_photo)) {
                            $avatar_url = "images/" . $user_photo;
                        }
                    ?>
                    <div class="avatar" style="background-image: url('<?php echo $avatar_url; ?>'); background-size: cover; background-position: center;"></div>
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
                        <div class="settings-nav-item active" onclick="showTab('profile')">
                            <i data-lucide="user"></i> My Profile
                        </div>
                        <div class="settings-nav-item" onclick="showTab('general')">
                            <i data-lucide="box"></i> General
                        </div>
                        <div class="settings-nav-item" onclick="showTab('connection')">
                            <i data-lucide="database"></i> Database
                        </div>
                        <div class="settings-nav-item" onclick="showTab('logs')">
                            <i data-lucide="activity"></i> Audit Logs
                        </div>
                    </div>

                    <form method="POST" class="settings-content" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        
                        <!-- TAB: PROFILE -->
                        <div id="profile" class="tab-content active">
                            <div class="form-section">
                                <h3>Account Profile</h3>
                                
                                <div class="form-group" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                                    <?php 
                                        $p_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=F1F5F9&color=333&size=128";
                                        if (!empty($user_photo)) {
                                            $p_url = "images/" . $user_photo;
                                        }
                                    ?>
                                    <div id="photoPreview" style="width: 100px; height: 100px; border-radius: 20px; background-image: url('<?php echo $p_url; ?>'); background-size: cover; background-position: center; border: 4px solid #F1F5F9; box-shadow: 0 4px 10px rgba(0,0,0,0.05);"></div>
                                    <div>
                                        <label style="cursor: pointer; display: inline-block; padding: 10px 20px; background: #F1F5F9; border-radius: 10px; font-size: 0.85rem; font-weight: 600; color: var(--ice-1); transition: all 0.3s; <?php echo ($_SESSION['login_source'] ?? 'BEXMEDIA') !== 'BEXMEDIA' ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                                            <i data-lucide="camera" size="16" style="vertical-align: middle; margin-right: 8px"></i> Change Photo
                                            <input type="file" name="photo" id="photoInput" style="display: none" accept="image/*" onchange="previewImage(this)">
                                        </label>
                                        <p style="margin-top: 8px; font-size: 0.75rem; color: #94A3B8">PNG, JPG or GIF. Max 2MB.</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Username (Read-only)</label>
                                    <input type="text" value="<?php echo h($current_user); ?>" readonly style="background: #F8FAFC">
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="nama_lengkap" value="<?php echo h($user_full_name); ?>" <?php echo ($_SESSION['login_source'] ?? 'BEXMEDIA') !== 'BEXMEDIA' ? 'readonly' : ''; ?>>
                                </div>
                                <div class="form-group">
                                    <label>New Password (Leave blank to keep current)</label>
                                    <input type="password" name="new_password" placeholder="••••••••" <?php echo ($_SESSION['login_source'] ?? 'BEXMEDIA') !== 'BEXMEDIA' ? 'readonly' : ''; ?>>
                                </div>
                                
                                <?php if (($_SESSION['login_source'] ?? 'BEXMEDIA') === 'BEXMEDIA'): ?>
                                <button type="submit" name="update_profile" class="btn-save">Update Profile</button>
                                <?php else: ?>
                                <p style="font-size: 0.8rem; color: #EF4444; font-style: italic">
                                    <i data-lucide="alert-circle" size="14" style="vertical-align: middle"></i> 
                                    Akun Anda berasal dari database KHANZA. Profil hanya bisa diubah di sistem SIMRS utama.
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- TAB: GENERAL -->
                        <div id="general" class="tab-content">
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

            // Hide save button on logs and profile tab (they have special needs)
            const saveBtn = document.getElementById('saveContainer');
            if (tabId === 'logs' || tabId === 'profile') {
                saveBtn.style.display = 'none';
            } else {
                saveBtn.style.display = 'block';
            }
        }

        // Preview Image
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').style.backgroundImage = 'url(' + e.target.result + ')';
                }
                reader.readAsDataURL(input.files[0]);
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
