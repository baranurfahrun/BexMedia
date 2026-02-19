<?php
// === KONFIGURASI KEAMANAN & DATABASE (ADAPTASI WEB_DOKTER) ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();
date_default_timezone_set("Asia/Makassar");

$db_hostname = "localhost";
$db_username = "root";
$db_password = "";
$db_name     = "bexmedia";

$conn = mysqli_connect($db_hostname, $db_username, $db_password);
if (!$conn) die("Koneksi Server Gagal!");

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
mysqli_select_db($conn, $db_name);

// Cek apakah kolom username sudah VARBINARY, jika tdk drop (Reset untuk Migrasi)
$check_type = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field = 'username'");
$type_info = mysqli_fetch_assoc($check_type);
if ($type_info && strpos(strtolower($type_info['Type']), 'varbinary') === false) {
    mysqli_query($conn, "DROP TABLE users");
}

// Re-Create Tabel dengan tipe VARBINARY agar support AES Encrypt ala Khanza
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARBINARY(255) NOT NULL UNIQUE,
    password VARBINARY(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cek & Buat Admin Default jika kosong
$check_admin = mysqli_query($conn, "SELECT id FROM users");
if (mysqli_num_rows($check_admin) == 0) {
    // Password admin default: 'admin123' dienkripsi AES (key: 'bex' & 'bara')
    mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap) VALUES 
        (AES_ENCRYPT('admin', 'bex'), AES_ENCRYPT('admin123', 'bara'), 'Administrator BexMedia')");
}

// Helper Keamanan
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
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
        die('Validasi CSRF Gagal! Silakan refresh halaman.');
    }
    return true;
}

function validTeks($data) {
    $save = str_replace("'", "", $data);
    $save = str_replace("\\", "", $save);
    $save = str_replace(";", "", $save);
    $save = str_replace("`", "", $save);
    $save = str_replace("--", "", $save);
    $save = str_replace("/*", "", $save);
    $save = str_replace("*/", "", $save);
    $save = str_replace("#", "", $save);
    return $save;
}

function cleanInput($data) {
    global $conn;
    $clean = mysqli_real_escape_string($conn, $data);
    return validTeks($clean);
}

function bukaquery($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql) or die(mysqli_error($conn));
    return $result;
}
?>
