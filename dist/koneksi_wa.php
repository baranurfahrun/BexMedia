<?php
// Ambil pengaturan dari tabel web_settings via config.php
require_once __DIR__ . "/../conf/config.php";

$host_wa = get_setting('wa_db_host', '192.20.20.234');
$user_wa = get_setting('wa_db_user', 'simrs');
$pass_wa = get_setting('wa_db_pass', 'Simrs2022');
$db_wa   = get_setting('wa_db_name', 'wa_gateway');

$conn_wa = new mysqli($host_wa, $user_wa, $pass_wa, $db_wa);
if ($conn_wa->connect_error) {
    error_log("Koneksi WA Gateway Gagal: " . $conn_wa->connect_error);
    // Jangan die agar tidak mematikan seluruh aplikasi jika database WA mati
}
?>
