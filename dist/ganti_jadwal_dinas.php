<?php
session_start();
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Makassar');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__);

// === Data user login ===
$qUser = mysqli_query($conn, "SELECT id, nama_lengkap, unit_kerja FROM users WHERE id='$user_id'");
$userLogin = mysqli_fetch_assoc($qUser);

// === Dropdown karyawan pengganti (unit kerja sama, kecuali diri sendiri) ===
$delegasiList = mysqli_query($conn, "SELECT id, nama_lengkap FROM users 
                                     WHERE unit_kerja = '".$userLogin['unit_kerja']."' 
                                     AND id <> '".$userLogin['id']."' 
                                     ORDER BY nama_lengkap ASC");

// Mapping jam kerja
$jamQuery = mysqli_query($conn, "SELECT * FROM jam_kerja ORDER BY jam_mulai");
$jamList = [];
while($j = mysqli_fetch_assoc($jamQuery)){
    $jamList[$j['id']] = $j['nama_jam'] . " ({$j['jam_mulai']} - {$j['jam_selesai']})";
}

// === Proses simpan pengajuan ganti jadwal ===
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['simpan'])){
    $karyawan_id = $userLogin['id'];
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_kerja_id = intval($_POST['jam_kerja_id']);
    $pengganti_id = intval($_POST['pengganti_id']);
    $alasan = $_POST['alasan'] ?? '';

    if(empty($tanggal) || $pengganti_id<=0 || $jam_kerja_id<=0 || empty($alasan)){
        $_SESSION['flash_message'] = "error:Semua field wajib diisi!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = $conn->prepare("INSERT INTO pengajuan_ganti_jadwal 
                    (karyawan_id, pengganti_id, tanggal, jam_kerja_id, alasan, 
                     status, status_pengganti, status_atasan, status_hrd, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Menunggu', 'Menunggu', 'Menunggu', 'Menunggu', ?, NOW())");
            
            $created_by = $userLogin['nama_lengkap'];
            $stmt->bind_param("iisiss", $karyawan_id, $pengganti_id, $tanggal, $jam_kerja_id, $alasan, $created_by);
            $stmt->execute();
            
            mysqli_commit($conn);
            $_SESSION['flash_message'] = "success:✅ Pengajuan ganti jadwal berhasil disimpan.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash_message'] = "error:Gagal menyimpan data: " . $e->getMessage();
        }
    }

    header("Location: ganti_jadwal_dinas.php");
    exit;
}

// === Ambil data pengajuan untuk tabel ===
$dataPengajuan = mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap AS nama_karyawan, d.nama_lengkap AS nama_pengganti, j.nama_jam
    FROM pengajuan_ganti_jadwal p
    JOIN users u ON p.karyawan_id=u.id
    JOIN users d ON p.pengganti_id=d.id
    JOIN jam_kerja j ON p.jam_kerja_id=j.id
    WHERE p.karyawan_id = '$user_id' OR p.pengganti_id = '$user_id'
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8">
  <title>BexMedia - Ganti Jadwal Dinas</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/custom.css">
  <style>
    .ganti-table { font-size: 13px; white-space: nowrap; }
    .ganti-table th, .ganti-table td { padding: 12px 15px; vertical-align: middle; border-color: #f1f5f9; }
    .form-group label { font-weight: 700; color: #334155; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; }
    .form-group label i { color: #0ea5e9; font-size: 14px; }
    .badge-soft-primary { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
  </style>
</head>
<body class="ice-blue-theme">
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
      <?php 
      $breadcrumb = "Employee Hub / <strong>Ganti Jadwal Dinas</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">

          <div class="card card-ice">
            <div class="card-header">
              <h4 class="mb-0">Pengajuan Ganti Jadwal Dinas</h4>
            </div>
            <div class="card-body px-4">
              <ul class="nav nav-tabs-ice" id="gantiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">
                    <i class="fas fa-plus-circle mr-2"></i>INPUT PENGAJUAN
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">
                    <i class="fas fa-history mr-2"></i>DATA PENGAJUAN
                  </a>
                </li>
              </ul>

              <div class="tab-content mt-4">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                  <form method="post">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group mb-4">
                          <label><i class="fas fa-user"></i> Karyawan Pemohon</label>
                          <input type="text" class="form-control form-control-ice" value="<?= h($userLogin['nama_lengkap']) ?>" readonly style="background: #f8fafc !important; font-weight: 600;">
                        </div>

                        <div class="form-group mb-4">
                          <label><i class="fas fa-calendar-day"></i> Tanggal Berlaku</label>
                          <input type="date" name="tanggal" class="form-control form-control-ice" required>
                          <small class="text-muted mt-2 d-inline-block">Pilih tanggal jadwal yang ingin digantikan.</small>
                        </div>

                        <div class="form-group mb-4">
                          <label><i class="fas fa-hourglass-half"></i> Shift / Jam Kerja Baru</label>
                          <select name="jam_kerja_id" class="form-control form-control-ice" required>
                            <option value="">-- Pilih Shift Kerja --</option>
                            <?php foreach($jamList as $id=>$jam): ?>
                              <option value="<?= $id ?>"><?= $jam ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group mb-4">
                          <label><i class="fas fa-user-friends"></i> Karyawan Pengganti</label>
                          <select name="pengganti_id" class="form-control form-control-ice" required>
                            <option value="">-- Pilih Rekan Pengganti --</option>
                            <?php
                            mysqli_data_seek($delegasiList,0);
                            while($d = mysqli_fetch_assoc($delegasiList)): ?>
                              <option value="<?= $d['id'] ?>"><?= h($d['nama_lengkap']) ?></option>
                            <?php endwhile; ?>
                          </select>
                          <small class="text-muted mt-2 d-inline-block">Rekan dalam satu unit kerja (<?= h($userLogin['unit_kerja']) ?>).</small>
                        </div>

                        <div class="form-group mb-4">
                          <label><i class="fas fa-comment-dots"></i> Alasan Pertukaran</label>
                          <textarea name="alasan" class="form-control form-control-ice" rows="4" placeholder="Contoh: Ada keperluan keluarga mendesak..." required style="min-height: 110px;"></textarea>
                        </div>

                        <div class="text-right">
                          <button type="submit" name="simpan" class="btn btn-ice">
                            <i class="fas fa-paper-plane mr-2"></i>AJUKAN JADWAL
                          </button>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>

                <!-- Tabel Data -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-hover ganti-table">
                      <thead style="background: var(--ice-soft-bg);">
                        <tr>
                          <th width="50" class="text-center">NO</th>
                          <th>PEMOHON</th>
                          <th>PENGGANTI</th>
                          <th>TANGGAL</th>
                          <th>SHIFT</th>
                          <th>STATUS</th>
                          <th width="100" class="text-center">AKSI</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while($row = mysqli_fetch_assoc($dataPengajuan)): ?>
                          <tr>
                            <td class="text-center font-weight-bold"><?= $no++ ?></td>
                            <td>
                                <div class="font-weight-bold text-dark"><?= h($row['nama_karyawan']) ?></div>
                                <?php if($row['karyawan_id'] == $user_id): ?>
                                    <span class="badge badge-soft-primary" style="font-size: 9px; padding: 2px 6px;">OWNER</span>
                                <?php endif; ?>
                            </td>
                            <td><div class="font-weight-600 text-muted"><?= h($row['nama_pengganti']) ?></div></td>
                            <td><span class="text-primary font-weight-bold"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($row['tanggal'])) ?></span></td>
                            <td><span class="badge badge-light border text-dark" style="font-weight: 600;"><?= h($row['nama_jam']) ?></span></td>
                            <td>
                                <?php
                                $status = $row['status'];
                                $badge = 'badge-warning';
                                if($status == 'Disetujui') $badge = 'badge-success';
                                if(strpos($status, 'Ditolak') !== false) $badge = 'badge-danger';
                                ?>
                                <span class="badge <?= $badge ?> px-3" style="border-radius: 20px; text-transform: uppercase; font-size: 10px;"><?= $status ?></span>
                            </td>
                            <td class="text-center">
                              <a href="cetak_ganti_jadwal.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-icon btn-info btn-sm rounded-circle shadow-sm" title="Cetak Surat">
                                <i class="fas fa-print"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($dataPengajuan) == 0): ?>
                          <tr>
                            <td colspan="7" class="text-center py-5">
                                <img src="../images/no-data.svg" alt="no data" style="width: 150px; opacity: 0.5;">
                                <p class="text-muted mt-3 font-weight-600">Belum ada data pengajuan ganti jadwal.</p>
                            </td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div> <!-- End Tab Content -->
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
