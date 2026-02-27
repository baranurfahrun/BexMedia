<?php
// security.php bridging to BexMedia security
require_once __DIR__ . "/../conf/config.php";

// Map BexMedia session to FixPoint expected variables if needed
if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    // Attempt to get user_id from database if missing in session
    $u = $_SESSION['username'];
    $res = mysqli_query($conn, "SELECT id FROM users WHERE username = AES_ENCRYPT('$u', 'bex')");
    if ($row = mysqli_fetch_assoc($res)) {
        $_SESSION['user_id'] = $row['id'];
    }
}

// Call BexMedia's checkLogin
checkLogin();

// Update activity tracking
$_SESSION['LAST_ACTIVITY'] = time();

// Common user data for header
$user_id_logged = $_SESSION['user_id'] ?? 0;
$nama_user      = 'User';
$foto_user      = '';
$jabatan_user   = '';
$nik_user       = '';
$unit_user      = '';

if ($user_id_logged) {
    $_qUser = mysqli_query($conn, "SELECT nama_lengkap, photo, jabatan, nik, unit_kerja, AES_DECRYPT(username, 'bex') as uname FROM users WHERE id = $user_id_logged LIMIT 1");
    if ($_rUser = mysqli_fetch_assoc($_qUser)) {
        // Use Full Name for display in header as requested
        $nama_user    = !empty($_rUser['nama_lengkap']) ? $_rUser['nama_lengkap'] : ($_rUser['uname'] ?? 'User');
        $foto_user    = !empty($_rUser['photo']) ? "images/" . $_rUser['photo'] : '';
        $jabatan_user = $_rUser['jabatan'] ?? '';
        $nik_user     = $_rUser['nik'] ?? '';
        $unit_user    = $_rUser['unit_kerja'] ?? '';
        
        // Persist to session for access in legacy components (like navbar.php)
        $_SESSION['nama_user']    = $nama_user;
        $_SESSION['foto_user']    = $foto_user;
        $_SESSION['jabatan_user'] = $jabatan_user;
        $_SESSION['nik_user']     = $nik_user;
        $_SESSION['unit_user']    = $unit_user;
    }
}


?>







