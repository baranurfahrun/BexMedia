<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); 

// === Cek hak akses menu ===
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Data user sudah diambil secara otomatis di security.php ($nik_user, $nama_user, $jabatan_user, $unit_user)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" />
  <title>BexMedia Dashboard</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/custom.css">
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
    .table thead th {
      background-color: #000;
      color: #fff;
      white-space: nowrap;
    }
    .table td {
      vertical-align: middle;
      white-space: nowrap;
    }
    .table-responsive-custom {
      width: 100%;
      overflow-x: auto;
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
      $breadcrumb = "Technical Support / <strong>Sarpras Service Request</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card">
            <div class="card-header">
              <h4>Sarpras Service Request</h4>
            </div>
            <div class="card-body">
              
              <!-- Tabs -->
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="order-tab" data-toggle="tab" href="#order" role="tab">New Request</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="tiket-saya-tab" data-toggle="tab" href="#tiket-saya" role="tab">My Requests</a>
                </li>
              </ul>

              <div class="tab-content mt-4" id="myTabContent">

                <!-- =================== FORM ORDER =================== -->
                <div class="tab-pane fade show active" id="order" role="tabpanel">
                  <form method="POST" action="simpan_tiket_sarpras.php" id="form-order-sarpras">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="simpan" value="1">
                    <div class="row">
                      <div class="form-group col-md-3">
                        <label>NIK</label>
                        <input type="text" name="nik" value="<?php echo h($nik_user); ?>" class="form-control" readonly style="background: #F8FAFC">
                      </div>
                      <div class="form-group col-md-3">
                        <label>Nama</label>
                        <input type="text" name="nama" value="<?php echo h($nama_user); ?>" class="form-control" readonly style="background: #F8FAFC">
                      </div>
                      <div class="form-group col-md-3">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" value="<?php echo h($jabatan_user); ?>" class="form-control" readonly style="background: #F8FAFC">
                      </div>
                      <div class="form-group col-md-3">
                        <label>Unit Kerja</label>
                        <select name="unit_kerja" class="form-control" required>
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

                      <div class="form-group col-md-6">
                        <label>Kategori Permintaan</label>
                        <select name="kategori" class="form-control" required>
                          <option value="">-- Pilih Kategori --</option>
                          <option value="Perbaikan AC">Perbaikan AC</option>
                          <option value="Pengecekan AC">Pengecekan AC</option>
                          <option value="Perbaikan Listrik">Perbaikan Listrik</option>
                          <option value="Perbaikan Furniture">Perbaikan Furniture</option>
                          <option value="Lainnya">Lainnya</option>
                        </select>
                      </div>

                    <div class="form-group col-md-6">
  <label>Lokasi / Ruangan</label>
  <select name="lokasi" class="form-control" required>
    <option value="">-- Pilih Lokasi / Unit --</option>
    <?php
    // Ambil data unit_kerja dari database
    $queryUnit = mysqli_query($conn, "SELECT id, nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
    while ($unit = mysqli_fetch_assoc($queryUnit)) {
        echo "<option value=\"" . htmlspecialchars($unit['nama_unit']) . "\">" . htmlspecialchars($unit['nama_unit']) . "</option>";
    }
    ?>
  </select>
</div>


                      <div class="form-group col-md-12">
                        <label>Kendala / Laporan</label>
                        <textarea name="kendala" class="form-control" rows="3" placeholder="Tuliskan kendala atau permintaan..." required></textarea>
                      </div>
                    </div>
                    <button type="submit" name="simpan" class="btn-ice"><i class="fas fa-paper-plane"></i> Submit Request</button>
                  </form>
                </div>

                <!-- =================== DAFTAR TIKET =================== -->
                <div class="tab-pane fade" id="tiket-saya" role="tabpanel">
                  <div class="table-responsive-custom">
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>SR Number</th>
                          <th>Date</th>
                          <th>Category</th>
                          <th>Issue</th>
                          <th>Status</th>
                          <th>Validation Status</th>
                          <th>Validation Time</th>
                          <th>Technician</th>
                          <th>IT Remarks</th>
                          <th>Action</th>
                          <th>Print</th>
                        </tr>
                      </thead>
                      <tbody>
<?php
$no = 1;
$queryTiket = mysqli_query($conn, "SELECT * FROM tiket_sarpras WHERE user_id = '$user_id' ORDER BY tanggal_input DESC");
if (mysqli_num_rows($queryTiket) > 0) {
  while ($row = mysqli_fetch_assoc($queryTiket)) {
    echo "<tr>
      <td>{$no}</td>
      <td>{$row['nomor_tiket']}</td>
      <td>" . ($row['tanggal_input'] ? date('d-m-Y H:i', strtotime($row['tanggal_input'])) : '-') . "</td>
      <td>{$row['kategori']}</td>
      <td>{$row['kendala']}</td>
      <td><span class='badge badge-" . statusColor($row['status']) . "'>{$row['status']}</span></td>
      <td><span class='badge badge-" . validasiColor($row['status_validasi']) . "'>{$row['status_validasi']}</span></td>
      <td>" . ($row['waktu_validasi'] ? date('d-m-Y H:i', strtotime($row['waktu_validasi'])) : '-') . "</td>
      <td>" . (!empty($row['teknisi_nama']) ? $row['teknisi_nama'] : '-') . "</td>
      <td>" . (!empty($row['catatan_it']) ? $row['catatan_it'] : '-') . "</td>
      <td class='text-center'>";
      
    // Tombol validasi/tolak tetap ada
    $statusLower = strtolower($row['status']);
    $validasiLower = strtolower($row['status_validasi']);

    if ($validasiLower == 'belum validasi') {
      echo "
      <div class='d-flex gap-1'>
        <form method='POST' action='validasi_tiket_sarpras.php' class='form-validasi' style='display:inline-block; margin-right: 5px;'>
          <input type='hidden' name='tiket_id' value='{$row['id']}'>
          <input type='hidden' name='ajax' value='1'>
          <button type='submit' name='validasi' class='btn btn-success btn-sm'>Terima</button>
        </form>
        <form method='POST' action='validasi_tiket_sarpras.php' class='form-validasi' style='display:inline-block;'>
          <input type='hidden' name='tiket_id' value='{$row['id']}'>
          <input type='hidden' name='ajax' value='1'>
          <button type='submit' name='tolak' class='btn btn-danger btn-sm'>Tolak</button>
        </form>
      </div>";
    } elseif ($validasiLower == 'diterima' && $statusLower == 'diproses') {
      echo "
      <form method='POST' action='selesai_tiket.php' class='form-selesai'>
        <input type='hidden' name='tiket_id' value='{$row['id']}'>
        <input type='hidden' name='tipe' value='sarpras'>
        <input type='hidden' name='ajax' value='1'>
        <button type='submit' class='btn btn-primary btn-sm w-100'><i class='fas fa-check-double'></i> Selesai</button>
      </form>";
    } else {
      echo "-";
    }

    echo "</td>";

    // Tambah kolom Cetak Tiket
    echo "<td class='text-center'>
      <a href='cetak_tiket_sarpras.php?id={$row['id']}' target='_blank' class='btn btn-ice-blue btn-sm' title='Print Request'>
        <i class='fas fa-print'></i>
      </a>
    </td>";

    echo "</tr>";
    $no++;
  }
} else {
  echo "<tr><td colspan='12' class='text-center'>Belum ada request yang dibuat.</td></tr>";
}

// Fungsi warna status
function statusColor($status) {
  switch (strtolower($status)) {
    case 'menunggu': return 'warning';
    case 'diproses': return 'info';
    case 'selesai': return 'success';
    case 'tidak bisa diperbaiki': return 'danger';
    default: return 'secondary';
  }
}

function validasiColor($status_validasi) {
  switch (strtolower($status_validasi)) {
    case 'belum validasi': return 'secondary';
    case 'diterima': return 'success';
    case 'ditolak': return 'danger';
    default: return 'light';
  }
}
?>
</tbody>

                    </table>
                  </div>
                </div>
              </div><!-- end tab -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- JS -->
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

    // AJAX Submit Tiket Sarpras
    $('#form-order-sarpras').on('submit', function(e) {
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
                    html: 'Sarpras Service Request berhasil disimpan & dikirim ke Telegram & WA.<br><div class="sr-number">' + response.nomor_tiket + '</div>',
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
