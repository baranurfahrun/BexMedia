<?php
include 'koneksi.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'send_wa.php';

$nomor_tes = '6281342264881'; 
$pesan_tes = "Halo! Ini adalah pesan tes ULANG dari sistem BexMedia pada jam " . date('H:i:s');

echo "Sedang mencoba mengirim WA ke $nomor_tes...<br>";

// Tambahan Pengecekan Tabel
include_once 'koneksi_wa.php';

if (!isset($conn_wa)) {
    echo "<b style='color:red'>EROR: Variabel \$conn_wa tidak terdefinisi!</b><br>";
} elseif ($conn_wa->connect_error) {
    echo "<b style='color:red'>EROR Koneksi DB WA:</b> " . $conn_wa->connect_error . "<br>";
    echo "Host: " . $host_wa . "<br>";
    echo "User: " . $user_wa . "<br>";
    echo "Pass: " . ($pass_wa ? 'TERISI' : 'KOSONG') . "<br>";
} else {
    echo "<b style='color:green'>Koneksi DB WA Berhasil.</b><br>";
    $check_table = mysqli_query($conn_wa, "SHOW TABLES LIKE 'wa_outbox'");
    if (mysqli_num_rows($check_table) == 0) {
        echo "<b style='color:red'>EROR: Tabel 'wa_outbox' tidak ditemukan!</b><br>";
    } else {
        echo "<b style='color:green'>Tabel 'wa_outbox' ditemukan.</b><br>";
    }
}

if (sendWA($nomor_tes, $pesan_tes)) {
    echo "<b style='color:green'>Data berhasil masuk ke antrian (wa_outbox)!</b><br>";
} else {
    echo "<b style='color:red'>Gagal memasukkan ke antrian.</b> Cek log PHP.";
}
?>
