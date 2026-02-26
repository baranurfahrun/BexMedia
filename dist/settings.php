<?php
include 'security.php';
syncMenus(); 
// MenuName: Pengaturan Sistem

// Handle AJAX Request for Access Data (MUST BE AT TOP)
if (isset($_GET['get_access'])) {
    $target = $_GET['get_access'];
    $data = [];
    $res = safe_query("SELECT menu_id FROM web_access WHERE username = ?", [$target]);
    while($r = mysqli_fetch_assoc($res)) $data[] = (int)$r['menu_id'];
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$brand_name = get_setting('app_name', 'BexMedia');

// === KODE VERIFIKASI KEAMANAN PROJEK ===
$private_key = "KODE_RAHASIA_BARA";
$sig_logo      = "aW1hZ2VzL2xvZ29fZmluYWwucG5n";
$hash_path     = "55dc42da93bc5f52de4c1b967b5f35fe";
$hash_content  = "0201dd7a6e3b787967c12fa9e61d9b6a"; // Hash fisik file

if (md5($sig_logo . $private_key) !== $hash_path) die("Security Breach: Logo path modified!");
$logo_path = "../" . base64_decode($sig_logo);

if (!file_exists($logo_path) || md5_file($logo_path) !== $hash_content) {
    die("Security Breach: Logo file content compromised or missing! Hubungi hak cipta: bara.n.fahrun (085117476001)");
}

$status_msg = "";

// --- MAIL SETTINGS SAVE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_mail']) && $_POST['save_mail'] == '1') {
    csrf_verify();
    $mid              = intval($_POST['mail_id'] ?? 0);
    $mail_host        = mysqli_real_escape_string($conn, $_POST['mail_host'] ?? '');
    $mail_port        = intval($_POST['mail_port'] ?? 587);
    $mail_username    = mysqli_real_escape_string($conn, $_POST['mail_username'] ?? '');
    $mail_password    = mysqli_real_escape_string($conn, $_POST['mail_password'] ?? '');
    $mail_from_email  = mysqli_real_escape_string($conn, $_POST['mail_from_email'] ?? '');
    $mail_from_name   = mysqli_real_escape_string($conn, $_POST['mail_from_name'] ?? '');
    $base_url         = mysqli_real_escape_string($conn, $_POST['base_url'] ?? '');

    if ($mid > 0) {
        mysqli_query($conn, "UPDATE mail_settings SET mail_host='$mail_host', mail_port=$mail_port, mail_username='$mail_username', mail_password='$mail_password', mail_from_email='$mail_from_email', mail_from_name='$mail_from_name', base_url='$base_url' WHERE id=$mid");
    } else {
        mysqli_query($conn, "INSERT INTO mail_settings (mail_host, mail_port, mail_username, mail_password, mail_from_email, mail_from_name, base_url) VALUES ('$mail_host', $mail_port, '$mail_username', '$mail_password', '$mail_from_email', '$mail_from_name', '$base_url')");
    }
    write_log("UPDATE_MAIL_SETTINGS", "User " . $_SESSION['username'] . " memperbarui Email Engine settings.");
    $status_msg = "Email Engine settings berhasil disimpan!";
    
    // Refresh mail settings
    header("Location: settings.php?tab=email&saved=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    csrf_verify();
    
    $settings_fields = [
        'app_name', 'host_khanza', 'name_khanza', 'user_khanza', 'pass_khanza', 
        'host_bex', 'name_bex', 'user_bex', 'pass_bex',
        'running_text', 'rt_speed', 'rt_font_size', 'rt_font_family', 'rt_color',
        'wa_gateway_url', 'wa_number', 'wa_group_it', 'wa_group_sarpras', 
        'wa_db_host', 'wa_db_user', 'wa_db_pass', 'wa_db_name',
        'telegram_bot_token', 'telegram_chat_id'
    ];
    $updated_count = 0;
    $saved_keys = [];
    foreach ($settings_fields as $key) {
        if (isset($_POST[$key])) {
            $val = $_POST[$key];
            $safe_val = mysqli_real_escape_string($conn, $val);
            
            // Cek apakah key sudah ada
            $cek = mysqli_query($conn, "SELECT setting_key FROM web_settings WHERE setting_key = '$key'");
            if (mysqli_num_rows($cek) > 0) {
                $res = mysqli_query($conn, "UPDATE web_settings SET setting_value = '$safe_val' WHERE setting_key = '$key'");
            } else {
                $res = mysqli_query($conn, "INSERT INTO web_settings (setting_key, setting_value) VALUES ('$key', '$safe_val')");
            }
            
            if ($res) {
                $updated_count++;
                $saved_keys[] = $key;
            }
        }
    }
    write_log("UPDATE_SETTINGS", "User " . $_SESSION['username'] . " [DEBUG] Masuk: " . count($saved_keys) . " field (" . implode(",", $saved_keys) . ")");

    $active_tab = "general";
    if (isset($_POST['host_khanza'])) $active_tab = "connection";

    // --- PART 2: UPDATE USER PROFILE (IF FIELDS PRESENT) ---
    if (isset($_POST['nama_lengkap'])) {
        $new_nama = cleanInput($_POST['nama_lengkap']);
        $new_pass = $_POST['new_password'] ?? '';
        $source   = $_SESSION['login_source'] ?? 'BEXMEDIA';

        if ($source === 'BEXMEDIA') {
            $photo_query = "";
            $params = [$new_nama];

            // Handle Photo Upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $target_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR;
                    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                    
                    $user_clean = preg_replace("/[^a-zA-Z0-9]/", "_", $_SESSION['username']);
                    $existing_files = glob($target_dir . $user_clean . ".*");
                    if ($existing_files) {
                        foreach ($existing_files as $efile) { if (is_file($efile)) unlink($efile); }
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
            write_log("UPDATE_PROFILE", "User " . $_SESSION['username'] . " memperbarui profil.");
        }
    }
    
    if (isset($_POST['nama_lengkap'])) $active_tab = "profile";

    header("Location: settings.php?tab=$active_tab&saved=1");
    exit;
}

// --- PART 3: ACTIVATE PENDING USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['activate_user'])) {
    csrf_verify();
    $uid = (int)$_POST['user_id'];
    $res = safe_query("UPDATE users SET status = 'active' WHERE id = ? AND status = 'pending'", [$uid]);
    if ($res) {
        write_log("ACTIVATE_USER", "Administrator mengaktifkan User ID: $uid");
        $status_msg = "Akun berhasil diaktifkan!";
    }
}

// --- PART 4: UPDATE ACCESS RIGHTS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_access'])) {
    csrf_verify();
    $target_user = $_POST['target_username'];
    $menus = $_POST['menus'] ?? [];
    
    // Reset access
    safe_query("DELETE FROM web_access WHERE username = ?", [$target_user]);
    
    // Insert new access
    foreach ($menus as $mid) {
        safe_query("INSERT INTO web_access (username, menu_id) VALUES (?, ?)", [$target_user, (int)$mid]);
    }
    write_log("UPDATE_ACCESS", "Administrator memperbarui hak akses untuk user: $target_user");
    $status_msg = "Hak akses berhasil diperbarui!";
}

// Ambil Data Profil
$current_user = $_SESSION['username'];
$user_full_name = $nama_user;
$user_photo = !empty($foto_user) ? str_replace('images/', '', $foto_user) : '';
if (!empty($user_photo)) $_SESSION['user_photo'] = $user_photo;

// --- PART 5: DATABASE PRE-CHECK ---
$khanza_status = 'offline';
$bex_status = 'offline';

// Check Khanza
if (isset($conn_sik) && $conn_sik !== false) {
    $khanza_status = 'online';
} else {
    $test_sik = mysqli_init();
    mysqli_options($test_sik, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
    if (@mysqli_real_connect($test_sik, get_setting('host_khanza'), get_setting('user_khanza'), get_setting('pass_khanza'), get_setting('name_khanza'))) {
        $khanza_status = 'online';
        mysqli_close($test_sik);
    }
}

// Check Bex (Local)
if (isset($conn) && $conn !== false) {
    $bex_status = 'online';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?php echo h($brand_name); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/index.css">
    <style>
        :root {
            --primary: #3B82F6;
            --primary-hover: #2563EB;
        }

        /* Compact settings page layout */
        main .dashboard-content {
            padding-top: 16px;
            padding-bottom: 20px;
        }
        .dashboard-content .dashboard-header {
            margin-bottom: 10px;
        }
        .dashboard-content .dashboard-header h1 {
            padding-top: 0;
            margin-bottom: 2px;
            font-size: 1.8rem;
        }
        .dashboard-content .dashboard-header p {
            margin-bottom: 0;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-top: 0;
            align-items: start;
        }

        .settings-nav {
            background: white;
            padding: 14px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            height: fit-content;
            position: sticky;
            top: 10px;
        }

        .settings-content {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            position: relative;
            min-height: 300px;
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

        .form-group input, .form-group select {
            width: 100%;
            height: 48px; /* Fixed height for all */
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.3s;
            background: white;
            box-sizing: border-box;
        }

        .form-group input[type="color"] {
            padding: 4px;
            height: 48px;
            cursor: pointer;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
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
        .badge-offline { background: #F1F5F9; color: #64748B; }

        /* Dropdown Styles */
        .nav-sub {
            display: none;
            padding-left: 24px;
            margin-top: 4px;
            border-left: 2px solid #F1F5F9;
            margin-left: 24px;
        }
        .nav-sub.active { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .dropdown-trigger.active .chevron { transform: rotate(180deg); }

        /* Access Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            width: 700px;
            max-width: 90%;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #EEE;
        }
        .menu-check-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .menu-check-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #F8FAFC;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s;
        }
        .menu-check-item:hover { background: #F1F5F9; }
        .menu-check-item input { width: 18px; height: 18px; cursor: pointer; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
        <div class="app-container">
            <!-- Sidebar -->
            <?php include "sidebar.php"; ?>

            <main>
            <?php 
            $breadcrumb = "System / <strong>Settings</strong>";
            include "topbar.php"; 
            ?>


            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1>System Settings</h1>
                    <p>Configure your hybrid server and application parameters.</p>
                </div>

                <?php 
                $msg = $status_msg;
                if (isset($_GET['saved'])) $msg = "Pengaturan berhasil disimpan!";
                if ($msg): 
                ?>
                    <div class="status-toast" id="statusToast">
                        <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px"></i>
                        <?php echo $msg; ?>
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
                        <div class="settings-nav-item" onclick="showTab('email')">
                            <i data-lucide="mail"></i> Email Engine
                        </div>
                        <div class="settings-nav-item" onclick="showTab('notifications')">
                            <i data-lucide="bell"></i> Notification Gateway
                        </div>
                        
                        <!-- Account Management Dropdown -->
                        <div class="settings-nav-item dropdown-trigger" onclick="toggleSub('acc-mgmt')">
                            <i data-lucide="users"></i> Management Akun
                            <i data-lucide="chevron-down" class="chevron" size="16"></i>
                        </div>
                        <div id="acc-mgmt" class="nav-sub">
                            <div class="settings-nav-item" onclick="showTab('access')" style="font-size: 0.9rem;">
                                <i data-lucide="shield-check" size="18"></i> Hak Akses
                            </div>
                            <div class="settings-nav-item" onclick="showTab('userlist')" style="font-size: 0.9rem;">
                                <i data-lucide="list" size="18"></i> Daftar Akun
                            </div>
                            <div class="settings-nav-item" onclick="showTab('confirm')" style="font-size: 0.9rem;">
                                <i data-lucide="user-plus" size="18"></i> Konfirmasi Akun
                            </div>
                        </div>

                        <div class="settings-nav-item" onclick="showTab('logs')">
                            <i data-lucide="activity"></i> Audit Logs
                        </div>
                        <div class="settings-nav-item" onclick="showTab('integrity')">
                            <i data-lucide="shield-check"></i> Performance Guard
                        </div>
                    </div>

                    <div class="settings-content">
                        
                        <!-- TAB: PROFILE -->
                        <div id="profile" class="tab-content active">
                            <form method="POST" enctype="multipart/form-data">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                            <div class="form-section">
                                <h3>Account Profile</h3>
                                
                                <div class="form-group" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                                    <?php 
                                        $p_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=F1F5F9&color=333&size=128";
                                        if (!empty($user_photo)) {
                                            $p_url = "../images/" . $user_photo;
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
                                
                                <?php if (($_SESSION['login_source'] ?? 'BEXMEDIA') !== 'BEXMEDIA'): ?>
                                <p style="font-size: 0.8rem; color: #EF4444; font-style: italic">
                                    <i data-lucide="alert-circle" size="14" style="vertical-align: middle"></i> 
                                    Akun Anda berasal dari database KHANZA. Profil hanya bisa diubah di sistem SIMRS utama.
                                </p>
                                <?php endif; ?>

                                <button type="submit" class="btn-save">
                                    <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                    Simpan Perubahan Profil
                                </button>
                            </div>
                            </form>
                        </div>

                        <!-- TAB: GENERAL -->
                        <div id="general" class="tab-content">
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                                <div class="form-section">
                                    <h3>App Identity</h3>
                                    <div class="form-group" style="margin-top:20px">
                                        <label>Application Name (Brand)</label>
                                        <input type="text" name="app_name" value="<?php echo h(get_setting('app_name', 'BexMedia')); ?>">
                                    </div>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                        Simpan Nama Aplikasi
                                    </button>
                                </div>
                            </form>

                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                                <div class="form-section">
                                    <h3>Running Text Configuration</h3>
                                    <div class="form-group">
                                        <label>Teks Berjalan (Marquee Text)</label>
                                        <input type="text" name="running_text" value="<?php echo h(get_setting('running_text')); ?>" placeholder="Masukkan teks pengumuman/selamat datang...">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Kecepatan (1-50, semakin besar semakin cepat)</label>
                                            <input type="number" name="rt_speed" value="<?php echo h(get_setting('rt_speed', '10')); ?>" min="1" max="50">
                                        </div>
                                        <div class="form-group">
                                            <label>Ukuran Font (Pixel)</label>
                                            <input type="number" name="rt_font_size" value="<?php echo h(get_setting('rt_font_size', '16')); ?>" min="8" max="72">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Jenis Font (Font Family)</label>
                                            <select name="rt_font_family">
                                                <?php 
                                                $current_font = get_setting('rt_font_family', "'Inter', sans-serif");
                                                $fonts = [
                                                    "'Inter', sans-serif" => "Inter (Standard)",
                                                    "'Outfit', sans-serif" => "Outfit (Premium)",
                                                    "'Montserrat', sans-serif" => "Montserrat (Modern)",
                                                    "'Poppins', sans-serif" => "Poppins (Clean)",
                                                    "'Playfair Display', serif" => "Playfair (Elegant)",
                                                    "'Ubuntu', sans-serif" => "Ubuntu (Style)",
                                                    "'Open Sans', sans-serif" => "Open Sans (Readable)",
                                                    "'Roboto', sans-serif" => "Roboto",
                                                    "Arial, sans-serif" => "Arial",
                                                    "'Courier New', monospace" => "Monospace"
                                                ];
                                                foreach ($fonts as $val => $label) {
                                                    $sel = ($current_font == $val) ? "selected" : "";
                                                    echo "<option value=\"$val\" $sel>$label</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Warna Font</label>
                                            <div style="display:flex; gap:12px; align-items:center;">
                                                <input type="color" name="rt_color" value="<?php echo h(get_setting('rt_color', '#1e3a8a')); ?>">
                                                <span style="font-family:monospace; color:#64748B; font-weight:600;"><?php echo h(get_setting('rt_color', '#1e3a8a')); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                        Simpan Running Text
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB: CONNECTION -->
                        <div id="connection" class="tab-content">
                            <div class="form-section">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #EEE; padding-bottom: 10px;">
                                    <h3 style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">Database Khanza (Remote Server)</h3>
                                    <?php if ($khanza_status === 'online'): ?>
                                        <span id="khanzaStatusBadge" class="badge badge-success" style="transition: all 0.3s;"><i data-lucide="check" size="12" style="vertical-align:middle; margin-right:4px"></i> Connected</span>
                                    <?php else: ?>
                                        <span id="khanzaStatusBadge" class="badge badge-warn" style="background:#FEE2E2; color:#B91C1C; transition: all 0.3s;"><i data-lucide="x" size="12" style="vertical-align:middle; margin-right:4px"></i> Disconnected</span>
                                    <?php endif; ?>
                                </div>
                                <form method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="save_settings" value="1">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Hostname / IP</label>
                                            <input type="text" name="host_khanza" id="host_khanza" value="<?php echo h(get_setting('host_khanza')); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Database Name</label>
                                            <input type="text" name="name_khanza" id="name_khanza" value="<?php echo h(get_setting('name_khanza')); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text" name="user_khanza" id="user_khanza" value="<?php echo h(get_setting('user_khanza')); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" name="pass_khanza" id="pass_khanza" value="<?php echo h(get_setting('pass_khanza')); ?>">
                                        </div>
                                    </div>
                                    <div style="margin-top:-10px; margin-bottom:20px; display: flex; gap: 10px;">
                                        <button type="button" id="btnTestKhanza" class="btn-save" style="background:#F1F5F9; color:#475569; padding:10px 18px; font-size:0.8rem; border:1px solid #E2E8F0; display:flex; align-items:center;" onclick="testKhanzaConnection()">
                                            <i data-lucide="refresh-cw" size="14" style="margin-right:6px"></i> Tes Koneksi Remote
                                        </button>
                                        <span id="khanzaDot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo ($khanza_status === 'online' ? '#10B981' : '#EF4444'); ?>; margin-left:5px; transition: background 0.3s;" title="Status Koneksi"></span>
                                        <button type="submit" class="btn-save" style="padding:10px 18px; font-size:0.8rem;">
                                            <i data-lucide="save" size="14" style="vertical-align:middle; margin-right:6px"></i> Simpan Konfigurasi Remote
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="form-section">
                                <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #EEE; padding-bottom: 10px;">
                                    <h3 style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">Database BexMedia (Local Server)</h3>
                                    <?php if ($bex_status === 'online'): ?>
                                        <span id="bexStatusBadge" class="badge badge-success" style="transition: all 0.3s;"><i data-lucide="check" size="12" style="vertical-align:middle; margin-right:4px"></i> Connected</span>
                                    <?php else: ?>
                                        <span id="bexStatusBadge" class="badge badge-warn" style="background:#FEE2E2; color:#B91C1C; transition: all 0.3s;"><i data-lucide="x" size="12" style="vertical-align:middle; margin-right:4px"></i> Disconnected</span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Hostname</label>
                                        <input type="text" name="host_bex" id="host_bex" value="<?php echo h(get_setting('host_bex')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Database Name</label>
                                        <input type="text" name="name_bex" id="name_bex" value="<?php echo h(get_setting('name_bex')); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="user_bex" id="user_bex" value="<?php echo h(get_setting('user_bex')); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="pass_bex" id="pass_bex" value="<?php echo h(get_setting('pass_bex')); ?>">
                                    </div>
                                </div>
                                <div style="margin-top:-10px; margin-bottom:20px; display: flex; gap: 10px;">
                                    <button type="button" id="btnTestBex" class="btn-save" style="background:#F1F5F9; color:#475569; padding:10px 18px; font-size:0.8rem; border:1px solid #E2E8F0; display:flex; align-items:center;" onclick="testBexConnection()">
                                        <i data-lucide="refresh-cw" size="14" style="margin-right:6px"></i> Tes Koneksi Lokal
                                    </button>
                                    <span id="bexDot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo ($bex_status === 'online' ? '#10B981' : '#EF4444'); ?>; margin-left:5px; transition: background 0.3s;" title="Status Koneksi"></span>
                                    <button type="submit" name="save_settings" class="btn-save" style="padding:10px 18px; font-size:0.8rem;">
                                        <i data-lucide="save" size="14" style="vertical-align:middle; margin-right:6px"></i> Simpan Konfigurasi Lokal
                                    </button>
                                </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB: EMAIL ENGINE -->
                        <div id="email" class="tab-content">
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_mail" value="1">
                                <input type="hidden" name="mail_id" value="<?php echo $mail_setting['id'] ?? 0; ?>">
                                <?php
                                $mail_setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mail_settings LIMIT 1"));
                                ?>
                                <div class="form-section">
                                    <h3>Email Engine (SMTP Config)</h3>
                                    <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 25px;">
                                        Konfigurasi SMTP server untuk pengiriman email otomatis (laporan audit, notifikasi sistem).
                                    </p>

                                    <div style="background: #F0F9FF; border-left: 4px solid var(--primary); border-radius: 0 12px 12px 0; padding: 14px 18px; margin-bottom: 25px; font-size: 0.85rem; color: #0369a1;">
                                        <i data-lucide="info" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                        Untuk Gmail, gunakan SMTP <strong>smtp.gmail.com</strong> port <strong>587</strong> dan gunakan <strong>App Password</strong> (bukan password biasa).
                                    </div>

                                    <div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>SMTP Host</label>
                                                <input type="text" name="mail_host" value="<?php echo htmlspecialchars($mail_setting['mail_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                                            </div>
                                            <div class="form-group">
                                                <label>SMTP Port</label>
                                                <input type="number" name="mail_port" value="<?php echo htmlspecialchars($mail_setting['mail_port'] ?? '587'); ?>" placeholder="587">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Username (Email)</label>
                                                <input type="text" name="mail_username" value="<?php echo htmlspecialchars($mail_setting['mail_username'] ?? ''); ?>" placeholder="yourname@gmail.com">
                                            </div>
                                            <div class="form-group">
                                                <label>App Password</label>
                                                <input type="password" name="mail_password" value="<?php echo htmlspecialchars($mail_setting['mail_password'] ?? ''); ?>" placeholder="••••••••••••">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>From Email</label>
                                                <input type="email" name="mail_from_email" value="<?php echo htmlspecialchars($mail_setting['mail_from_email'] ?? ''); ?>" placeholder="noreply@bexmedia.id">
                                            </div>
                                            <div class="form-group">
                                                <label>From Name</label>
                                                <input type="text" name="mail_from_name" value="<?php echo htmlspecialchars($mail_setting['mail_from_name'] ?? ''); ?>" placeholder="BexMedia System">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Base URL Aplikasi</label>
                                            <input type="text" name="base_url" value="<?php echo htmlspecialchars($mail_setting['base_url'] ?? ''); ?>" placeholder="http://localhost/bexmedia">
                                        </div>

                                        <button type="submit" class="btn-save">
                                            <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                            Simpan Email Engine
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- TAB: NOTIFICATION GATEWAY -->
                        <div id="notifications" class="tab-content">
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                                <div class="form-section">
                                    <h3>WhatsApp Gateway Configuration</h3>
                                    <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 25px;">
                                        Configure the WhatsApp gateway to send automated alerts via WA Bot.
                                    </p>
                                    
                                    <div class="form-group">
                                        <label>WhatsApp Gateway URL</label>
                                        <input type="text" name="wa_gateway_url" value="<?php echo h(get_setting('wa_gateway_url')); ?>" placeholder="https://api.whatsapp.id/send">
                                    </div>
                                    <div class="form-group">
                                        <label>Default WhatsApp Number (Admin/Test)</label>
                                        <input type="text" name="wa_number" value="<?php echo h(get_setting('wa_number')); ?>" placeholder="628123456789">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>WA Group ID - IT Support</label>
                                            <input type="text" name="wa_group_it" value="<?php echo h(get_setting('wa_group_it')); ?>" placeholder="Enter WA Group ID for IT">
                                        </div>
                                        <div class="form-group">
                                            <label>WA Group ID - Sarpras</label>
                                            <input type="text" name="wa_group_sarpras" value="<?php echo h(get_setting('wa_group_sarpras')); ?>" placeholder="Enter WA Group ID for Sarpras">
                                        </div>
                                    </div>

                                    <!-- Added Database Configuration for WA Gateway -->
                                    <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                        <h4 style="font-size: 1rem; margin-bottom: 15px; color: #1e293b;"><i data-lucide="database" size="16" style="vertical-align: middle; margin-right: 8px;"></i> Database Queue Configuration</h4>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>DB Host</label>
                                                <input type="text" name="wa_db_host" value="<?php echo h(get_setting('wa_db_host', '192.20.20.234')); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>DB Name</label>
                                                <input type="text" name="wa_db_name" value="<?php echo h(get_setting('wa_db_name', 'wa_gateway')); ?>">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>DB Username</label>
                                                <input type="text" name="wa_db_user" value="<?php echo h(get_setting('wa_db_user', 'simrs')); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>DB Password</label>
                                                <input type="password" name="wa_db_pass" value="<?php echo h(get_setting('wa_db_pass')); ?>" placeholder="••••••••">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                        Simpan Gateway WhatsApp
                                    </button>
                                </div>
                            </form>

                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="save_settings" value="1">
                                <div class="form-section" style="margin-top: 40px;">
                                    <h3>Telegram Notification Configuration</h3>
                                    <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 25px;">
                                        Configure the Telegram Bot token and targets for secondary alerts.
                                    </p>
                                    
                                    <div class="form-group">
                                        <label>Telegram Bot Token</label>
                                        <input type="text" name="telegram_bot_token" value="<?php echo h(get_setting('telegram_bot_token')); ?>" placeholder="000000000:AAHHHxxxx...">
                                    </div>
                                    <div class="form-group">
                                        <label>Telegram Chat ID (Target)</label>
                                        <input type="text" name="telegram_chat_id" value="<?php echo h(get_setting('telegram_chat_id')); ?>" placeholder="-100xxxxxxx">
                                    </div>
                                    <button type="submit" class="btn-save">
                                        <i data-lucide="save" size="16" style="vertical-align: middle; margin-right: 6px;"></i>
                                        Simpan Gateway Telegram
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB: ACCESS RIGHTS -->
                        <div id="access" class="tab-content">
                            <div class="form-section">
                                <h3>User Access Control</h3>
                                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 20px;">
                                    Klik tombol <strong>Akses</strong> untuk mengatur izin menu bagi setiap pengguna.
                                </p>
                                
                                <div style="overflow-x: auto;">
                                    <table class="log-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Lengkap</th>
                                                <th>Username</th>
                                                <th>Jabatan</th>
                                                <th>Unit Kerja</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $user_list = mysqli_query($conn, "SELECT id, nama_lengkap, AES_DECRYPT(username, 'bex') as uname, nik, jabatan, unit_kerja FROM users WHERE status = 'active'");
                                            while ($ul = mysqli_fetch_assoc($user_list)):
                                            ?>
                                            <tr>
                                                <td><strong><?php echo h($ul['nama_lengkap']); ?></strong></td>
                                                <td><code><?php echo h($ul['uname']); ?></code></td>
                                                <td><?php echo h($ul['jabatan']); ?></td>
                                                <td><?php echo h($ul['unit_kerja']); ?></td>
                                                <td>
                                                    <button type="button" class="btn-save" style="padding: 6px 12px; font-size: 0.75rem; display: flex; align-items: center; gap: 5px;" onclick="openAccessModal('<?php echo h($ul['uname']); ?>', '<?php echo addslashes($ul['nama_lengkap']); ?>')">
                                                        <i data-lucide="shield" size="14"></i> Akses
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: USER LIST -->
                        <div id="userlist" class="tab-content">
                            <div class="form-section">
                                <h3>User Account List</h3>
                                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 20px;">
                                    Daftar seluruh akun yang terdaftar di sistem beserta status aktif saat ini.
                                </p>
                                
                                <div style="overflow-x: auto;">
                                    <table class="log-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Lengkap</th>
                                                <th>Username</th>
                                                <th>Jabatan</th>
                                                <th>Status Akun</th>
                                                <th>Status Login</th>
                                                <th>Last Activity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $all_users = mysqli_query($conn, "SELECT id, nama_lengkap, AES_DECRYPT(username, 'bex') as uname, jabatan, last_activity, status FROM users ORDER BY last_activity DESC");
                                            while ($au = mysqli_fetch_assoc($all_users)):
                                                // Status Online: Aktif dalam 5 menit terakhir
                                                $is_online = (!empty($au['last_activity']) && strtotime($au['last_activity']) > (time() - 300));
                                                $status_label = $is_online ? "Online" : "Offline";
                                                $status_badge = $is_online ? "badge-success" : "badge-offline";
                                                
                                                // Status Akun
                                                $acc_status = ucfirst($au['status']);
                                                $acc_badge = ($au['status'] == 'active') ? "badge-success" : "badge-warn";
                                            ?>
                                            <tr>
                                                <td><strong><?php echo h($au['nama_lengkap']); ?></strong></td>
                                                <td><code style="background: #F8FAFC; padding: 2px 6px; border-radius: 4px;"><?php echo h($au['uname']); ?></code></td>
                                                <td><?php echo h($au['jabatan']); ?></td>
                                                <td><span class="badge <?php echo $acc_badge; ?>"><?php echo $acc_status; ?></span></td>
                                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_label; ?></span></td>
                                                <td style="font-size: 0.75rem; color: #94A3B8;"><?php echo $au['last_activity'] ? $au['last_activity'] : 'Never'; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: CONFIRM ACCOUNT -->
                        <div id="confirm" class="tab-content">
                            <div class="form-section">
                                <h3>Account Confirmation (Pending)</h3>
                                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 20px;">
                                    Daftar pengguna baru yang menunggu aktivasi akun untuk dapat mengakses sistem.
                                </p>
                                
                                <div style="overflow-x: auto;">
                                    <table class="log-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Lengkap</th>
                                                <th>NIK</th>
                                                <th>Unit Kerja</th>
                                                <th>Email</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $pending_users = mysqli_query($conn, "SELECT id, nama_lengkap, nik, unit_kerja, email FROM users WHERE status = 'pending' ORDER BY created_at DESC");
                                            if (mysqli_num_rows($pending_users) == 0):
                                            ?>
                                                <tr><td colspan="5" style="text-align:center; padding: 40px; color: #94A3B8;">Tidak ada akun yang menunggu konfirmasi.</td></tr>
                                            <?php else: ?>
                                                <?php while ($pu = mysqli_fetch_assoc($pending_users)): ?>
                                                <tr>
                                                    <td><strong><?php echo h($pu['nama_lengkap']); ?></strong></td>
                                                    <td><?php echo h($pu['nik']); ?></td>
                                                    <td><?php echo h($pu['unit_kerja']); ?></td>
                                                    <td><?php echo h($pu['email']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn-save" style="padding: 6px 12px; font-size: 0.75rem; background: #10B981;" onclick="activateUser(<?php echo $pu['id']; ?>, '<?php echo addslashes($pu['nama_lengkap']); ?>')">Aktifkan</button>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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

                        <!-- TAB: INTEGRITY GUARD -->
                        <div id="integrity" class="tab-content">
                            <div class="form-section">
                                <h3>Performance Guard</h3>
                                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 25px;">
                                    Sistem perlindungan integritas file inti dan validasi aset digital BexMedia.
                                </p>

                                <div style="background: rgba(59,130,246,0.03); border: 1px dashed rgba(59,130,246,0.3); border-radius: 20px; padding: 35px; text-align: center; margin-bottom: 30px;">
                                    <div style="background: white; width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 20px rgba(59,130,246,0.1);">
                                        <i data-lucide="shield-check" style="color: var(--primary)" size="30"></i>
                                    </div>
                                    <h4 style="font-family: 'Outfit', sans-serif; font-weight: 800; letter-spacing: -0.01em; margin-bottom: 10px;">Security Manifest Console</h4>
                                    <p style="color: #64748B; font-size: 0.85rem; max-width: 420px; margin: 0 auto 25px; line-height: 1.7;">
                                        Jika Anda melakukan perubahan sah pada file <strong>sidebar.php</strong> atau <strong>footer.php</strong>, gunakan konsol ini untuk mensinkronkan ulang sidik jari keamanan agar sistem tidak terblokir.
                                    </p>
                                    <a href="generate_hash_asli.php" class="btn-save" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px;">
                                        <i data-lucide="external-link" size="16"></i> Buka Integrity Console
                                    </a>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div style="padding: 20px; background: #F8FAFC; border-radius: 16px; border: 1px solid #E2E8F0;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 600; font-size: 0.9rem;">sidebar.php</span>
                                            <span class="badge badge-success">Protected</span>
                                        </div>
                                        <div style="margin-top: 8px; font-size: 0.75rem; color: #94A3B8;">Core Navigation Matrix</div>
                                    </div>
                                    <div style="padding: 20px; background: #F8FAFC; border-radius: 16px; border: 1px solid #E2E8F0;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 600; font-size: 0.9rem;">footer.php</span>
                                            <span class="badge badge-success">Protected</span>
                                        </div>
                                        <div style="margin-top: 8px; font-size: 0.75rem; color: #94A3B8;">Legal &amp; Copyright Entity</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- The saveContainer is removed as each section now has its own submit button -->
                    </div>
                </div>

                <!-- Hidden Form for Activation (Moved outside to fix nesting issue) -->
                <form id="activateForm" method="POST" style="display:none;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" id="activateUserId">
                    <input type="hidden" name="activate_user" value="1">
                </form>

                <!-- Access Modal -->
                <div id="accessModal" class="modal-overlay">
                    <div class="modal-box">
                        <div class="modal-header">
                            <h2 id="modalTitle" style="font-family:'Outfit',sans-serif; margin:0;">Hak Akses Menu</h2>
                            <i data-lucide="x" style="cursor:pointer" onclick="closeAccessModal()"></i>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="target_username" id="targetUsername">
                            <input type="hidden" name="update_access" value="1">
                            
                            <p style="color:#64748B; font-size:0.9rem">Beri tanda centang pada menu yang boleh diakses oleh user ini.</p>
                            
                            <div class="menu-check-grid" id="menuGrid">
                                <!-- Loaded via JS -->
                            </div>

                            <div style="margin-top:30px; display:flex; justify-content:flex-end; gap:10px;">
                                <button type="button" class="btn-save" style="background:#64748B" onclick="closeAccessModal()">Batal</button>
                                <button type="submit" class="btn-save">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Auto-open tab from URL param
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
            const content = document.getElementById(activeTab);
            if (content) content.classList.add('active');
            
            // Find corresponding nav item
            const navItems = document.querySelectorAll('.settings-nav-item');
            navItems.forEach(item => {
                if (item.getAttribute('onclick') && item.getAttribute('onclick').includes(activeTab)) {
                    item.classList.add('active');
                    // If it's in a sub-menu, open it
                    if (item.closest('.nav-sub')) {
                        item.closest('.nav-sub').classList.add('active');
                    }
                }
            });
        }

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
        }

        function toggleSub(id) {
            const sub = document.getElementById(id);
            const trigger = event.currentTarget;
            sub.classList.toggle('active');
            trigger.classList.toggle('active');
        }

        function activateUser(id, name) {
            Swal.fire({
                title: 'Aktivasi Akun?',
                text: "Apakah Anda yakin ingin mengaktifkan akun " + name + "?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Ya, Aktifkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('activateUserId').value = id;
                    document.getElementById('activateForm').submit();
                }
            })
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

        // ACCESS MODAL LOGIC
        const allMenus = <?php 
            $menus = [];
            $res_m = mysqli_query($conn, "SELECT id, display_name FROM web_menus ORDER BY display_name");
            while($m = mysqli_fetch_assoc($res_m)) $menus[] = $m;
            echo json_encode($menus);
        ?>;

        function openAccessModal(username, fullName) {
            document.getElementById('targetUsername').value = username;
            document.getElementById('modalTitle').innerText = "Hak Akses: " + fullName;
            
            // Get current access (AJAX)
            fetch('?get_access=' + encodeURIComponent(username))
                .then(res => res.json())
                .then(data => {
                    const grid = document.getElementById('menuGrid');
                    grid.innerHTML = '';
                    
                    allMenus.forEach(menu => {
                        const isChecked = data.includes(parseInt(menu.id)) ? 'checked' : '';
                        grid.innerHTML += `
                            <label class="menu-check-item">
                                <input type="checkbox" name="menus[]" value="${menu.id}" ${isChecked}>
                                <span style="font-size:0.9rem; font-weight:500;">${menu.display_name}</span>
                            </label>
                        `;
                    });
                    
                    document.getElementById('accessModal').classList.add('active');
                });
        }

        function closeAccessModal() {
            document.getElementById('accessModal').classList.remove('active');
        }

        function testKhanzaConnection() {
            const host = document.getElementById('host_khanza').value;
            const name = document.getElementById('name_khanza').value;
            const user = document.getElementById('user_khanza').value;
            const pass = document.getElementById('pass_khanza').value;
            
            const btn = document.getElementById('btnTestKhanza');
            const originalHTML = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin" size="14" style="vertical-align:middle; margin-right:6px"></i> Menghubungkan...';
            lucide.createIcons();
            
            $.ajax({
                url: 'ajax_test_db.php',
                method: 'POST',
                data: { host: host, name: name, user: user, pass: pass },
                success: function(res) {
                    const badge = document.getElementById('khanzaStatusBadge');
                    const dot = document.getElementById('khanzaDot');
                    if (badge) badge.style.display = 'inline-block';
                    
                    if (res.success) {
                        if (badge) {
                            badge.className = 'badge badge-success';
                            badge.style.background = ''; 
                            badge.style.color = '';
                            badge.innerHTML = '<i data-lucide="check" size="12" style="vertical-align:middle; margin-right:4px"></i> Connected';
                        }
                        if (dot) dot.style.background = '#10B981';
                        Swal.fire({ title: 'Koneksi Berhasil!', text: res.message, icon: 'success', confirmButtonColor: '#3B82F6' });
                    } else {
                        if (badge) {
                            badge.className = 'badge badge-warn';
                            badge.style.background = '#FEE2E2';
                            badge.style.color = '#B91C1C';
                            badge.innerHTML = '<i data-lucide="x" size="12" style="vertical-align:middle; margin-right:4px"></i> Disconnected';
                        }
                        if (dot) dot.style.background = '#EF4444';
                        Swal.fire({ title: 'Koneksi Gagal', text: res.message, icon: 'error', confirmButtonColor: '#EF4444' });
                    }
                    lucide.createIcons();
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan sistem saat mencoba koneksi.', 'error');
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    lucide.createIcons();
                }
            });
        }

        function testBexConnection() {
            const host = document.getElementById('host_bex').value;
            const name = document.getElementById('name_bex').value;
            const user = document.getElementById('user_bex').value;
            const pass = document.getElementById('pass_bex').value;
            
            const btn = document.getElementById('btnTestBex');
            const originalHTML = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin" size="14" style="vertical-align:middle; margin-right:6px"></i> Menghubungkan...';
            lucide.createIcons();
            
            $.ajax({
                url: 'ajax_test_db.php',
                method: 'POST',
                data: { host: host, name: name, user: user, pass: pass },
                success: function(res) {
                    const badge = document.getElementById('bexStatusBadge');
                    const dot = document.getElementById('bexDot');
                    if (badge) badge.style.display = 'inline-block';
                    
                    if (res.success) {
                        if (badge) {
                            badge.className = 'badge badge-success';
                            badge.style.background = ''; 
                            badge.style.color = '';
                            badge.innerHTML = '<i data-lucide="check" size="12" style="vertical-align:middle; margin-right:4px"></i> Connected';
                        }
                        if (dot) dot.style.background = '#10B981';
                        Swal.fire({ title: 'Koneksi Berhasil!', text: res.message, icon: 'success', confirmButtonColor: '#3B82F6' });
                    } else {
                        if (badge) {
                            badge.className = 'badge badge-warn';
                            badge.style.background = '#FEE2E2';
                            badge.style.color = '#B91C1C';
                            badge.innerHTML = '<i data-lucide="x" size="12" style="vertical-align:middle; margin-right:4px"></i> Disconnected';
                        }
                        if (dot) dot.style.background = '#EF4444';
                        Swal.fire({ title: 'Koneksi Gagal', text: res.message, icon: 'error', confirmButtonColor: '#EF4444' });
                    }
                    lucide.createIcons();
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan sistem saat mencoba koneksi.', 'error');
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    lucide.createIcons();
                }
            });
        }
    </script>

    <!-- Dynamic Footer (Marquee & Copyright) -->
    <?php include "footer.php"; ?>
</body>
</html>







