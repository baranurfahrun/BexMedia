<?php
session_start();
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'] ?? 0;
$current_file = basename(__FILE__); 

// === Cek akses menu ===
$qAkses = "SELECT 1 FROM akses_menu 
           JOIN menu ON akses_menu.menu_id = menu.id 
           WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$rAkses = mysqli_query($conn, $qAkses);
if (mysqli_num_rows($rAkses) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Data user sudah diambil secara otomatis di security.php ($nik_user, $nama_user, $jabatan_user, $unit_user)

// === Dropdown Master Cuti & Delegasi ===
$masterCuti = mysqli_query($conn, "SELECT * FROM master_cuti ORDER BY nama_cuti ASC");
$delegasiList = mysqli_query($conn, "SELECT id, nama_lengkap FROM users 
                                     WHERE unit_kerja = '$unit_user' 
                                     AND id <> '$user_id' 
                                     AND status = 'active'
                                     ORDER BY nama_lengkap ASC");

// === Data jatah cuti user login (untuk validasi) ===
$tahun = date('Y');
$sqlCuti = "SELECT cuti_id, sisa_hari FROM jatah_cuti WHERE karyawan_id='$user_id' AND tahun='$tahun'";
$resCuti = mysqli_query($conn, $sqlCuti);
$sisaCuti = [];
while ($row = mysqli_fetch_assoc($resCuti)) {
    $sisaCuti[$row['cuti_id']] = $row['sisa_hari'];
}

// === Proses Simpan Pengajuan ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
  $karyawan_id    = $user_id;
  $master_cuti_id = intval($_POST['master_cuti_id']);
  $delegasi_id    = intval($_POST['delegasi_id']);
  $alasan         = mysqli_real_escape_string($conn, $_POST['alasan']);
  $tanggalArray   = $_POST['tanggal'] ?? [];

  // === Cek apakah masih ada cuti yang statusnya pending ===
  $cekPending = mysqli_query($conn, "
    SELECT id FROM pengajuan_cuti 
    WHERE karyawan_id='$karyawan_id' 
      AND (
          status_delegasi='Menunggu' 
          OR status_atasan='Menunggu' 
          OR status_hrd='Menunggu'
      )
    LIMIT 1
  ");

  if (mysqli_num_rows($cekPending) > 0) {
    $_SESSION['flash_message'] = "⚠️ Anda masih memiliki pengajuan cuti yang belum diproses. Tunggu sampai disetujui atau ditolak sebelum mengajukan lagi.";
    header("Location: pengajuan_cuti.php");
    exit;
  }

  // === Validasi field ===
  if ($master_cuti_id <= 0 || empty($tanggalArray)) {
    $_SESSION['flash_message'] = "❌ Semua field wajib diisi!";
  } else {
    sort($tanggalArray);
    $tanggal_mulai   = $tanggalArray[0];
    $tanggal_selesai = end($tanggalArray);
    $lama_hari       = count($tanggalArray);

    // === VALIDASI BARU: Lama cuti > sisa cuti ===
    $sisa = $sisaCuti[$master_cuti_id] ?? 0;
    if ($lama_hari > $sisa) {
      $_SESSION['flash_message'] = "❌ Gagal! Lama cuti ($lama_hari hari) melebihi sisa cuti ($sisa hari).";
      header("Location: pengajuan_cuti.php");
      exit;
    }

    // === Simpan ke DB jika valid ===
    mysqli_begin_transaction($conn);
    try {
      $sql = "INSERT INTO pengajuan_cuti 
                (karyawan_id, cuti_id, delegasi_id, tanggal_mulai, tanggal_selesai, lama_hari, alasan, 
                 status, status_delegasi, status_atasan, status_hrd,
                 acc_delegasi_by, acc_atasan_by, acc_hrd_by) 
              VALUES 
                ('$karyawan_id','$master_cuti_id','$delegasi_id','$tanggal_mulai','$tanggal_selesai',
                 '$lama_hari','$alasan',
                 'Menunggu Delegasi','Menunggu','Menunggu','Menunggu',
                 NULL,NULL,NULL)";
      if(!mysqli_query($conn, $sql)) throw new Exception(mysqli_error($conn));
      $pengajuan_id = mysqli_insert_id($conn);

      foreach ($tanggalArray as $tgl) {
        $tgl = mysqli_real_escape_string($conn, $tgl);
        if(!mysqli_query($conn, "INSERT INTO pengajuan_cuti_detail (pengajuan_id, tanggal) VALUES ('$pengajuan_id','$tgl')")) {
          throw new Exception(mysqli_error($conn));
        }
      }

      mysqli_commit($conn);
      $_SESSION['flash_message'] = "✅ Pengajuan cuti berhasil disimpan.";
    } catch (Exception $e) {
      mysqli_rollback($conn);
      $_SESSION['flash_message'] = "❌ Gagal menyimpan data: " . $e->getMessage();
    }
  }
  header("Location: pengajuan_cuti.php");
  exit;
}

// === Ambil data jatah cuti user login (untuk modal) ===
$sql = "SELECT mc.nama_cuti, mc.id as cuti_id, jc.lama_hari, jc.sisa_hari,
               (jc.lama_hari - jc.sisa_hari) AS terpakai
        FROM jatah_cuti jc
        JOIN master_cuti mc ON jc.cuti_id = mc.id
        WHERE jc.karyawan_id = ? AND jc.tahun = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataCuti = [];
    while($row = $result->fetch_assoc()){ $dataCuti[] = $row; }
    $stmt->close();
} else {
    $dataCuti = [];
}

// === Ambil Data Pengajuan Cuti ===
$dataPengajuan = mysqli_query($conn, "
  SELECT p.*, u.nama_lengkap AS nama_karyawan, mc.nama_cuti, d.nama_lengkap AS nama_delegasi,
         GROUP_CONCAT(DATE_FORMAT(pc.tanggal,'%d-%m-%Y') ORDER BY pc.tanggal SEPARATOR ', ') AS tanggal_cuti
  FROM pengajuan_cuti p
  JOIN users u ON p.karyawan_id = u.id
  JOIN master_cuti mc ON p.cuti_id = mc.id
  LEFT JOIN users d ON p.delegasi_id = d.id
  LEFT JOIN pengajuan_cuti_detail pc ON pc.pengajuan_id = p.id
  WHERE p.karyawan_id = '$user_id'
  GROUP BY p.id
  ORDER BY p.id DESC
");
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="../images/logo_final.png">
    
  
  <meta charset="UTF-8">
  <title>BexMedia Dashboard</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/custom.css">
  <style>
    .cuti-table { font-size: 13px; white-space: nowrap; }
    .cuti-table th, .cuti-table td { padding: 6px 10px; vertical-align: middle; }
    .flash-center {
      position: fixed; top: 20%; left: 50%; transform: translate(-50%, -50%);
      z-index: 1050; min-width: 300px; max-width: 90%; text-align: center;
      padding: 15px; border-radius: 8px; font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
      $breadcrumb = "Employee Hub / <strong>Pengajuan Cuti</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">


          <div class="card">
           <div class="card-header">
  <h4 class="mb-0">
    Pengajuan Cuti 
    <a href="#" data-toggle="modal" data-target="#modalInfoCuti" class="ml-2 text-danger" title="Info Cuti">
      <i class="fas fa-question-circle"></i>
    </a>
  </h4>
</div>


            <div class="card-body">
              <!-- Tab menu -->
              <ul class="nav nav-tabs" id="pengajuanCutiTab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Pengajuan</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Pengajuan</a>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">
                <!-- Form Input -->
                <div class="tab-pane fade show active" id="input" role="tabpanel">
                 <form method="post">
  <div class="row">
    <!-- Kolom Kiri -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Karyawan</label>
        <input type="text" class="form-control" value="<?= h($nik_user) ?> - <?= h($nama_user) ?>" readonly style="background: #F8FAFC">
      </div>
      <div class="form-group">
        <label for="master_cuti_id">Jenis Cuti</label>
        <select name="master_cuti_id" id="master_cuti_id" class="form-control" required>
          <option value="">-- Pilih Jenis Cuti --</option>
          <?php while($mc = mysqli_fetch_assoc($masterCuti)): ?>
            <option value="<?= $mc['id'] ?>"><?= h($mc['nama_cuti']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="delegasi_id">Delegasi (Pengganti)</label>
        <select name="delegasi_id" id="delegasi_id" class="form-control" required>
          <option value="">-- Pilih Delegasi --</option>
          <?php while($d = mysqli_fetch_assoc($delegasiList)): ?>
            <option value="<?= $d['id'] ?>"><?= h($d['nama_lengkap']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group mb-0">
        <label for="alasan" class="font-weight-bold" style="font-size: 0.85rem;">Alasan Pengajuan</label>
        <textarea name="alasan" id="alasan" class="form-control" required placeholder="Tuliskan alasan atau keperluan cuti..." style="height: 145px;"></textarea>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-3 border-primary shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e2e8f0;">
          <h6 class="text-primary mb-0" style="font-size: 0.85rem;"><i class="fas fa-calendar-check"></i> Pilih Rentang Pengajuan</h6>
        </div>
        <div class="card-body p-3">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group mb-2">
                <small class="text-muted font-weight-bold">Mulai Tanggal</small>
                <input type="date" id="rangeMulai" class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group mb-2">
                <small class="text-muted font-weight-bold">Sampai Tanggal</small>
                <input type="date" id="rangeSampai" class="form-control form-control-sm">
              </div>
            </div>
          </div>
          <button type="button" id="btnApplyRange" class="btn-ice btn-sm btn-block mt-2" style="height: 38px;">
            <i class="fas fa-magic"></i> Terapkan Rentang
          </button>
        </div>
      </div>

      <label class="mb-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size: 0.85rem;">Daftar Tanggal Terpilih</span>
        <button type="button" id="btnAddTanggal" class="btn btn-outline-success btn-xs">
          <i class="fas fa-plus"></i> Manual
        </button>
      </label>
      
      <div id="tanggal-wrapper" class="date-tag-wrapper">
        <!-- Chips will appear here -->
        <div class="text-muted w-100 text-center py-2 empty-msg" style="font-size: 0.75rem; font-style: italic;">Belum ada tanggal dipilih</div>
      </div>

      <div class="form-group mt-3">
        <label for="lama_hari" class="font-weight-bold" style="font-size: 0.85rem;">Total Pengajuan</label>
        <div class="input-group">
          <input type="number" name="lama_hari" id="lama_hari" class="form-control font-weight-bold text-primary" readonly required style="background: #eff6ff; font-size: 1.1rem; border: 2px solid #3b82f6;">
          <div class="input-group-append">
            <span class="input-group-text bg-primary text-white border-primary">Hari</span>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="text-right mt-3">
    <button type="submit" name="simpan" class="btn-ice">
      <i class="fas fa-paper-plane"></i> Ajukan Cuti Sekarang
    </button>
  </div>
</form>

                </div>

               
                   <!-- Tabel Data -->
                <div class="tab-pane fade" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered cuti-table">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Karyawan</th>
                          <th>Jenis Cuti</th>
                          <th>Delegasi</th>
                          <th>Tanggal</th>
                          <th>Lama</th>
                          <th>Alasan</th>
                          <th>Status Delegasi</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Status Atasan</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Status HRD</th>
                          <th>Disetujui/Tolak Oleh</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; while ($row = mysqli_fetch_assoc($dataPengajuan)): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= h($row['nama_karyawan']) ?></td>
                            <td><?= h($row['nama_cuti']) ?></td>
                            <td><?= h($row['nama_delegasi'] ?? '-') ?></td>
                            <td><?= h($row['tanggal_cuti']) ?></td>
                            <td><?= $row['lama_hari'] ?> hari</td>
                            <td><?= h($row['alasan']) ?></td>

                            <!-- Status Delegasi -->
                            <td>
                              <span class="badge 
                                <?= $row['status_delegasi']=='Disetujui'?'badge-success':($row['status_delegasi']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_delegasi'] ?>
                              </span>
                            </td>
                            <td><?= h($row['acc_delegasi_by'] ?? '-') ?></td>

                            <!-- Status Atasan -->
                            <td>
                              <span class="badge 
                                <?= $row['status_atasan']=='Disetujui'?'badge-success':($row['status_atasan']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_atasan'] ?>
                              </span>
                            </td>
                            <td><?= h($row['acc_atasan_by'] ?? '-') ?></td>

                            <!-- Status HRD -->
                            <td>
                              <span class="badge 
                                <?= $row['status_hrd']=='Disetujui'?'badge-success':($row['status_hrd']=='Ditolak'?'badge-danger':'badge-warning') ?>">
                                <?= $row['status_hrd'] ?>
                              </span>
                            </td>
                            <td><?= h($row['acc_hrd_by'] ?? '-') ?></td>

                          <td class="text-center">
  <a href="cetak_cuti.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info" title="Cetak">
    <i class="fas fa-print"></i>
  </a>

  <?php if (
    $row['status_delegasi'] == 'Menunggu' && 
    $row['status_atasan'] == 'Menunggu' && 
    $row['status_hrd'] == 'Menunggu'
  ): ?>
    <a href="batal_cuti.php?id=<?= $row['id'] ?>" 
       class="btn btn-sm btn-danger" 
       title="Batalkan Pengajuan" 
       onclick="return confirm('Yakin ingin membatalkan pengajuan cuti ini?');">
       <i class="fas fa-times"></i>
    </a>
  <?php endif; ?>
</td>

                          </tr>
                        <?php endwhile; ?>
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


<!-- Modal Info Cuti -->
<div class="modal fade" id="modalInfoCuti" tabindex="-1" role="dialog" aria-labelledby="modalInfoCutiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalInfoCutiLabel"><i class="fas fa-info-circle"></i> Informasi Jatah Cuti <?= $tahun ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php if (!empty($dataCuti)): ?>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr>
                  <th>Jenis Cuti</th>
                  <th>Jatah (Hari)</th>
                  <th>Terpakai</th>
                  <th>Sisa</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dataCuti as $cuti): ?>
                  <tr>
                    <td><?= h($cuti['nama_cuti']) ?></td>
                    <td><?= $cuti['lama_hari'] ?></td>
                    <td><?= $cuti['terpakai'] ?></td>
                    <td><?= $cuti['sisa_hari'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">
            Data jatah cuti belum tersedia untuk tahun <?= $tahun ?>.
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>


<!-- JS -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
<script>
$(document).ready(function() {
  // Tambah input tanggal manual (Premium SweetAlert2)
  $("#btnAddTanggal").click(function() {
    Swal.fire({
      title: 'Pilih Tanggal Manual',
      html: '<input type="date" id="swal-date" class="form-control">',
      showCancelButton: true,
      confirmButtonText: 'Tambah',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#3B82F6',
      preConfirm: () => {
        const date = Swal.getPopup().querySelector('#swal-date').value;
        if (!date) {
          Swal.showValidationMessage(`Pilih tanggal dulu bro!`);
        }
        return date;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        addDateChip(result.value);
      }
    });
  });

  // Hapus chip tanggal
  $(document).on("click", ".btn-remove-date", function() {
    $(this).closest(".date-chip").remove();
    if ($(".date-chip").length === 0) {
      $("#tanggal-wrapper").append('<div class="text-muted w-100 text-center py-2 empty-msg" style="font-size: 0.75rem; font-style: italic;">Belum ada tanggal dipilih</div>');
    }
    updateJumlahHari();
  });

  function addDateChip(dateStr) {
    if (!dateStr) return;
    $(".empty-msg").remove();

    // Cek duplikat
    var exists = false;
    $("input[name='tanggal[]']").each(function() {
      if ($(this).val() === dateStr) exists = true;
    });
    if (exists) return;

    // Formatting tanggal untuk tampilan
    var d = new Date(dateStr);
    var options = { day: '2-digit', month: 'short', year: 'numeric' };
    var label = d.toLocaleDateString('id-ID', options);

    var html = `
      <div class="date-chip">
        ${label}
        <input type="hidden" name="tanggal[]" value="${dateStr}">
        <button type="button" class="btn-remove-date">&times;</button>
      </div>`;
    $("#tanggal-wrapper").append(html);
    updateJumlahHari();
  }

  // Hitung lama hari otomatis
  $(document).on("change", "input[name='tanggal[]']", function() {
    updateJumlahHari();
  });

  function updateJumlahHari() {
    var count = $("input[name='tanggal[]']").filter(function() {
      return $(this).val() !== "";
    }).length;
    $("#lama_hari").val(count);
  }

  // --- Fitur Auto-Fill Rentang Tanggal ---
  $("#btnApplyRange").click(function() {
    var start = $("#rangeMulai").val();
    var end = $("#rangeSampai").val();
    
    if (!start || !end) {
      Swal.fire('Oops!', 'Pilih tanggal mulai & sampai dulu bro!', 'warning');
      return;
    }

    var startDate = new Date(start);
    var endDate = new Date(end);

    if (endDate < startDate) {
      Swal.fire('Eits!', 'Tanggal sampai gak boleh mendahului tanggal mulai!', 'error');
      return;
    }

    const processRange = () => {
      $("#tanggal-wrapper").empty();
      var current = new Date(startDate);
      while (current <= endDate) {
        addDateChip(current.toISOString().split('T')[0]);
        current.setDate(current.getDate() + 1);
      }
    };

    // Konfirmasi Premium jika sudah ada data
    if ($(".date-chip").length > 0) {
      Swal.fire({
        title: 'Ganti Tanggal?',
        text: "Daftar tanggal yang sudah dipilih akan diganti dengan rentang baru ini.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        confirmButtonText: 'Ya, Ganti!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) processRange();
      });
    } else {
      processRange();
    }
  });
});
</script>
</body>
</html>
