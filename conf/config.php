<?php
// --- SESSION HYGIENE & PROTECTION ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}
date_default_timezone_set("Asia/Makassar");

// --- SESSION HYGIENE & PROTECTION ---
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// --- SECURITY HEADERS (PRO-LEAD ARCHITECTURE) ---
function send_security_headers() {
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'none';");
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}
send_security_headers();

// --- SESSION SECURITY ---
// Session Regeneration
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Auto Logout (30 Menit) - DISABLED FOR DEVELOPMENT
/*
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    
    // Deteksi apakah file yang memanggil config ini berada di folder 'dist'
    $is_in_dist = (strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']), '/dist/') !== false);
    $login_path = $is_in_dist ? "../login.php" : "login.php";
    
    header("Location: " . $login_path . "?timeout=true");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();
*/

// --- ERROR HANDLING (PRODUCTION MODE) ---
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// --- INITIAL CONNECTION (BOOTSTRAP) ---
// Default credentials untuk bootmup awal (Bisa disesuaikan sbg fallback)
$tmp_host = "localhost";
$tmp_user = "root";
$tmp_pass = "";
$tmp_name = "bexmedia";

$conn = mysqli_connect($tmp_host, $tmp_user, $tmp_pass);
if ($conn) {
    mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $tmp_name");
    mysqli_select_db($conn, $tmp_name);
}

// --- LOAD SETTINGS DARI DATABASE BEXMEDIA ---
function get_setting($key, $default = "") {
    global $conn;
    if (!$conn) return $default;
    $res = mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = '$key'");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row['setting_value'];
    }
    return $default;
}

// --- 1. CONFIG DATABASE KHANZA (REMOTE SERVER) ---
$host_khanza = get_setting('host_khanza', "192.20.20.253"); 
$user_khanza = get_setting('user_khanza', "root");
$pass_khanza = get_setting('pass_khanza', "root");
$name_khanza = get_setting('name_khanza', "sik");

// --- 2. CONFIG DATABASE BEXMEDIA (LOCAL/INTERNAL SERVER) ---
$host_bex    = get_setting('host_bex', "localhost"); 
$user_bex    = get_setting('user_bex', "root");
$pass_bex    = get_setting('pass_bex', "");
$name_bex    = get_setting('name_bex', "bexmedia");

// Note: Re-connect dengan setting baru (jika berbeda dari bootstrap)
if ($host_bex != $tmp_host || $user_bex != $tmp_user || $pass_bex != $tmp_pass || $name_bex != $tmp_name) {
    if ($conn) mysqli_close($conn);
    $conn = mysqli_connect($host_bex, $user_bex, $pass_bex, $name_bex);
}

// --- USER ACTIVITY TRACKING (MUST BE AFTER DB CONNECTION) ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($conn)) {
    mysqli_query($conn, "UPDATE users SET last_activity = NOW() WHERE username = AES_ENCRYPT('" . $_SESSION['username'] . "', 'bex')");
}

if (!$conn) die("Gagal koneksi ke DB INTERNAL BEXMEDIA!");
mysqli_query($conn, "SET NAMES 'latin1'");

// --- KONEKSI KHANZA (REMOTE SERVER) ---
// Logika skip cerdas: jika pernah gagal, jangan coba lagi selama 5 menit agar tidak lemot
$is_settings_page = (strpos($_SERVER['SCRIPT_NAME'], 'settings.php') !== false);
$khanza_down_time = $_SESSION['khanza_down_until'] ?? 0;

if (!$is_settings_page && time() < $khanza_down_time) {
    // Skip koneksi karena sedang masa hukuman (down)
    $conn_sik = false;
} else {
    $conn_sik = mysqli_init();
    mysqli_options($conn_sik, MYSQLI_OPT_CONNECT_TIMEOUT, 2); 
    $sik_connected = @mysqli_real_connect($conn_sik, $host_khanza, $user_khanza, $pass_khanza, $name_khanza);

    if ($sik_connected) {
        mysqli_query($conn_sik, "SET NAMES 'latin1'");
        unset($_SESSION['khanza_down_until']); // Reset jika sukses
    } else {
        $conn_sik = false;
        // Jika gagal, jangan coba lagi selama 5 menit (kecuali di halaman settings)
        if (!$is_settings_page) {
            $_SESSION['khanza_down_until'] = time() + 300; 
        }
    }
}

// Migrasi Tabel
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARBINARY(255) NOT NULL UNIQUE,
    password VARBINARY(255) NOT NULL,
    nik VARCHAR(30) UNIQUE,
    nama_lengkap VARCHAR(100),
    jabatan VARCHAR(100),
    unit_kerja VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    photo VARCHAR(255),
    atasan_id INT,
    status ENUM('active', 'pending', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Leave Request Tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengajuan_cuti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    cuti_id INT NOT NULL,
    delegasi_id INT,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    lama_hari INT,
    alasan TEXT,
    status VARCHAR(50) DEFAULT 'Menunggu',
    status_delegasi VARCHAR(50) DEFAULT 'Menunggu',
    status_atasan VARCHAR(50) DEFAULT 'Menunggu',
    status_hrd VARCHAR(50) DEFAULT 'Menunggu',
    acc_delegasi_by VARCHAR(100),
    acc_delegasi_time DATETIME NULL,
    acc_atasan_by VARCHAR(100),
    acc_atasan_time DATETIME NULL,
    acc_hrd_by VARCHAR(100),
    acc_hrd_time DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Patch for existing table
$col_checks = [
    'acc_delegasi_time' => 'acc_delegasi_by',
    'acc_atasan_time'   => 'acc_atasan_by',
    'acc_hrd_time'      => 'acc_hrd_by'
];
foreach ($col_checks as $col => $after) {
    if (mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM pengajuan_cuti LIKE '$col'")) == 0) {
        mysqli_query($conn, "ALTER TABLE pengajuan_cuti ADD COLUMN $col DATETIME NULL AFTER $after");
    }
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengajuan_cuti_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengajuan_id INT NOT NULL,
    tanggal DATE NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengajuan_ganti_jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    pengganti_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_kerja_id INT NOT NULL,
    alasan TEXT,
    status VARCHAR(50) DEFAULT 'Menunggu',
    status_pengganti VARCHAR(50) DEFAULT 'Menunggu',
    status_atasan VARCHAR(50) DEFAULT 'Menunggu',
    status_hrd VARCHAR(50) DEFAULT 'Menunggu',
    acc_pengganti_by VARCHAR(100),
    acc_pengganti_time DATETIME NULL,
    acc_atasan_by VARCHAR(100),
    acc_atasan_time DATETIME NULL,
    acc_hrd_by VARCHAR(100),
    acc_hrd_time DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100)
)");

// Off-Duty Laporan Table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS laporan_off_duty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_tiket VARCHAR(50) UNIQUE,
    nik VARCHAR(30),
    nama VARCHAR(100),
    jabatan VARCHAR(100),
    unit_kerja VARCHAR(100),
    kategori VARCHAR(100),
    petugas VARCHAR(100) DEFAULT '-',
    keterangan TEXT,
    tanggal DATETIME,
    user_id INT,
    nama_input VARCHAR(100),
    status_validasi VARCHAR(50) DEFAULT 'Menunggu',
    catatan_it TEXT,
    tanggal_validasi DATETIME NULL,
    validator_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS jabatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(100) UNIQUE
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS unit_kerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_unit VARCHAR(100) UNIQUE
)");

// Seed Jabatan & Unit if empty
$check_jabatan = mysqli_query($conn, "SELECT id FROM jabatan LIMIT 1");
if (mysqli_num_rows($check_jabatan) == 0) {
    mysqli_query($conn, "INSERT INTO jabatan (nama_jabatan) VALUES 
        ('Dokter Spesialis'), ('Dokter Umum'), ('Perawat'), ('Bidan'), 
        ('Apoteker'), ('Analis Kesehatan'), ('Staf Administrasi'), ('IT Support')");
}

$check_unit = mysqli_query($conn, "SELECT id FROM unit_kerja LIMIT 1");
if (mysqli_num_rows($check_unit) == 0) {
    mysqli_query($conn, "INSERT INTO unit_kerja (nama_unit) VALUES 
        ('Rawat Jalan'), ('Rawat Inap'), ('IGD'), ('Farmasi'), 
        ('Laboratorium'), ('Radiologi'), ('Administrasi'), ('IT')");
}

// Seed Kategori Software if empty
$check_kat_soft = mysqli_query($conn, "SELECT id FROM kategori_software LIMIT 1");
if (mysqli_num_rows($check_kat_soft) == 0) {
    mysqli_query($conn, "INSERT INTO kategori_software (nama_kategori) VALUES 
        ('Operating System'), ('Microsoft Office'), ('SIMRS'), ('Email / Network'), 
        ('Browser / Web'), ('Antivirus / Security'), ('Lain-lain')");
}

// Seed Kategori Hardware if empty
$check_kat_hard = mysqli_query($conn, "SELECT id FROM kategori_hardware LIMIT 1");
if (mysqli_num_rows($check_kat_hard) == 0) {
    mysqli_query($conn, "INSERT INTO kategori_hardware (nama_kategori) VALUES 
        ('Monitor / Display'), ('Keyboard / Mouse'), ('Printer / Scanner'), ('PC / Laptop'), 
        ('Jaringan / Router'), ('CCTV / Security'), ('Lain-lain')");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS web_dokter_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100),
    ip_address VARCHAR(50),
    action VARCHAR(100),
    description TEXT,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
    ip VARCHAR(50) PRIMARY KEY,
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS web_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Cek & Isi Setting Default satu-persatu (Robust Initialization)
$default_settings = [
    'app_name'           => 'BexMedia',
    'host_khanza'        => '192.20.20.253',
    'user_khanza'        => 'root',
    'pass_khanza'        => 'root',
    'name_khanza'        => 'sik',
    'host_bex'           => 'localhost',
    'user_bex'           => 'root',
    'pass_bex'           => '',
    'name_bex'           => 'bexmedia',
    'app_version'        => 'V. 1. - .20.02.2026',
    'telegram_bot_token' => '',
    'telegram_chat_id'   => '',
    'wa_gateway_url'     => '',
    'running_text'       => 'Selamat Datang di Portal BexMedia - Executive Mission Control Dashboard',
    'rt_speed'           => '10',
    'rt_font_size'       => '16',
    'rt_font_family'     => "'Inter', sans-serif",
    'rt_color'           => '#1e3a8a'
];

foreach ($default_settings as $key => $val) {
    mysqli_query($conn, "INSERT IGNORE INTO web_settings (setting_key, setting_value) VALUES ('$key', '$val')");
}

// --- HELPER KEAMANAN ---
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function checkLogin() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        $is_in_dist = (strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']), '/dist/') !== false);
        $login_path = $is_in_dist ? "../login.php" : "login.php";
        header("Location: " . $login_path);
        exit;
    }
    // Auto-check access for every page load (except when in root)
    if (strpos($_SERVER['PHP_SELF'], '/dist/') !== false) {
        checkAccess();
    }
}

function syncMenus() {
    global $conn;
    $dist_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "dist";
    if (!is_dir($dist_path)) return;

    $files = scandir($dist_path);
    $active_files = [];

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'logout.php') {
            $full_path = $dist_path . DIRECTORY_SEPARATOR . $file;
            $content = file_get_contents($full_path);
            
            // Mencari "// MenuName: Nama Menu"
            $display_name = $file;
            if (preg_match('/\/\/ MenuName:\s*(.*)/', $content, $matches)) {
                $display_name = trim($matches[1]);
            }
            
            $active_files[] = $file;
            
            // Insert or update
            safe_query("INSERT INTO web_menus (file_name, display_name) VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE display_name = ?", [$file, $display_name, $display_name]);
        }
    }
    
    // Optional: Bersihkan menu yang filenya sudah dihapus
    if (!empty($active_files)) {
        $placeholders = implode(',', array_fill(0, count($active_files), '?'));
        safe_query("DELETE FROM web_menus WHERE file_name NOT IN ($placeholders)", $active_files);
    }
}

function checkAccess() {
    global $conn;
    $current_file = basename($_SERVER['PHP_SELF']);
    $username = $_SESSION['username'] ?? '';
    
    // 0. Bypass untuk file AJAX/utility (tidak perlu dicek di web_menus)
    $ajax_whitelist = [
        'ajax_get_karyawan.php', 'update_karyawan.php',
        'ajax_get_karyawan.php', 'ajax_generate_jadwal.php',
        'ajax_get_employees_by_unit.php', 'ajax_save_jadwal_v2.php',
        'ajax_add_unit.php', 'ajax_update_shifts.php', 'ajax_preview_nomor.php',
        'ajax_test_db.php', 'get_petugas.php', 'get_petunjuk.php',
        'get_online_users.php', 'get_capaian_ajax.php', 'get_capaian_imp.php',
        'get_capaian_imut_rs.php', 'get_pph_persen.php',
        'simpan_off_duty.php', 'update_status_off_duty.php',
        'update_karyawan.php', 'kirim_wa.php', 'kirim_pesan.php',
        'hapus_data.php', 'hapus_dokumen.php', 'hapus_barang.php',
        'hapus_barang_ac.php', 'hapus_edaran.php', 'hapus_spo.php',
        'hapus_undangan.php', 'proses_acc.php', 'ubah_status.php',
        'ubah_status_it_hardware.php', 'ubah_status_it_software.php',
        'ubah_status_sarpras.php', 'ubah_status_pelamar.php',
        'batal_cuti.php', 'selesai_tiket.php', 'simpan_akses_menu.php',
        'load_chat.php', 'cek_status_ajax.php', 'captcha.php',
        'generate_qr.php', 'download_tte.php', 'proses_lihat_jawaban.php',
        'simpan_jawaban.php', 'save_capaian.php', 'update_logbook.php',
        'send_wa.php', 'send_wa_grup.php', 'notif_wa_finish.php',
    ];
    if (in_array($current_file, $ajax_whitelist)) return true;

    // 1. Bypass untuk Super Admin (Bara N.Fahrun / Sesuai Database)
    if ($username === 'admin' || $username === 'bara') return true; 

    // 2. Cek apakah menu terdaftar
    $res_menu = safe_query("SELECT id FROM web_menus WHERE file_name = ?", [$current_file]);
    if ($row_menu = mysqli_fetch_assoc($res_menu)) {
        $menu_id = $row_menu['id'];
        
        // 3. Cek izin akses
        $res_access = safe_query("SELECT id FROM web_access WHERE username = ? AND menu_id = ?", [$username, $menu_id]);
        if (mysqli_num_rows($res_access) == 0) {
            // Log penolakan akses
            write_log("ACCESS_DENIED", "User $username mencoba akses menu $current_file tanpa izin.");
            
            // Tampilan Access Denied
            die('
            <div style="font-family:sans-serif; text-align:center; padding:100px;">
                <h1 style="color:#ef4444;">Akses Ditolak!</h1>
                <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href="index.php" style="color:#3b82f6; text-decoration:none; font-weight:bold;">Kembali ke Dashboard</a>
            </div>');
        }
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        write_log("CSRF_FAILURE", "Percobaan CSRF terdeteksi dari " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));
        die('Validasi CSRF Gagal! Silakan refresh halaman.');
    }
    return true;
}

// SAFE QUERY (PREPARED STATEMENTS)
function safe_query($sql, $params = [], $target_conn = null) {
    global $conn, $conn_sik;
    
    // Gunakan koneksi BexMedia jika tidak ditentukan
    $active_conn = ($target_conn === null) ? $conn : $target_conn;
    
    if (!$active_conn) return false;

    $stmt = mysqli_prepare($active_conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($active_conn) . " SQL: " . $sql);
        return false;
    }

    if (!empty($params)) {
        $types = "";
        foreach ($params as $param) {
            if (is_int($param)) $types .= "i";
            elseif (is_double($param)) $types .= "d";
            elseif (is_string($param)) $types .= "s";
            else $types .= "b";
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    
    // Jika tidak ada result set (misal: INSERT/UPDATE/DELETE), kembalikan true
    if ($result === false && mysqli_stmt_errno($stmt) === 0) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);
    return $result;
}

function write_log($action, $description = "") {
    $user_id = $_SESSION['username'] ?? 'GUEST';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    
    // Log selalu masuk ke database bexmedia ($conn)
    safe_query("INSERT INTO web_dokter_audit_log (user_id, ip_address, action, description, user_agent) 
                VALUES (?, ?, ?, ?, ?)", 
               [$user_id, $ip_address, $action, $description, $user_agent]);
}

function cleanInput($data) {
    $save = str_replace(["'", "\\", ";", "`", "--", "/*", "*/", "#"], "", $data);
    return $save;
}

function bukaquery($sql, $target_conn = null) {
    global $conn, $conn_sik;
    $active_conn = ($target_conn === null) ? $conn : $target_conn;
    return mysqli_query($active_conn, $sql);
}

// --- FUNGSI UTILITY (ADAPTASI DARI WEB_DOKTER) ---
function get_nama_instansi() {
    global $conn_sik;
    static $hospital_name = null;
    if ($hospital_name === null) {
        // Ambil dari database SIK
        $res = safe_query("SELECT nama_instansi FROM setting LIMIT 1", [], $conn_sik);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $hospital_name = $row['nama_instansi'];
        } else {
            $hospital_name = "SIMRS BexMedia";
        }
    }
    return $hospital_name;
}

function konversiTanggal($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return "-";
    $bulan = [
        "01" => "Januari", "02" => "Februari", "03" => "Maret", "04" => "April",
        "05" => "Mei", "06" => "Juni", "07" => "Juli", "08" => "Agustus",
        "09" => "September", "10" => "Oktober", "11" => "Nopember", "12" => "Desember"
    ];
    list($thn, $bln, $tgl) = explode('-', $tanggal);
    return $tgl . " " . ($bulan[$bln] ?? "") . " " . $thn;
}

function formatDuit($duit) {
    return "Rp. " . number_format($duit, 0, ",", ".") . ",-";
}

function getOne($sql, $params = [], $target_conn = null) {
    $res = safe_query($sql, $params, $target_conn);
    if ($res && $row = mysqli_fetch_array($res)) {
        return $row[0];
    }
    return null;
}

// --- FUNGSI KEAMANAN TAMBAHAN (DARI REFERENCE) ---
function encrypt_decrypt($string, $action) {
    $secret_key     = 'BexMediaSecretKey2026';
    $secret_iv      = 'bexmedia_iv_secure';
    $output         = FALSE;
    $encrypt_method = "AES-256-CBC";
    $key            = hash('sha256', $secret_key);
    $iv             = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'e') {
        $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    } else if ($action == 'd') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

function validangka($angka) {
    return (isset($angka) && is_numeric($angka)) ? $angka : 0;
}

function JSRedirect($url) {
    echo "<html><head><title></title><meta http-equiv='refresh' content='1;URL=$url'></head><body></body></html>";
}

function Zet($url) {
    echo "<html><head><title></title><meta http-equiv='refresh' content='0;URL=$url'></head><body></body></html>";
}
?>
