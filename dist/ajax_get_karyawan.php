<?php
include 'security.php';
include 'koneksi.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) { echo json_encode(['error'=>'Unauthorized']); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Invalid ID']); exit; }

// User dasar
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?? [];
// Hapus sensitive fields
unset($user['password'], $user['pin'], $user['token']);

// Info pribadi
$stmt = $conn->prepare("SELECT * FROM informasi_pribadi WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $id); $stmt->execute();
$info_pribadi = $stmt->get_result()->fetch_assoc() ?? [];

// Riwayat pekerjaan
$stmt = $conn->prepare("SELECT * FROM riwayat_pekerjaan WHERE user_id=? ORDER BY tanggal_mulai DESC");
$stmt->bind_param("i", $id); $stmt->execute();
$pekerjaan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Riwayat pendidikan
$stmt = $conn->prepare("SELECT * FROM riwayat_pendidikan WHERE user_id=? ORDER BY tgl_lulus DESC");
$stmt->bind_param("i", $id); $stmt->execute();
$pendidikan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Kesehatan
$stmt = $conn->prepare("SELECT * FROM riwayat_kesehatan WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $id); $stmt->execute();
$kesehatan = $stmt->get_result()->fetch_assoc() ?? [];

// Dokumen
$stmt = $conn->prepare("SELECT * FROM dokumen_pendukung WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $id); $stmt->execute();
$dokumen = $stmt->get_result()->fetch_assoc() ?? [];

echo json_encode([
    'user'         => $user,
    'info_pribadi' => $info_pribadi,
    'pekerjaan'    => $pekerjaan,
    'pendidikan'   => $pendidikan,
    'kesehatan'    => $kesehatan,
    'dokumen'      => $dokumen,
], JSON_UNESCAPED_UNICODE);
