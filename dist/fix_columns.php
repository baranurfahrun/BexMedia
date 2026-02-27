<?php
include 'koneksi.php';

$tables = ['tiket_it_software', 'tiket_it_hardware', 'tiket_sarpras'];
foreach ($tables as $table) {
    // Check if waktu_selesai exists
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE 'waktu_selesai'");
    if (mysqli_num_rows($res) == 0) {
        $alter = mysqli_query($conn, "ALTER TABLE $table ADD COLUMN waktu_selesai DATETIME NULL");
        if ($alter) echo "Added waktu_selesai to $table\n";
        else echo "Failed to add column to $table: " . mysqli_error($conn) . "\n";
    } else {
        echo "waktu_selesai already exists in $table\n";
    }
}
?>
