<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); 

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  $_SESSION['flash_message'] = "error:Anda tidak memiliki akses ke halaman Monitoring Off-Duty.";
  header("Location: dashboard.php");
  exit;
}

// Fungsi badge warna status
function renderStatusBadge($status) {
  $status = strtolower($status);
  $cls = 'badge-secondary';
  switch ($status) {
    case 'menunggu': $cls = 'badge-warning'; break;
    case 'diproses': $cls = 'badge-info'; break;
    case 'selesai': $cls = 'badge-success'; break;
    case 'tidak bisa diperbaiki': $cls = 'badge-dark'; break;
    case 'ditolak': $cls = 'badge-danger'; break;
  }
  return "<span class='badge $cls'>" . ucfirst($status) . "</span>";
}

// Ambil parameter pencarian
$keyword     = $_GET['keyword'] ?? '';
$tgl_dari    = $_GET['tgl_dari'] ?? '';
$tgl_sampai  = $_GET['tgl_sampai'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>BexMedia - Monitoring Off-Duty</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <link rel="stylesheet" href="assets/css/custom.css" />
  <style>
  .table-nowrap td, .table-nowrap th {
    white-space: nowrap;
    vertical-align: middle;
  }
  </style>
</head>
<body class="ice-blue-theme">
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <?php include 'navbar.php'; ?>
      <?php include 'sidebar.php'; ?>
      
    <div class="main-content">
      <?php 
      $breadcrumb = "Command Center / <strong>Monitoring Off-Duty</strong>";
      include "topbar.php"; 
      ?>
        <section class="section">
          <div class="section-body">
            <div class="card card-ice">
              <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-user-clock text-primary mr-2"></i> Monitoring Laporan Off-Duty</h4>
              </div>
              <div class="card-body">
                <form method="GET" class="mb-4">
                  <div class="row align-items-end">
                    <div class="col-md-3">
                      <label class="small font-weight-bold text-muted">CARI DATA</label>
                      <input type="text" name="keyword" class="form-control form-control-ice" placeholder="Nama / Kategori / Petugas" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div class="col-md-2">
                      <label class="small font-weight-bold text-muted">DARI TANGGAL</label>
                      <input type="date" name="tgl_dari" class="form-control form-control-ice" value="<?= $tgl_dari ?>">
                    </div>
                    <div class="col-md-2">
                      <label class="small font-weight-bold text-muted">SAMPAI TANGGAL</label>
                      <input type="date" name="tgl_sampai" class="form-control form-control-ice" value="<?= $tgl_sampai ?>">
                    </div>
                    <div class="col-md-3">
                      <div class="btn-group w-100">
                        <button type="submit" class="btn btn-ice">
                          <i class="fas fa-search mr-1"></i> FILTER
                        </button>
                        <a href="data_off_duty.php" class="btn btn-outline-secondary">
                          <i class="fas fa-sync-alt"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </form>

<?php
$limit = 10;
$page = (int) ($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

$whereClauses = [];
if (!empty($tgl_dari) && !empty($tgl_sampai)) {
  $whereClauses[] = "DATE(tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'";
} else {
  $whereClauses[] = "DATE(tanggal) = CURDATE()";
}
if (!empty($keyword)) {
  $keywordEscaped = mysqli_real_escape_string($conn, $keyword);
  $whereClauses[] = "(nama LIKE '%$keywordEscaped%' OR kategori LIKE '%$keywordEscaped%' OR petugas LIKE '%$keywordEscaped%')";
}
$where = "WHERE " . implode(" AND ", $whereClauses);

$totalRowsQ = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_off_duty $where");
$totalRows = mysqli_fetch_assoc($totalRowsQ)['total'];
$totalPages = ceil($totalRows / $limit);

$query = "SELECT * FROM laporan_off_duty $where ORDER BY tanggal DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);
$no = $offset + 1;
?>

                <div class="table-responsive">
                  <table class="table table-hover table-nowrap">
                    <thead style="background: var(--ice-5);">
                      <tr>
                        <th class="text-center">NO</th>
                        <th>NO TIKET</th>
                        <th>TANGGAL</th>
                        <th>PELAPOR</th>
                        <th>UNIT KERJA</th>
                        <th>KATEGORI</th>
                        <th>PETUGAS IT</th>
                        <th class="text-center">STATUS</th>
                        <th>VALIDATOR</th>
                        <th class="text-center">AKSI</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                          <td class="text-center"><?= $no++; ?></td>
                          <td><span class="font-weight-bold"><?= h($row['no_tiket']); ?></span></td>
                          <td><small><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></small></td>
                          <td>
                            <strong><?= h($row['nama']); ?></strong><br>
                            <small class="text-muted"><?= h($row['nik']); ?> - <?= h($row['jabatan']); ?></small>
                          </td>
                          <td><?= h($row['unit_kerja']); ?></td>
                          <td><?= h($row['kategori']); ?></td>
                          <td><span class="badge badge-light"><?= h($row['petugas']); ?></span></td>
                          <td class="text-center"><?= renderStatusBadge($row['status_validasi']); ?></td>
                          <td>
                            <?php
                              $validator = '-';
                              if (!empty($row['validator_id'])) {
                                $getValidator = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '{$row['validator_id']}'");
                                if ($v = mysqli_fetch_assoc($getValidator)) {
                                  $validator = $v['nama_lengkap'];
                                }
                              }
                              echo "<small>$validator</small>";
                            ?>
                          </td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-ice rounded-pill px-3" onclick="bukaModal(<?= $row['id']; ?>, <?= htmlspecialchars(json_encode($row['status_validasi'])); ?>, <?= htmlspecialchars(json_encode($row['catatan_it'])); ?>)">
                              <i class="fa fa-edit mr-1"></i> PROSES
                            </button>
                          </td>
                        </tr>
                      <?php endwhile; else: ?>
                        <tr><td colspan="10" class="text-center py-5 text-muted">Tidak ada data Off-Duty yang ditemukan untuk kriteria ini.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <?php if ($totalPages > 1): ?>
                  <nav>
                    <ul class="pagination justify-content-center mt-4">
                      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                          <a class="page-link" href="?page=<?= $i; ?>&keyword=<?= urlencode($keyword); ?>&tgl_dari=<?= $tgl_dari; ?>&tgl_sampai=<?= $tgl_sampai; ?>"><?= $i; ?></a>
                        </li>
                      <?php endfor; ?>
                    </ul>
                  </nav>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

<!-- Modal Update Status -->
<div class="modal fade" id="ubahStatusModalGlobal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content card-ice">
      <form action="update_status_off_duty.php" method="POST">
        <input type="hidden" name="id" id="modalInputId">
        <div class="modal-header bg-white">
          <h5 class="modal-title text-primary"><i class="fas fa-tasks mr-2"></i>Update Status Task</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold small text-muted text-uppercase">Status Validasi</label>
            <select name="status_validasi" id="modalStatus" class="form-control form-control-ice" required>
              <option value="Menunggu">Menunggu</option>
              <option value="Diproses">Diproses</option>
              <option value="Selesai">Selesai</option>
              <option value="Tidak Bisa Diperbaiki">Tidak Bisa Diperbaiki</option>
              <option value="Ditolak">Ditolak</option>
            </select>
          </div>
          <div class="form-group">
            <label class="font-weight-bold small text-muted text-uppercase">Catatan IT</label>
            <textarea name="catatan_it" id="modalCatatan" class="form-control form-control-ice" rows="4" placeholder="Tambahkan keterangan teknis..."></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light container-fluid">
          <button type="button" class="btn btn-outline-secondary px-4" data-dismiss="modal">BATAL</button>
          <button type="submit" name="simpan" class="btn btn-ice px-4">SIMPAN PERUBAHAN</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>

<script>
function bukaModal(id, status, catatan) {
  $('#modalInputId').val(id);
  $('#modalStatus').val(status);
  $('#modalCatatan').val(catatan);
  $('#ubahStatusModalGlobal').modal('show');
}
</script>
</body>
</html>
