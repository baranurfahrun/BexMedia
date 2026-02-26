<?php
include 'koneksi.php';

// Cek apakah tabel ada
$check = mysqli_query($conn, "SHOW TABLES LIKE 'data_barang_it'");
if (mysqli_num_rows($check) == 0) {
    echo "Tabel data_barang_it belum ada. Membuat tabel...\n";
} else {
    echo "Tabel data_barang_it sudah ada. Memeriksa struktur...\n";
}

$sql = "CREATE TABLE IF NOT EXISTS data_barang_it (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    no_barang VARCHAR(50),
    nama_barang VARCHAR(255),
    kategori VARCHAR(100),
    merk VARCHAR(100),
    spesifikasi TEXT,
    ip_address VARCHAR(50),
    lokasi VARCHAR(100),
    kondisi VARCHAR(50),
    waktu_input DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Tabel data_barang_it berhasil disiapkan!\n";
    
    // Pastikan kolom user_id ada jika tabel sudah pernah dibuat sebelumnya tapi strukturnya berbeda
    $res = mysqli_query($conn, "DESCRIBE data_barang_it");
    $cols = [];
    while($row = mysqli_fetch_assoc($res)) { $cols[] = $row['Field']; }
    
    if (!in_array('user_id', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN user_id INT AFTER id");
        echo "Kolom user_id ditambahkan.\n";
    }
    if (!in_array('no_barang', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN no_barang VARCHAR(50) AFTER user_id");
        echo "Kolom no_barang ditambahkan.\n";
    }
    if (!in_array('merk', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN merk VARCHAR(100) AFTER kategori");
        echo "Kolom merk ditambahkan.\n";
    }
    if (!in_array('spesifikasi', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN spesifikasi TEXT AFTER merk");
        echo "Kolom spesifikasi ditambahkan.\n";
    }
    if (!in_array('ip_address', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN ip_address VARCHAR(50) AFTER spesifikasi");
        echo "Kolom ip_address ditambahkan.\n";
    }
    if (!in_array('kondisi', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN kondisi VARCHAR(50) AFTER lokasi");
        echo "Kolom kondisi ditambahkan.\n";
    }
    if (!in_array('waktu_input', $cols)) {
        mysqli_query($conn, "ALTER TABLE data_barang_it ADD COLUMN waktu_input DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "Kolom waktu_input ditambahkan.\n";
    }
    
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
