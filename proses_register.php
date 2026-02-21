<?php
require_once "conf/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("DEBUG: proses_register.php started");
    csrf_verify();

    $nik = trim(cleanInput($_POST['nik']));
    $nama = trim(cleanInput($_POST['nama']));
    $jabatan = trim(cleanInput($_POST['jabatan']));
    $unit_kerja = trim(cleanInput($_POST['unit_kerja']));
    $email = trim(cleanInput($_POST['email']));
    error_log("DEBUG: Processing register for NIK: $nik, Email: $email");
    
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $atasan_id = !empty($_POST['atasan_id']) ? (int)$_POST['atasan_id'] : null;

    if (empty($nik) || empty($nama) || empty($email) || empty($password)) {
        error_log("DEBUG: Validation failed - Empty fields");
        $_SESSION['login_error'] = "Data wajib diisi semua!";
        header("Location: login.php"); exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("DEBUG: Validation failed - Invalid email: $email");
        $_SESSION['login_error'] = "Format email tidak valid!";
        header("Location: login.php"); exit;
    }

    if (strlen($password) < 8) {
        error_log("DEBUG: Validation failed - Password too short");
        $_SESSION['login_error'] = "Password minimal 8 karakter!";
        header("Location: login.php"); exit;
    }

    if ($password !== $konfirmasi) {
        error_log("DEBUG: Validation failed - Password mismatch");
        $_SESSION['login_error'] = "Konfirmasi password tidak cocok!";
        header("Location: login.php"); exit;
    }

    // CEK DUPLIKASI
    $check_email = safe_query("SELECT id FROM users WHERE email = ? OR username = AES_ENCRYPT(?, 'bex')", [$email, $email]);
    if ($check_email === false) {
        error_log("DEBUG: Duplicate check (email) query failed");
        $_SESSION['login_error'] = "Terjadi kesalahan sistem (DB Error 1).";
        header("Location: login.php"); exit;
    }
    
    if (mysqli_num_rows($check_email) > 0) {
        error_log("DEBUG: Duplicate found - Email: $email");
        $_SESSION['login_error'] = "Email sudah terdaftar!";
        header("Location: login.php"); exit;
    }

    $check_nik = safe_query("SELECT id FROM users WHERE nik = ?", [$nik]);
    if ($check_nik === false) {
        error_log("DEBUG: Duplicate check (nik) query failed");
        $_SESSION['login_error'] = "Terjadi kesalahan sistem (DB Error 2).";
        header("Location: login.php"); exit;
    }
    
    if (mysqli_num_rows($check_nik) > 0) {
        error_log("DEBUG: Duplicate found - NIK: $nik");
        $_SESSION['login_error'] = "NIK sudah terdaftar!";
        header("Location: login.php"); exit;
    }

    // INSERT DENGAN STATUS PENDING
    $sql = "INSERT INTO users (username, password, nik, nama_lengkap, jabatan, unit_kerja, email, atasan_id, status) 
            VALUES (AES_ENCRYPT(?, 'bex'), AES_ENCRYPT(?, 'bara'), ?, ?, ?, ?, ?, ?, 'pending')";
    
    $params = [$email, $password, $nik, $nama, $jabatan, $unit_kerja, $email, $atasan_id];
    error_log("DEBUG: Attempting INSERT for $email");
    $res = safe_query($sql, $params);

    if ($res) {
        error_log("DEBUG: Registration success for $email");
        write_log("REGISTER_SUCCESS", "User baru mendaftar (PENDING): $nama ($email)");
        
        $tg_token = get_setting('telegram_bot_token');
        $tg_chat = get_setting('telegram_chat_id');
        if (!empty($tg_token) && !empty($tg_chat)) {
            $msg = "<b>🆕 BEXMEDIA: PENDAFTARAN BARU</b>\n\n";
            $msg .= "👤 Nama: $nama\n🆔 NIK: $nik\n💼 Jabatan: $jabatan\n🏢 Unit: $unit_kerja\n✉️ Email: $email\n\n⏳ <i>Mohon aktivasi di menu Settings.</i>";
            @file_get_contents("https://api.telegram.org/bot$tg_token/sendMessage?chat_id=$tg_chat&parse_mode=HTML&text=" . urlencode($msg));
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['register_success'] = "Pendaftaran berhasil! Akun Anda sedang dalam proses aktivasi oleh administrator.";
        header("Location: login.php"); exit;
    } else {
        error_log("DEBUG: INSERT query failed for $email");
        write_log("REGISTER_FAILED", "Gagal mendaftarkan user: $nama ($email)");
        $_SESSION['login_error'] = "Terjadi kesalahan sistem saat simpan data. Silakan hubungi IT.";
        header("Location: login.php"); exit;
    }
}
header("Location: login.php");
exit;
