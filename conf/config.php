<?php
// === KONFIGURASI KEAMANAN & DATABASE (ADAPTASI WEB_DOKTER V2.0) ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();
date_default_timezone_set("Asia/Makassar");

// --- SECURITY HEADERS ---
function send_security_headers() {
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com unpkg.com; img-src 'self' data: ui-avatars.com picsum.photos *.picsum.photos; font-src 'self' fonts.gstatic.com; connect-src 'self' unpkg.com; frame-ancestors 'none';");
    
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

// Auto Logout (30 Menit)
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=true");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// --- ERROR HANDLING (PRODUCTION MODE) ---
error_reporting(E_ALL);
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

if (!$conn) die("Gagal koneksi ke DB INTERNAL BEXMEDIA!");
mysqli_query($conn, "SET NAMES 'latin1'");

// --- KONEKSI KHANZA ---
$conn_sik = mysqli_connect($host_khanza, $user_khanza, $pass_khanza, $name_khanza);
if ($conn_sik) {
    mysqli_query($conn_sik, "SET NAMES 'latin1'");
}

// Migrasi Tabel
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARBINARY(255) NOT NULL UNIQUE,
    password VARBINARY(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

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

// Cek & Isi Setting Default jika kosong
$check_settings = mysqli_query($conn, "SELECT setting_key FROM web_settings");
if (mysqli_num_rows($check_settings) == 0) {
    mysqli_query($conn, "INSERT INTO web_settings (setting_key, setting_value) VALUES 
        ('app_name', 'BexMedia'),
        ('host_khanza', '192.20.20.253'),
        ('user_khanza', 'root'),
        ('pass_khanza', 'root'),
        ('name_khanza', 'sik'),
        ('host_bex', 'localhost'),
        ('user_bex', 'root'),
        ('pass_bex', ''),
        ('name_bex', 'bexmedia')");
}

// --- HELPER KEAMANAN ---
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function checkLogin() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: login.php");
        exit;
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
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
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
