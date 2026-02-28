<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Makassar');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// === Data user login ===
$qUser = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE id='$user_id'");
$userLogin = mysqli_fetch_assoc($qUser);

// === Proses Approval ===
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];
    $status = ($aksi == 'acc') ? 'Disetujui' : 'Ditolak';

    $stmt = $conn->prepare("UPDATE pengajuan_ganti_jadwal SET 
                            status_pengganti = ?, 
                            acc_pengganti_by = ?, 
                            acc_pengganti_time = NOW() 
                            WHERE id = ? AND pengganti_id = ?");
    $stmt->bind_param("ssii", $status, $userLogin['nama_lengkap'], $id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "success:Berhasil memperbarui status delegasi.";
    } else {
        $_SESSION['flash_message'] = "error:Gagal memproses data.";
    }
    header("Location: data_ganti_jadwal_pengganti.php");
    exit;
}

// === Ambil data yang perlu persetujuan saya sebagai pengganti ===
$data = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap AS nama_pemohon, j.nama_jam
    FROM pengajuan_ganti_jadwal p
    JOIN users u ON p.karyawan_id = u.id
    JOIN jam_kerja j ON p.jam_kerja_id = j.id
    WHERE p.pengganti_id = '$user_id'
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8">
  <title>BexMedia - Persetujuan Ganti Jadwal (Pengganti)</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="ice-blue-theme">
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
      <?php 
      $breadcrumb = "Approval Hub / <strong>Persetujuan Ganti Jadwal</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card card-ice">
            <div class="card-header">
              <h4 class="text-primary"><i class="fas fa-user-check mr-2"></i>Konfirmasi Rekan Pengganti</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead style="background: var(--ice-soft-bg);">
                    <tr>
                      <th width="40">NO</th>
                      <th>PEMOHON</th>
                      <th>TANGGAL JAWAL</th>
                      <th>SHIFT BARU</th>
                      <th>ALASAN</th>
                      <th>STATUS ANDA</th>
                      <th width="180" class="text-center">TINDAKAN</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no=1; while($row = mysqli_fetch_assoc($data)): ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= h($row['nama_pemohon']) ?></strong></td>
                        <td><code><?= date('d M Y', strtotime($row['tanggal'])) ?></code></td>
                        <td><span class="badge badge-light"><?= h($row['nama_jam']) ?></span></td>
                        <td><small><?= h($row['alasan']) ?></small></td>
                        <td>
                            <?php
                            $st = $row['status_pengganti'];
                            $badge = ($st == 'Disetujui') ? 'badge-success' : (($st == 'Ditolak') ? 'badge-danger' : 'badge-warning');
                            ?>
                            <span class="badge <?= $badge ?>"><?= $st ?></span>
                        </td>
                        <td class="text-center">
                          <?php if($row['status_pengganti'] == 'Menunggu'): ?>
                            <a href="data_ganti_jadwal_pengganti.php?id=<?= $row['id'] ?>&aksi=acc" 
                               class="btn btn-sm btn-success shadow-sm rounded-pill px-3"
                               onclick="return confirm('Apakah Anda bersedia menggantikan jadwal ini?')">
                               <i class="fas fa-check mr-1"></i> SETUJU
                            </a>
                            <a href="data_ganti_jadwal_pengganti.php?id=<?= $row['id'] ?>&aksi=tolak" 
                               class="btn btn-sm btn-danger shadow-sm rounded-pill px-3"
                               onclick="return confirm('Yakin ingin menolak permintaan ini?')">
                               <i class="fas fa-times mr-1"></i> TOLAK
                            </a>
                          <?php else: ?>
                            <span class="text-muted small">Processed</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($data) == 0): ?>
                      <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada permintaan pertukaran jadwal untuk Anda.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>
