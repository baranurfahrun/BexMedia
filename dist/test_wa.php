<?php
include 'koneksi.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'send_wa.php';

// Masukkan nomor WA tes Anda (gunakan format 628...)
$nomor_tes = '6281342264881'; // Nomor yang benar
$pesan_tes = "Halo! Ini adalah pesan tes dari sistem BexMedia pada jam " . date('H:i:s');

echo "Sedang mencoba mengirim WA ke $nomor_tes...<br>";

// Tambahan Pengecekan Tabel
include_once 'koneksi_wa.php';

if (!isset($conn_wa)) {
    echo "<b style='color:red'>EROR: Variabel \$conn_wa tidak terdefinisi!</b><br>";
} elseif ($conn_wa->connect_error) {
    echo "<b style='color:red'>EROR Koneksi DB WA:</b> " . $conn_wa->connect_error . "<br>";
} else {
    echo "<b style='color:green'>Koneksi DB WA Berhasil.</b><br>";
    $check_table = mysqli_query($conn_wa, "SHOW TABLES LIKE 'wa_outbox'");
    if (mysqli_num_rows($check_table) == 0) {
        echo "<b style='color:red'>EROR: Tabel 'wa_outbox' tidak ditemukan!</b><br>";
        $show_tables = mysqli_query($conn_wa, "SHOW TABLES");
        echo "Daftar tabel: ";
        while($t = mysqli_fetch_array($show_tables)) echo "$t[0], ";
        echo "<br>";
    } else {
        echo "<b style='color:green'>Tabel 'wa_outbox' ditemukan.</b><br>";
    }
}

echo "Memanggil fungsi sendWA()...<br>";
if (sendWA($nomor_tes, $pesan_tes)) {
    echo "<b style='color:green'>Data berhasil masuk ke antrian (wa_outbox)!</b><br>";
    echo "Pesan: <i>$pesan_tes</i><br><br>";
    echo "Silakan cek aplikasi Gateway WA Anda untuk memastikan pesan terkirim ke HP.";
} else {
    echo "<b style='color:red'>Gagal memasukkan ke antrian.</b> Cek log PHP.";
}
?>
