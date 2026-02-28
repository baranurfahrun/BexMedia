<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Makassar');

$user_id = $_SESSION['user_id'] ?? 0;

// === Data user login ===
$qUser = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE id='$user_id'");
$userLogin = mysqli_fetch_assoc($qUser);

// === Proses Approval ===
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];
    $status_hrd = ($aksi == 'acc') ? 'Disetujui' : 'Ditolak';
    $status_utama = ($aksi == 'acc') ? 'Disetujui' : 'Ditolak HRD';

    $stmt = $conn->prepare("UPDATE pengajuan_ganti_jadwal SET 
                            status_hrd = ?, 
                            status = ?,
                            acc_hrd_by = ?, 
                            acc_hrd_time = NOW() 
                            WHERE id = ?");
    $stmt->bind_param("sssi", $status_hrd, $status_utama, $userLogin['nama_lengkap'], $id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "success:Berhasil memproses pengajuan HRD.";
    } else {
        $_SESSION['flash_message'] = "error:Gagal memproses data.";
    }
    header("Location: data_ganti_jadwal_hrd.php");
    exit;
}

// === Ambil data yang sudah disetujui atasan ===
$data = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap AS nama_pemohon, d.nama_lengkap AS nama_pengganti, j.nama_jam
    FROM pengajuan_ganti_jadwal p
    JOIN users u ON p.karyawan_id = u.id
    JOIN users d ON p.pengganti_id = d.id
    JOIN jam_kerja j ON p.jam_kerja_id = j.id
    WHERE p.status_atasan = 'Disetujui'
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8">
  <title>BexMedia - Approval Ganti Jadwal (HRD)</title>
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
      $breadcrumb = "Approval Hub / <strong>Final Approval HRD - Ganti Jadwal</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card card-ice">
            <div class="card-header">
              <h4 class="text-danger"><i class="fas fa-id-badge mr-2"></i>Persetujuan Akhir HRD</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead style="background: var(--ice-soft-bg);">
                    <tr>
                      <th width="40">NO</th>
                      <th>PEMOHON</th>
                      <th>PENGGANTI</th>
                      <th>TANGGAL JAWAL</th>
                      <th>SHIFT</th>
                      <th>STATUS HRD</th>
                      <th width="180" class="text-center">TINDAKAN</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no=1; while($row = mysqli_fetch_assoc($data)): ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= h($row['nama_pemohon']) ?></strong></td>
                        <td><?= h($row['nama_pengganti']) ?></td>
                        <td><code><?= date('d M Y', strtotime($row['tanggal'])) ?></code></td>
                        <td><span class="badge badge-light border"><?= h($row['nama_jam']) ?></span></td>
                        <td>
                            <?php
                            $st = $row['status_hrd'];
                            $badge = ($st == 'Disetujui') ? 'badge-success' : (($st == 'Ditolak') ? 'badge-danger' : 'badge-warning');
                            ?>
                            <span class="badge <?= $badge ?>"><?= $st ?></span>
                        </td>
                        <td class="text-center">
                          <?php if($row['status_hrd'] == 'Menunggu'): ?>
                            <a href="data_ganti_jadwal_hrd.php?id=<?= $row['id'] ?>&aksi=acc" 
                               class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill"
                               onclick="return confirm('Proses final persetujuan ganti jadwal ini?')">
                               <i class="fas fa-check mr-1"></i> FINALIZE
                            </a>
                            <a href="data_ganti_jadwal_hrd.php?id=<?= $row['id'] ?>&aksi=tolak" 
                               class="btn btn-sm btn-outline-danger shadow-sm rounded-pill px-3"
                               onclick="return confirm('Tolak pengajuan ini?')">
                               <i class="fas fa-times mr-1"></i> REJECT
                            </a>
                          <?php else: ?>
                            <a href="cetak_ganti_jadwal.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info rounded-circle shadow-sm"><i class="fas fa-print"></i></a>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($data) == 0): ?>
                      <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data pengajuan yang masuk ke HRD.</td></tr>
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
