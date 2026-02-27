<?php
include 'security.php';
include 'koneksi.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('ID tidak ditemukan'); window.location='barang_ac.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$hapus = mysqli_query($conn, "DELETE FROM data_barang_ac WHERE id='$id'");

if ($hapus) {
    $_SESSION['flash_message'] = "✅ Data AC berhasil dihapus!";
} else {
    $_SESSION['flash_message'] = "❌ Gagal menghapus data: " . mysqli_error($conn);
}
header("Location: data_barang_ac.php?tab=data");
exit;
?>







