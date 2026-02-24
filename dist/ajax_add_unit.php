<?php
/**
 * ajax_add_unit.php - Menambah unit kerja baru ke database master
 */
include 'security.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], ['admin', 'bara'])) {
    die(json_encode(['success' => false, 'message' => 'Restricted access']));
}

$nama_unit = trim($_POST['nama_unit'] ?? '');

if (empty($nama_unit)) {
    die(json_encode(['success' => false, 'message' => 'Nama unit wajib diisi']));
}

$nameEsc = mysqli_real_escape_string($conn, $nama_unit);

// Cek apakah sudah ada
$check = mysqli_query($conn, "SELECT id FROM unit_kerja WHERE nama_unit = '$nameEsc' LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    die(json_encode(['success' => false, 'message' => 'Unit sudah ada dalam daftar']));
}

// Insert baru
$ins = mysqli_query($conn, "INSERT INTO unit_kerja (nama_unit) VALUES ('$nameEsc')");

if ($ins) {
    echo json_encode(['success' => true, 'nama' => $nama_unit]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
}
