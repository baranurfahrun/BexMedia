<?php
include 'koneksi.php';
// Ensure no ghost shifts exist
mysqli_query($conn, "DELETE FROM jam_kerja WHERE kode = 'LEPAS' OR nama_jam LIKE 'Lepas%'");
// Ensure IDs are consistent
mysqli_query($conn, "UPDATE jam_kerja SET kode='PAGI' WHERE nama_jam='Pagi'");
mysqli_query($conn, "UPDATE jam_kerja SET kode='SIANG' WHERE nama_jam='Siang'");
mysqli_query($conn, "UPDATE jam_kerja SET kode='MALAM' WHERE nama_jam='Malam'");
mysqli_query($conn, "UPDATE jam_kerja SET kode='LIBUR' WHERE nama_jam='Libur'");
echo "DB Cleaned.";
?>
