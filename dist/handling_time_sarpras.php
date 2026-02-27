<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = 'handling_time.php'; // Cek akses via file utama

// Cek akses menu
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Pencarian & Filter tanggal
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

if ($dari_tanggal) $dari_tanggal = date('Y-m-d', strtotime($dari_tanggal));
if ($sampai_tanggal) $sampai_tanggal = date('Y-m-d', strtotime($sampai_tanggal));

// Fungsi format tanggal & durasi
function formatTanggal($tanggal) {
    return $tanggal ? date('d-m-Y H:i', strtotime($tanggal)) : '-';
}
function hitungDurasi($mulai, $selesai) {
    if (!$mulai || !$selesai) return '-';
    $start = new DateTime($mulai);
    $end = new DateTime($selesai);
    $interval = $start->diff($end);
    $jam = $interval->h + ($interval->days * 24);
    $menit = $interval->i;
    return "{$jam}j {$menit}m";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8" />
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>BexMedia Dashboard</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <style>
    .table-responsive-custom { width: 100%; overflow-x: auto; }
    .table-responsive-custom table { min-width: 1500px; white-space: nowrap; }
    
    .pagination .page-link { color: #3498db; }
    .pagination .page-item.active .page-link { 
      background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); 
      border-color: #2980b9; 
      color: #fff;
    }
    .pagination .page-link:hover {
      background-color: #3498db;
      color: #fff;
      border-color: #3498db;
    }

    /* Custom Ice Blue Button */
    .btn-ice-blue {
      background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
      border: none;
      color: #fff;
      box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s;
    }

    .btn-ice-blue:hover {
      background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
      color: #fff;
    }

    .table thead th {
      background-color: #000 !important;
      color: #fff !important;
    }

    /* Custom Nav Tabs Ice Blue */
    .nav-tabs {
      border-bottom: 2px solid #ebeefd;
    }
    .nav-tabs .nav-item .nav-link {
      color: #78828a;
      font-weight: 600;
      border: none;
      padding: 10px 20px;
    }
    .nav-tabs .nav-item .nav-link.active {
      color: #3498db !important;
      background: transparent;
      border-bottom: 3px solid #3498db;
    }
    .nav-tabs .nav-item .nav-link:hover {
      color: #3498db;
      border: none;
    }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
      <?php 
      $breadcrumb = "Technical Support / <strong>Handling Time</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-clock"></i> SR Handling Time Data</h4>
            </div>
            <div class="card-body">

              <!-- Tabs -->
              <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                  <a class="nav-link" href="handling_time.php">Hardware SR</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="handling_time_software.php">Software SR</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link active" href="handling_time_sarpras.php">Sarpras SR</a>
                </li>
              </ul>

              <!-- Form Filter -->
              <form method="GET" class="mb-3">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Dari Tanggal</label>
                      <input type="date" name="dari_tanggal" class="form-control" value="<?php echo htmlspecialchars($dari_tanggal); ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Sampai Tanggal</label>
                      <input type="date" name="sampai_tanggal" class="form-control" value="<?php echo htmlspecialchars($sampai_tanggal); ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Pencarian</label>
                      <input type="text" name="keyword" class="form-control" placeholder="NIK / Nama / No Tiket" value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>&nbsp;</label>
                      <div>
                        <button type="submit" class="btn btn-ice-blue mr-2"><i class="fas fa-search"></i> Filter</button>
                        <a href="handling_time_sarpras.php" class="btn btn-secondary mr-2"><i class="fas fa-sync"></i> Reset</a>
                        <a href="handling_time_sarpras_pdf.php?dari_tanggal=<?php echo $dari_tanggal; ?>&sampai_tanggal=<?php echo $sampai_tanggal; ?>&keyword=<?php echo urlencode($keyword); ?>" target="_blank" class="btn btn-danger">
                          <i class="fas fa-file-pdf"></i> PDF
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </form>

              <!-- Tabel -->
              <div class="table-responsive-custom">
                <table class="table table-bordered table-sm table-hover">
                  <thead class="text-center">
                    <tr>
                      <th>No</th>
                      <th>SR Number</th>
                      <th>NIK</th>
                      <th>Nama</th>
                      <th>Jabatan</th>
                      <th>Unit Kerja</th>
                      <th>Kategori</th>
                      <th>Kendala</th>
                      <th>Status</th>
                      <th>Teknisi</th>
                      <th>Tgl Input</th>
                      <th>Diproses</th>
                      <th>Selesai</th>
                      <th>Validasi</th>
                      <th>Waktu Validasi</th>
                      <th>Respon Time</th>
                      <th>Selesai Time</th>
                      <th>Validasi Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $no = 1;
                    $q = "SELECT * FROM tiket_sarpras WHERE 1=1";
                    if (!empty($keyword)) {
                        $kw = mysqli_real_escape_string($conn, $keyword);
                        $q .= " AND (nik LIKE '%$kw%' OR nama LIKE '%$kw%' OR nomor_tiket LIKE '%$kw%' OR kategori LIKE '%$kw%')";
                    }
                    if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
                        $q .= " AND DATE(tanggal_input) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
                    }
                    $q .= " ORDER BY tanggal_input DESC";
                    $res = mysqli_query($conn, $q);
                    if(mysqli_num_rows($res) > 0){
                      while($row = mysqli_fetch_assoc($res)){
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</td>";
                        echo "<td>{$row['nomor_tiket']}</td>";
                        echo "<td>{$row['nik']}</td>";
                        echo "<td>{$row['nama']}</td>";
                        echo "<td>{$row['jabatan']}</td>";
                        echo "<td>{$row['unit_kerja']}</td>";
                        echo "<td>{$row['kategori']}</td>";
                        echo "<td>" . htmlspecialchars($row['kendala']) . "</td>";
                        
                        $status = $row['status'];
                        $badge = 'secondary';
                        $ls = strtolower($status);
                        if($ls == 'menunggu') $badge = 'warning';
                        elseif($ls == 'diproses') $badge = 'info';
                        elseif($ls == 'selesai') $badge = 'success';
                        elseif($ls == 'tidak bisa diperbaiki') $badge = 'danger';

                        echo "<td class='text-center'><span class='badge badge-{$badge}'>{$status}</span></td>";

                        echo "<td>" . ($row['teknisi_nama'] ?? '-') . "</td>";
                        echo "<td>".formatTanggal($row['tanggal_input'])."</td>";
                        echo "<td>".formatTanggal($row['waktu_diproses'])."</td>";
                        echo "<td>".formatTanggal($row['waktu_selesai'])."</td>";
                        echo "<td>" . ($row['status_validasi'] ?? '-') . "</td>";
                        echo "<td>".formatTanggal($row['waktu_validasi'] ?? null)."</td>";
                        echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_diproses'])."</td>";
                        echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_selesai'])."</td>";
                        echo "<td>".hitungDurasi($row['tanggal_input'], $row['waktu_validasi'] ?? null)."</td>";
                        echo "</tr>";
                        $no++;
                      }
                    }else{
                      echo "<tr><td colspan='18' class='text-center'>Tidak ada data ditemukan.</td></tr>";
                    }
                    ?>
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
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
