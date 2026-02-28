<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$sql = "SELECT p.*, u.id as pemohon_id, u.nik, u.nama_lengkap AS nama, u.unit_kerja, u.jabatan,
               mc.nama_cuti, 
               d.id as delegasi_user_id, d.nama_lengkap AS nama_delegasi,
               (SELECT COUNT(id) FROM pengajuan_cuti_detail WHERE pengajuan_id = p.id) AS lama_hari_count,
               (SELECT GROUP_CONCAT(DATE_FORMAT(tanggal,'%d-%m-%Y') ORDER BY tanggal SEPARATOR ', ') FROM pengajuan_cuti_detail WHERE pengajuan_id = p.id) AS tanggal_cuti_list
        FROM pengajuan_cuti p
        JOIN users u ON p.karyawan_id = u.id
        JOIN master_cuti mc ON p.cuti_id = mc.id
        LEFT JOIN users d ON p.delegasi_id = d.id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

echo "<pre>";
print_r($data);
echo "</pre>";

$q_perusahaan = $conn->query("SELECT * FROM perusahaan LIMIT 1");
$perusahaan = $q_perusahaan->fetch_assoc();
echo "<pre>";
print_r($perusahaan);
echo "</pre>";
?>
