<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); // 

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

$user_id = $_SESSION['user_id'];
$queryUser = mysqli_query($conn, "SELECT nik, nama_lengkap, jabatan, unit_kerja FROM users WHERE id = '$user_id'");
$userData = mysqli_fetch_assoc($queryUser);

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
  <meta charset="UTF-8" />
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport" />
  <title>BexMedia Dashboard</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&display=swap" rel="stylesheet">
  <style>
    .swal2-popup {
        font-family: 'Outfit', sans-serif !important;
        border-radius: 24px !important;
        padding: 2em !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px) !important;
    }
    .swal2-title {
        color: #0B192E !important;
        font-weight: 700 !important;
        letter-spacing: -0.02em !important;
    }
    .swal2-html-container {
        color: #475569 !important;
        line-height: 1.6 !important;
    }
    .swal2-confirm {
        background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%) !important;
        border-radius: 12px !important;
        padding: 12px 30px !important;
        font-weight: 600 !important;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
    }
    .sr-number {
        display: inline-block;
        background: #F1F5F9;
        padding: 8px 16px;
        border-radius: 8px;
        color: #3B82F6;
        font-weight: 700;
        margin-top: 10px;
        border: 1px solid #E2E8F0;
        letter-spacing: 0.05em;
    }
  </style>

  <style>
    .table-responsive-custom {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .table-responsive-custom table {
      width: 100%;
      min-width: 1200px;
      white-space: nowrap;
    }

    .d-flex.gap-1 > form {
      margin-right: 5px;
    }

    .table thead th {
      background-color: #000 !important;
      color: #fff !important;
    }

    /* Custom Ice Blue Button */
    .btn-ice-blue {
      background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
      border: none;
      color: #fff !important;
      box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
      border-radius: 8px;
      padding: 10px 25px;
      font-weight: 600;
      transition: all 0.3s;
    }

    .btn-ice-blue:hover {
      background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
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
      $breadcrumb = "Technical Support / <strong>Software Service Request</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card">
            <div class="card-header">
              <h4>Software Service Request</h4>
            </div>
            <div class="card-body">

              <!-- Nav Tabs -->
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="order-tab" data-toggle="tab" href="#order" role="tab">New Request</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tiket-saya-tab" data-toggle="tab" href="#tiket-saya" role="tab">My Requests</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-4" id="myTabContent">

                <!-- Order Tiket -->
                <div class="tab-pane fade show active" id="order" role="tabpanel">
                  <form method="POST" action="simpan_tiket_it_software.php" id="form-order-software">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="simpan" value="1">
                    <div class="row">
                      <div class="form-group col-md-4">
                        <label for="nik">NIK</label>
                        <input type="text" name="nik" id="nik" class="form-control" value="<?= htmlspecialchars($userData['nik'] ?? ''); ?>" <?= empty($userData['nik']) ? '' : 'readonly'; ?>>
                      </div>

                      <div class="form-group col-md-4">
                        <label for="nama">Nama</label>
                        <input type="text" name="nama" id="nama" class="form-control" value="<?= $userData['nama_lengkap']; ?>" readonly>
                      </div>

                      <div class="form-group col-md-4">
                        <label for="jabatan">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" class="form-control" value="<?= htmlspecialchars($userData['jabatan'] ?? ''); ?>" <?= empty($userData['jabatan']) ? '' : 'readonly'; ?>>
                      </div>

                      <div class="form-group col-md-4">
                        <label for="unit_kerja">Unit Kerja</label>
                        <select name="unit_kerja" id="unit_kerja" class="form-control" required>
                          <option value="">-- Pilih Unit Kerja --</option>
                          <?php
                          $unitResult = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
                          while ($u = mysqli_fetch_assoc($unitResult)) {
                            $selected = ($userData['unit_kerja'] == $u['nama_unit']) ? 'selected' : '';
                            echo "<option value='{$u['nama_unit']}' $selected>{$u['nama_unit']}</option>";
                          }
                          ?>
                        </select>
                      </div>

                      <div class="form-group col-md-4">
                        <label for="kategori">Kategori Software</label>
                        <select class="form-control" name="kategori" required>
                          <option value="">-- Pilih Kategori --</option>
                          <?php
                          $kategoriResult = mysqli_query($conn, "SELECT nama_kategori FROM kategori_software");
                          while ($k = mysqli_fetch_assoc($kategoriResult)) {
                            echo "<option value='{$k['nama_kategori']}'>{$k['nama_kategori']}</option>";
                          }
                          ?>
                        </select>
                      </div>

                      <div class="form-group col-md-12">
                        <label for="kendala">Kendala / Laporan</label>
                        <textarea name="kendala" class="form-control" rows="3" required></textarea>
                      </div>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-ice-blue">Submit Request</button>
                  </form>
                </div>

                <!-- Tiket Saya -->
                <div class="tab-pane fade" id="tiket-saya" role="tabpanel">
                  <div class="table-responsive-custom">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>SR Number</th>
                          <th>Date</th>
                          <th>Category</th>
                          <th>Issue</th>
                          <th>IT Remarks</th>
                          <th>Status</th>
                          <th>Validation</th>
                          <th>Print</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $no = 1;
                        $queryTiket = mysqli_query($conn, "SELECT * FROM tiket_it_software WHERE user_id = '$user_id' ORDER BY tanggal_input DESC");
                        if (mysqli_num_rows($queryTiket) > 0) {
                          while ($row = mysqli_fetch_assoc($queryTiket)) {
                            echo "<tr>
                                    <td>{$no}</td>
                                    <td>{$row['nomor_tiket']}</td>
                                    <td>" . date('d-m-Y H:i', strtotime($row['tanggal_input'])) . "</td>
                                    <td>{$row['kategori']}</td>
                                    <td>{$row['kendala']}</td>
                                    <td>" . (!empty($row['catatan_it']) ? nl2br(htmlspecialchars($row['catatan_it'])) : '-') . "</td>
                                    <td><span class='badge badge-" . statusColor($row['status']) . "'>{$row['status']}</span></td>
                                    <td>" . renderValidasiButton($row['status_validasi'], $row['id'], $row['status']) . "</td>
                                     <td>
                                      <a href='cetak_tiket_it_software.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-info' title='Lihat Tiket'>
                                        <i class='fas fa-print'></i>
                                      </a>
                                    </td>
                                  </tr>";
                            $no++;
                          }
                        } else {
                          echo "<tr><td colspan='8' class='text-center'>Belum ada tiket.</td></tr>";
                        }

                        function statusColor($status) {
                          switch (strtolower($status)) {
                            case 'menunggu': return 'warning';
                            case 'diproses': return 'info';
                            case 'selesai': return 'success';
                            case 'ditolak': return 'danger';
                            default: return 'secondary';
                          }
                        }

                        function renderValidasiButton($status_validasi, $id, $status_utama) {
                          $status_utama_low = strtolower($status_utama);
                          
                          // Jika masih menunggu validasi awal
                          if ($status_validasi == 'Belum Validasi') {
                            return "
                              <div class='d-flex gap-1'>
                                <form method='post' action='validasi_tiket_software.php' class='form-validasi' style='display:inline-block; margin-right: 5px;'>
                                  <input type='hidden' name='tiket_id' value='$id'>
                                  <input type='hidden' name='ajax' value='1'>
                                  <button type='submit' name='validasi' class='btn btn-sm btn-success'>Terima</button>
                                </form>
                                <form method='post' action='validasi_tiket_software.php' class='form-validasi' style='display:inline-block;'>
                                  <input type='hidden' name='tiket_id' value='$id'>
                                  <input type='hidden' name='ajax' value='1'>
                                  <button type='submit' name='tolak' class='btn btn-sm btn-danger'>Tolak</button>
                                </form>
                              </div>";
                          } 
                          
                          // Jika sudah diterima tapi pengerjaan belum selesai, munculkan tombol selesai
                          if ($status_validasi == 'Diterima' && $status_utama_low == 'diproses') {
                            return "
                              <form method='post' action='selesai_tiket.php' class='form-selesai'>
                                <input type='hidden' name='tiket_id' value='$id'>
                                <input type='hidden' name='tipe' value='software'>
                                <input type='hidden' name='ajax' value='1'>
                                <button type='submit' class='btn btn-sm btn-primary w-100'><i class='fas fa-check-double'></i> Selesai</button>
                              </form>";
                          }

                          // Jika sudah selesai atau ditolak
                          if ($status_validasi == 'Diterima' && $status_utama_low == 'selesai') {
                            return "<span class='badge badge-success'>Diterima & Selesai</span>";
                          }

                          if ($status_validasi == 'Ditolak') {
                            return "<span class='badge badge-danger'>Ditolak</span>";
                          }

                          return "<span class='badge badge-secondary'>$status_validasi</span>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div> <!-- .tab-content -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/sweetalert/sweetalert.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<!-- Script auto-tab -->
<script>
  $(document).ready(function() {
    var hash = window.location.hash;
    if (hash) {
      $('.nav-tabs a[href="' + hash + '"]').tab('show');
    }

    // AJAX Selesai Tiket
    $('.form-selesai').on('submit', function(e) {
      e.preventDefault();
      var form = $(this);
      
      swal({
        title: "Konfirmasi Selesai?",
        text: "Pastikan pengerjaan sudah benar-benar tuntas sebelum menandai Selesai.",
        icon: "warning",
        buttons: ["Batal", "Ya, Selesai!"],
        dangerMode: true,
      }).then((confirm) => {
        if (confirm) {
          $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: form.serialize(),
            success: function(response) {
              if(response.trim() == "success") {
                swal("Berhasil!", "Tiket telah ditandai sebagai Selesai.", "success")
                .then(() => {
                  window.location.hash = 'tiket-saya';
                  location.reload();
                });
              } else {
                swal("Gagal!", "Gagal memperbarui status tiket.", "error");
              }
            },
            error: function() {
              swal("Error!", "Terjadi kesalahan sistem.", "error");
            }
          });
        }
      });
    });

    // AJAX Validasi Tiket
    $('.form-validasi').on('submit', function(e) {
      e.preventDefault();
      var form = $(this);
      var btn = form.find('button[type="submit"]');
      var actionName = btn.attr('name') == 'validasi' ? 'Terima' : 'Tolak';
      
      swal({
        title: "Konfirmasi " + actionName + "?",
        text: "Anda akan melakukan validasi " + actionName + " pada tiket ini.",
        icon: "info",
        buttons: ["Batal", "Ya, Lanjutkan!"],
      }).then((confirm) => {
        if (confirm) {
          // Tambahkan name as data since serialize() doesn't include the clicked button name
          var formData = form.serialize() + '&' + btn.attr('name') + '=1';
          
          $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: formData,
            success: function(response) {
              if(response.trim() == "success") {
                swal("Berhasil!", "Tiket berhasil divalidasi.", "success")
                .then(() => {
                  window.location.hash = 'tiket-saya';
                  location.reload();
                });
              } else {
                swal("Gagal!", response, "error");
              }
            },
            error: function() {
              swal("Error!", "Terjadi kesalahan sistem.", "error");
            }
          });
        }
      });
    });

    // AJAX Submit Tiket Software
    $('#form-order-software').on('submit', function(e) {
      e.preventDefault();
      var form = $(this);
      
      Swal.fire({
        title: 'Submit Request?',
        text: 'Pastikan data yang diisi sudah benar.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu sebentar.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
          });

          $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: form.serialize(),
            dataType: "json",
            success: function(response) {
              if(response.status == "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'SUCCESS!',
                    html: 'Software Service Request berhasil disimpan & dikirim ke Telegram & WA.<br><div class="sr-number">' + response.nomor_tiket + '</div>',
                    confirmButtonText: 'OKE MANTAP!',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.hash = 'tiket-saya';
                    location.reload();
                });
              } else {
                Swal.fire("Gagal!", response.message || "Terjadi kesalahan.", "error");
              }
            },
            error: function() {
              Swal.fire("Error!", "Terjadi kesalahan sistem.", "error");
            }
          });
        }
      });
    });
  });
</script>
</body>
</html>
