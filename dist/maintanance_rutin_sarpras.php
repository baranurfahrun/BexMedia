<?php
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// Cek apakah user boleh mengakses halaman ini
$query = "SELECT 1 FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id' 
          AND menu.file_menu = '$current_file'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
  echo "<script>alert('Anda tidak memiliki akses ke halaman ini.'); window.location.href='dashboard.php';</script>";
  exit;
}

// Proses Simpan
if (isset($_POST['simpan'])) {
  $barang_id = $_POST['barang_id'];
  $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
  $kondisi_fisik = isset($_POST['kondisi_fisik']) ? implode(", ", $_POST['kondisi_fisik']) : '';
  $fungsi_perangkat = isset($_POST['fungsi_perangkat']) ? implode(", ", $_POST['fungsi_perangkat']) : '';

  // Ambil nama teknisi dari user login
  $get_user = mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = '$user_id' LIMIT 1");
  $nama_teknisi = ($get_user && mysqli_num_rows($get_user) > 0) ? mysqli_fetch_assoc($get_user)['nama_lengkap'] : 'Tidak Diketahui';

  $query = "INSERT INTO maintanance_rutin_sarpras 
            (user_id, nama_teknisi, barang_id, kondisi_fisik, fungsi_perangkat, catatan, waktu_input)
            VALUES 
            ('$user_id', '$nama_teknisi', '$barang_id', '$kondisi_fisik', '$fungsi_perangkat', '$catatan', NOW())";

  if (mysqli_query($conn, $query)) {
    // --- NOTIFIKASI WHATSAPP (MULAI) ---
    include_once 'send_wa.php';
    
    // Ambil detail barang untuk pesan WA
    $q_brg = mysqli_query($conn, "SELECT lokasi, merk, kode_ac FROM data_barang_ac WHERE id = '$barang_id' LIMIT 1");
    $d_brg = mysqli_fetch_assoc($q_brg);
    $lokasi_ac = $d_brg['lokasi'] ?? '-';
    $merk_ac   = $d_brg['merk'] ?? '-';
    $kode_ac   = $d_brg['kode_ac'] ?? '-';
    
    $waktu_skrg = date('d-m-Y H:i');
    
    // Format pesan
    $pesan_wa = "!!! MAINTENANCE RUTIN SARPRAS (AC) !!!\n\n";
    $pesan_wa .= "*Lokasi*: " . $lokasi_ac . "\n";
    $pesan_wa .= "*Unit*: " . $merk_ac . " (" . $kode_ac . ")\n";
    $pesan_wa .= "*Teknisi*: " . $nama_teknisi . "\n";
    $pesan_wa .= "*Waktu*: " . $waktu_skrg . " WIB\n\n";
    
    if (!empty($kondisi_fisik)) {
        $pesan_wa .= "*Kondisi Fisik*:\n";
        $kf_arr = explode(", ", $kondisi_fisik);
        foreach ($kf_arr as $kf) { $pesan_wa .= "» " . $kf . "\n"; }
        $pesan_wa .= "\n";
    }
    
    if (!empty($fungsi_perangkat)) {
        $pesan_wa .= "*Fungsi Perangkat*:\n";
        $fp_arr = explode(", ", $fungsi_perangkat);
        foreach ($fp_arr as $fp) { $pesan_wa .= "» " . $fp . "\n"; }
        $pesan_wa .= "\n";
    }
    
    if (!empty($catatan)) {
        $pesan_wa .= "*Catatan*: " . $catatan . "\n";
    }

    $target_wa = get_setting('wa_group_sarpras'); 
    if (empty($target_wa)) $target_wa = get_setting('wa_number');

    if (!empty($target_wa)) {
        sendWA($target_wa, $pesan_wa);
    }
    // --- NOTIFIKASI WHATSAPP (SELESAI) ---

    $_SESSION['flash_message'] = "✅ Data maintenance SARPRAS berhasil disimpan.";
    echo "<script>location.href='maintanance_rutin_sarpras.php';</script>";
    exit;
  } else {
    $error_message = mysqli_error($conn);
    $_SESSION['flash_message'] = "❌ Gagal menyimpan data: $error_message";
  }
}

$data_barang = mysqli_query($conn, "SELECT * FROM data_barang_ac ORDER BY lokasi ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <link rel="icon" href="../images/logo_final.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Maintenance Rutin SARPRAS</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <style>
    #notif-toast { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; display: none; min-width: 300px; }
    .text-success { color: green !important; }
    .text-warning { color: orange !important; }
    .text-danger { color: red !important; }
    .table thead th { background-color: #000 !important; color: #fff !important; text-align: center; }
    .table tbody td { vertical-align: middle; }
    .center { text-align: center; }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
      <?php 
      $breadcrumb = "Technical Support / <strong>Maintanance Rutin Sarpras</strong>";
      include "topbar.php"; 
      ?>
      <section class="section">
        <div class="section-body">
          <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="notif-toast" class="alert alert-info text-center"><?= $_SESSION['flash_message'] ?></div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h4>Form Maintenance Rutin SARPRAS 
                <i class="fas fa-question-circle text-info ml-2" style="cursor: pointer;" data-toggle="modal" data-target="#infoModal"></i>
              </h4>
            </div>
            <div class="card-body">
              <ul class="nav nav-tabs" id="tabMenu" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="form-tab" data-toggle="tab" href="#form" role="tab">Form Maintenance</a></li>
                <li class="nav-item"><a class="nav-link" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Maintenance</a></li>
              </ul>

              <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="form" role="tabpanel">
                  <form method="POST">
                    <div class="form-group">
                      <label for="barang_id">Pilih Barang AC</label>
                      <select name="barang_id" class="form-control" required>
                        <option value="">-- Pilih AC --</option>
                        <?php 
                        $ada_ac = false;
                        mysqli_data_seek($data_barang, 0);
                        while($row = mysqli_fetch_assoc($data_barang)): 
                          $ada_ac = true; ?>
                          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['lokasi']).' - '.htmlspecialchars($row['merk']).' ('.$row['kode_ac'].')' ?></option>
                        <?php endwhile; ?>
                      </select>
                      <?php if (!$ada_ac): ?>
                        <div class="mt-2">
                          <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Data AC masih kosong. Silakan isi dulu di menu <a href="data_barang_ac.php"><strong>Data Barang AC</strong></a>.</small>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="form-group">
                      <label>Kondisi Fisik</label>
                      <div class="form-row">
                        <?php
                        $fisik = ['Bersih', 'Tidak Bocor', 'Body Utuh', 'Kabel Aman', 'Remote Berfungsi', 'Label Aset Jelas'];
                        foreach ($fisik as $f) {
                          echo "<div class='form-check col-md-4'><input class='form-check-input' type='checkbox' name='kondisi_fisik[]' value='$f'><label class='form-check-label'>$f</label></div>";
                        }
                        ?>
                      </div>
                    </div>

                    <div class="form-group">
                      <label>Fungsi Perangkat</label>
                      <div class="form-row">
                        <?php
                        $fungsi = ['Dingin Normal', 'Fan Berfungsi', 'Drainase Lancar', 'Tidak Berisik', 'Kompresor Normal'];
                        foreach ($fungsi as $f) {
                          echo "<div class='form-check col-md-4'><input class='form-check-input' type='checkbox' name='fungsi_perangkat[]' value='$f'><label class='form-check-label'>$f</label></div>";
                        }
                        ?>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="catatan">Catatan Teknisi</label>
                      <textarea name="catatan" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                  </form>
                </div>

                <div class="tab-pane fade" id="data" role="tabpanel">
                  <form method="GET" class="form-inline mb-3">
                    <div class="form-group mr-2"><label class="mr-2">Dari</label><input type="date" name="dari" class="form-control" value="<?= $_GET['dari'] ?? '' ?>" required></div>
                    <div class="form-group mr-2"><label class="mr-2">Sampai</label><input type="date" name="sampai" class="form-control" value="<?= $_GET['sampai'] ?? '' ?>" required></div>
                    <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter"></i> Filter</button>
                    <?php if (!empty($_GET['dari'])): ?>
                      <a href="rekap_maintenance_sarpras.php?dari=<?= urlencode($_GET['dari']) ?>&sampai=<?= urlencode($_GET['sampai']) ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Cetak</a>
                    <?php endif; ?>
                  </form>

                  <div class="table-responsive table-sm">
                    <table class="table table-bordered table-hover table-sm text-nowrap" style="font-size: 13px;">
                      <thead>
                        <tr><th class="center">No</th><th class="center">Kartu</th><th>Kode AC</th><th>Lokasi</th><th>Merk</th><th>Kondisi Fisik</th><th>Fungsi Perangkat</th><th>Catatan</th><th>Teknisi</th><th>Waktu</th><th>Status</th></tr>
                      </thead>
                      <tbody>
                        <?php
                        $no = 1; $where = "";
                        if (!empty($_GET['dari']) && !empty($_GET['sampai'])) {
                          $dari = mysqli_real_escape_string($conn, $_GET['dari']);
                          $sampai = mysqli_real_escape_string($conn, $_GET['sampai']);
                          $where = "WHERE DATE(mr.waktu_input) BETWEEN '$dari' AND '$sampai'";
                        }
                        $limit = 10; $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; $offset = ($page - 1) * $limit;
                        $total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM maintanance_rutin_sarpras mr JOIN data_barang_ac db ON mr.barang_id = db.id $where");
                        $total_data = mysqli_fetch_assoc($total_query)['total'] ?? 0;
                        $total_pages = ceil($total_data / $limit);
                        $query = mysqli_query($conn, "SELECT mr.*, db.kode_ac, db.lokasi, db.merk FROM maintanance_rutin_sarpras mr JOIN data_barang_ac db ON mr.barang_id = db.id $where ORDER BY mr.waktu_input DESC LIMIT $limit OFFSET $offset");
                        while ($row = mysqli_fetch_assoc($query)):
                          $waktu_input = strtotime($row['waktu_input']);
                          $selisih_bulan = floor((time() - $waktu_input) / (30 * 24 * 60 * 60));
                          if ($selisih_bulan < 1) { $status_text = 'Aman'; $status_color = 'text-success font-weight-bold'; }
                          elseif ($selisih_bulan < 2) { $status_text = 'Persiapkan Maintenance'; $status_color = 'text-warning font-weight-bold'; }
                          else { $status_text = 'Wajib Maintenance'; $status_color = 'text-danger font-weight-bold'; }
                        ?>
                        <tr>
                          <td class="center"><?= $no++ ?></td>
                          <td class="center"><a href="cetak_kartu_maintenance.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-id-card text-primary"></i></a></td>
                          <td><?= htmlspecialchars($row['kode_ac']) ?></td>
                          <td><?= htmlspecialchars($row['lokasi']) ?></td>
                          <td><?= htmlspecialchars($row['merk']) ?></td>
                          <td><?= htmlspecialchars($row['kondisi_fisik']) ?></td>
                          <td><?= htmlspecialchars($row['fungsi_perangkat']) ?></td>
                          <td><?= htmlspecialchars($row['catatan']) ?></td>
                          <td><?= htmlspecialchars($row['nama_teknisi']) ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($row['waktu_input'])) ?></td>
                          <td class="<?= $status_color ?>"><?= $status_text ?></td>
                        </tr>
                        <?php endwhile; ?>
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

<div class="modal fade" id="infoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Penjelasan Status</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <p><span class="text-success font-weight-bold">Hijau (Aman)</span> — Maintenance terakhir < 1 bulan.</p>
        <p><span class="text-warning font-weight-bold">Oranye (Persiapkan)</span> — Maintenance terakhir 1–2 bulan.</p>
        <p><span class="text-danger font-weight-bold">Merah (Wajib)</span> — Maintenance terakhir > 2 bulan.</p>
      </div>
    </div>
  </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script>
$(function(){
  if ($('#notif-toast').length) $('#notif-toast').fadeIn(300).delay(2000).fadeOut(500);
  var activeTab = localStorage.getItem('activeTab_sarpras');
  if (activeTab) $('#tabMenu a[href="' + activeTab + '"]').tab('show');
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { localStorage.setItem('activeTab_sarpras', $(e.target).attr('href')); });
});
</script>
</body>
</html>
