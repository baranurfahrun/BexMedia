<?php
require_once "conf/config.php";

// 1. Tambah kolom ke tabel users
$cols = [
    "nik VARCHAR(30) UNIQUE AFTER password",
    "jabatan VARCHAR(100) AFTER nama_lengkap",
    "unit_kerja VARCHAR(100) AFTER jabatan",
    "email VARCHAR(255) UNIQUE AFTER unit_kerja",
    "atasan_id INT AFTER photo",
    "status ENUM('active', 'pending', 'blocked') DEFAULT 'active' AFTER atasan_id"
];

foreach ($cols as $col) {
    $parts = explode(" ", $col);
    $name = $parts[0];
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '$name'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE users ADD $col";
        if (mysqli_query($conn, $sql)) {
            echo "Successfully added column: $name\n";
        } else {
            echo "Failed to add column: $name - " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column already exists: $name\n";
    }
}

// 2. Pastikan Jabatan & Unit Terisi
$check_jabatan = mysqli_query($conn, "SELECT id FROM jabatan LIMIT 1");
if (mysqli_num_rows($check_jabatan) == 0) {
    mysqli_query($conn, "INSERT INTO jabatan (nama_jabatan) VALUES 
        ('Dokter Spesialis'), ('Dokter Umum'), ('Perawat'), ('Bidan'), 
        ('Apoteker'), ('Analis Kesehatan'), ('Staf Administrasi'), ('IT Support')");
    echo "Seeded jabatan table.\n";
}

$check_unit = mysqli_query($conn, "SELECT id FROM unit_kerja LIMIT 1");
if (mysqli_num_rows($check_unit) == 0) {
    mysqli_query($conn, "INSERT INTO unit_kerja (nama_unit) VALUES 
        ('Rawat Jalan'), ('Rawat Inap'), ('IGD'), ('Farmasi'), 
        ('Laboratorium'), ('Radiologi'), ('Administrasi'), ('IT')");
    echo "Seeded unit_kerja table.\n";
}

echo "\nMigration Complete.\n";
?>
