<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__); 

// Helper function safely
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    $_SESSION['flash_message'] = "error:Anda tidak memiliki akses ke halaman ini.";
    header("Location: dashboard.php");
    exit;
}

$queryUser = mysqli_query($conn, "SELECT nik, nama_lengkap, jabatan, unit_kerja FROM users WHERE id = '$user_id'");
$userData = mysqli_fetch_assoc($queryUser);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8" />
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport" />
  <title>BexMedia - Off Duty Report</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <link rel="stylesheet" href="assets/css/custom.css" />

  <style>
    .help-icon {
      cursor: pointer;
      font-size: 1.1rem;
      transition: all 0.3s;
    }
    .help-icon:hover {
      transform: scale(1.2);
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
      $breadcrumb = "Technical Support / <strong>Off-Duty Report</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <div class="card card-ice">
            <div class="card-header bg-white">
              <h4 class="mb-0">
                <i class="fas fa-user-clock text-primary mr-2"></i>
                Laporan Off-Duty (Diluar Jam Kerja)
                <i class="fas fa-question-circle help-icon ml-2 text-primary opacity-50" data-toggle="modal" data-target="#helpModal"></i>
              </h4>
            </div>

            <div class="card-body px-4">
              <ul class="nav nav-tabs-ice" id="tabMenu" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="form-tab" data-toggle="tab" href="#form" role="tab">
                    <i class="fas fa-edit mr-2"></i>FORM LAPORAN
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="laporan-tab" data-toggle="tab" href="#laporan" role="tab">
                    <i class="fas fa-history mr-2"></i>LAPORAN SAYA
                  </a>
                </li>
              </ul>

              <div class="tab-content mt-4">
                <!-- Tab Form -->
                <div class="tab-pane fade show active" id="form" role="tabpanel">
                  <form method="POST" action="simpan_off_duty.php" id="formOffDuty">
                    <div class="row">
                      <div class="form-group col-md-4">
                        <label class="text-uppercase small font-weight-bold text-muted">NIK</label>
                        <input type="text" name="nik" class="form-control form-control-ice" value="<?= h($userData['nik']); ?>" readonly style="background: var(--ice-5) !important;">
                      </div>
                      <div class="form-group col-md-4">
                        <label class="text-uppercase small font-weight-bold text-muted">Nama Pelapor</label>
                        <input type="text" name="nama" class="form-control form-control-ice" value="<?= h($userData['nama_lengkap']); ?>" readonly style="background: var(--ice-5) !important;">
                      </div>
                      <div class="form-group col-md-4">
                        <label class="text-uppercase small font-weight-bold text-muted">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control form-control-ice" value="<?= h($userData['jabatan']); ?>" readonly style="background: var(--ice-5) !important;">
                      </div>
                      <div class="form-group col-md-4">
                        <label class="text-uppercase small font-weight-bold text-muted">Unit Kerja</label>
                        <input type="text" name="unit_kerja" class="form-control form-control-ice" value="<?= h($userData['unit_kerja']); ?>" readonly style="background: var(--ice-5) !important;">
                      </div>

                      <div class="form-group col-md-4">
                        <label class="text-uppercase small font-weight-bold text-muted">Kategori Kendala</label>
                        <select class="form-control form-control-ice" name="kategori" id="kategori" required>
                          <option value="">-- Pilih Kategori --</option>
                          <optgroup label="Hardware">
                            <?php
                            $hardware = mysqli_query($conn, "SELECT nama_kategori FROM kategori_hardware");
                            while ($h_row = mysqli_fetch_assoc($hardware)) {
                              echo "<option value='hardware:{$h_row['nama_kategori']}'>{$h_row['nama_kategori']}</option>";
                            }
                            ?>
                          </optgroup>
                          <optgroup label="Software">
                            <?php
                            $software = mysqli_query($conn, "SELECT nama_kategori FROM kategori_software");
                            while ($s_row = mysqli_fetch_assoc($software)) {
                              echo "<option value='software:{$s_row['nama_kategori']}'>{$s_row['nama_kategori']}</option>";
                            }
                            ?>
                          </optgroup>
                        </select>
                      </div>

                      <div class="form-group col-md-4" id="petugas-container" style="display: none;">
                        <label class="text-uppercase small font-weight-bold text-muted">Petugas IT</label>
                        <select name="petugas" class="form-control form-control-ice" id="petugas">
                          <option value="">-- Pilih Petugas --</option>
                        </select>
                      </div>

                      <div class="form-group col-md-12">
                        <label class="text-uppercase small font-weight-bold text-muted">Deskripsi Kendala</label>
                        <textarea name="keterangan" class="form-control form-control-ice" rows="4" required placeholder="Jelaskan detail kendala yang terjadi..."></textarea>
                      </div>
                    </div>
                    <div class="text-right">
                      <button type="submit" name="simpan" class="btn btn-ice px-5 mt-2">
                        <i class="fas fa-paper-plane mr-2"></i>KIRIM LAPORAN OFF-DUTY
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Tab Laporan -->
                <div class="tab-pane fade" id="laporan" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead style="background: var(--ice-5);">
                        <tr>
                          <th class="text-center">NO</th>
                          <th>NO TIKET</th>
                          <th>TANGGAL</th>
                          <th>KATEGORI</th>
                          <th>PETUGAS IT</th>
                          <th>KENDALA</th>
                          <th class="text-center">STATUS</th>
                          <th>CATATAN IT</th>
                          <th class="text-center">AKSI</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $laporan = mysqli_query($conn, "SELECT * FROM laporan_off_duty WHERE user_id = '$user_id' ORDER BY tanggal DESC");
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($laporan)) {
                          echo "<tr>
                            <td class='text-center'>{$no}</td>
                            <td><span class='font-weight-bold'>{$row['no_tiket']}</span></td>
                            <td><small>" . date('d/m/Y H:i', strtotime($row['tanggal'])) . "</small></td>
                            <td>{$row['kategori']}</td>
                            <td>" . h($row['petugas'] ?: '-') . "</td>
                            <td><small>" . h($row['keterangan']) . "</small></td>
                            <td class='text-center'>" . renderValidasiBadge($row['status_validasi']) . "</td>
                            <td><small>" . (!empty($row['catatan_it']) ? h($row['catatan_it']) : '-') . "</small></td>
                            <td class='text-center'>
                              <a href='cetak_off_duty.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-outline-primary rounded-circle' title='Cetak Tiket'>
                                <i class='fa fa-print'></i>
                              </a>
                            </td>
                          </tr>";
                          $no++;
                        }

                        function renderValidasiBadge($status) {
                          $cls = 'badge-secondary';
                          switch (strtolower($status)) {
                            case 'menunggu': $cls = 'badge-warning'; break;
                            case 'diproses': $cls = 'badge-info'; break;
                            case 'selesai': $cls = 'badge-success'; break;
                            case 'ditolak': $cls = 'badge-danger'; break;
                          }
                          return "<span class='badge $cls'>" . ucfirst($status) . "</span>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div> 
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Modal Help -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content card-ice">
      <div class="modal-header bg-white">
        <h5 class="modal-title text-primary"><i class="fas fa-info-circle mr-2"></i>Bantuan Off-Duty</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center py-4">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" style="width:80px; margin-bottom:20px;">
        <p class="px-3"><b>Off-Duty</b> adalah sistem pelaporan kendala IT yang terjadi <u>di luar jam operasional resmi</u>.</p>
        <p class="small text-muted px-3">Gunakan form ini untuk mencatat masalah mendesak agar tim IT tetap dapat memantau dan memberikan estimasi perbaikan meskipun setelah jam pulang kantor.</p>
      </div>
      <div class="modal-footer bg-light container-fluid justify-content-center">
        <button type="button" class="btn btn-ice px-4" data-dismiss="modal">SAYA MENGERTI</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts - Already handled by navbar.php in most part, but we keep custom logic -->
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/scripts.js"></script>

<script>
  $(document).ready(function() {
    $('#kategori').on('change', function () {
      const val = $(this).val();
      const tipe = val.split(':')[0];
      if (tipe === 'hardware' || tipe === 'software') {
        $('#petugas-container').show();
        $('#petugas').html('<option>Memuat...</option>');
        $.getJSON('get_petugas.php?tipe=' + tipe, function (data) {
          let options = '<option value="">-- Pilih Petugas --</option>';
          data.forEach(function (item) {
            options += `<option value="${item.value}">${item.label}</option>`;
          });
          $('#petugas').html(options);
        });
      } else {
        $('#petugas-container').hide();
        $('#petugas').val('');
      }
    });

    // Premium Confirmation for Sending Report
    $('#formOffDuty').on('submit', function(e) {
      if ($(this).data('swal-done')) return true;
      e.preventDefault();
      Swal.fire({
        title: 'Kirim Laporan?',
        text: 'Pastikan data kendala sudah diisi dengan benar.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'Ya, Kirim Sekarang',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $(this).data('swal-done', true).submit();
        }
      });
    });
  });
</script>
</body>
</html>
