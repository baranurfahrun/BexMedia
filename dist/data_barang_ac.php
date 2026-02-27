<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$user_id = $_SESSION['user_id'];
$current_file = basename(__FILE__);

// 🔒 Cek Hak Akses
$query_akses = "SELECT 1 FROM akses_menu JOIN menu ON akses_menu.menu_id = menu.id WHERE akses_menu.user_id = '$user_id' AND menu.file_menu = '$current_file'";
$res_akses = mysqli_query($conn, $query_akses);
if (!$res_akses || mysqli_num_rows($res_akses) == 0) {
  echo "<script>alert('Akses Ditolak!'); window.location.href='dashboard.php';</script>";
  exit;
}

// 📌 SYNC STATE VIA AJAX
if (isset($_POST['action']) && $_POST['action'] == 'save_state') {
    if(isset($_POST['format'])) $_SESSION['ac_format'] = $_POST['format'];
    if(isset($_POST['mode']))   $_SESSION['ac_mode']   = $_POST['mode'];
    if(isset($_POST['tab']))    $_SESSION['ac_tab']    = $_POST['tab'];
    echo "OK";
    exit;
}

// 📌 GENERATOR KODE
function generate_no_barang_ac($conn) {
  if (isset($_SESSION['ac_mode']) && $_SESSION['ac_mode'] == 'manual' && isset($_SESSION['ac_format'])) {
      return $_SESSION['ac_format'];
  }
  $query = "SELECT kode_ac FROM data_barang_ac ORDER BY id DESC LIMIT 1";
  $result = mysqli_query($conn, $query);
  $suffix = "RSPH-AC"; $tail = date('Y');   
  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $last_no = $row['kode_ac'];
    $parts = explode('/', $last_no);
    if (count($parts) >= 1 && is_numeric($parts[0])) {
      $new_number = intval($parts[0]) + 1;
      if (isset($parts[1])) $suffix = $parts[1];
      if (isset($parts[2])) $tail = $parts[2];
    } else {
      $new_number = (preg_match('/(\d+)$/', $last_no, $m)) ? intval($m[0]) + 1 : 1;
    }
  } else { $new_number = 1; }
  return sprintf("%05d", $new_number) . "/" . $suffix . "/" . $tail;
}

$next_no_barang = generate_no_barang_ac($conn);
$current_mode = $_SESSION['ac_mode'] ?? 'auto';

// 📌 SIMPAN DATA
if (isset($_POST['simpan'])) {
    $kode_ac = mysqli_real_escape_string($conn, $_POST['no_barang_final']);
    unset($_SESSION['ac_format']); unset($_SESSION['ac_mode']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $merk = mysqli_real_escape_string($conn, $_POST['merk']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    $no_seri = mysqli_real_escape_string($conn, $_POST['no_seri']);
    $tahun_beli = mysqli_real_escape_string($conn, $_POST['tahun_beli']);
    $kondisi = mysqli_real_escape_string($conn, $_POST['kondisi']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO data_barang_ac (user_id, kode_ac, lokasi, merk, tipe, kapasitas, no_seri, tahun_beli, kondisi, status, keterangan, waktu_input) 
            VALUES ('$user_id', '$kode_ac', '$lokasi', '$merk', '$tipe', '$kapasitas', '$no_seri', '$tahun_beli', '$kondisi', '$status', '$keterangan', '$now')";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['flash_message'] = "✅ Data AC berhasil disimpan dengan Kode: <strong>$kode_ac</strong>";
        header("Location: data_barang_ac.php?tab=data"); exit;
    }
}

$lokasi_query = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
$data_ac_query = mysqli_query($conn, "SELECT * FROM data_barang_ac ORDER BY id DESC");
$rekap_kondisi = mysqli_query($conn, "SELECT kondisi, COUNT(*) AS jumlah FROM data_barang_ac GROUP BY kondisi");
// 📌 DETEKSI TAB AKTIF (Prioritas GET, lalu SESSION)
$active_tab = $_GET['tab'] ?? $_SESSION['ac_tab'] ?? 'input';
if (isset($_GET['tab'])) $_SESSION['ac_tab'] = $_GET['tab']; // Update session jika ada di GET
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="../images/logo_final.png">
  <title>Data Barang AC | BexMedia Dashboard</title>
  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/components.css" />
  <style>
    #notif-toast { display: none; }
    .no-barang-preview { background: #e3f2fd; border: 2px dashed #1976d2; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
    .no-barang-preview .label { font-size: 12px; color: #666; margin-bottom: 5px; }
    .no-barang-preview .value { font-size: 20px; font-weight: bold; color: #1976d2; font-family: 'Courier New', monospace; }
    .btn-ice-blue { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border: none; color: #fff; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); border-radius: 8px; font-weight: 600; transition: all 0.3s; }
    .btn-ice-blue:hover { background: linear-gradient(135deg, #2980b9 0%, #3498db 100%); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4); color: #fff; }
  </style>
</head>
<body>
<div id="app">
  <div class="main-wrapper main-wrapper-1">
    <?php include 'navbar.php'; include 'sidebar.php'; ?>
    <div class="main-content">
      <section class="section">
        <div class="section-body">
          <?php if (isset($_SESSION['flash_message'])): ?>
            <div id="notif-toast" class="alert alert-info text-center"><?= $_SESSION['flash_message'] ?></div>
            <?php unset($_SESSION['flash_message']); ?>
          <?php endif; ?>

          <div class="card">
            <div class="card-header"><h4>Manajemen Data Barang AC</h4></div>
            <div class="card-body">
              <ul class="nav nav-tabs" id="dataTab" role="tablist">
                <li class="nav-item"><a class="nav-link <?= ($active_tab == 'input') ? 'active' : '' ?>" id="input-tab" data-toggle="tab" href="#input" role="tab">Input Barang</a></li>
                <li class="nav-item"><a class="nav-link <?= ($active_tab == 'data') ? 'active' : '' ?>" id="data-tab" data-toggle="tab" href="#data" role="tab">Data Barang</a></li>
                <li class="nav-item"><a class="nav-link <?= ($active_tab == 'laporan') ? 'active' : '' ?>" id="laporan-tab" data-toggle="tab" href="#laporan" role="tab">Laporan</a></li>
              </ul>
              <div class="tab-content mt-3">
                <div class="tab-pane fade <?= ($active_tab == 'input') ? 'show active' : '' ?>" id="input" role="tabpanel">
                  <form method="POST">
                    <div class="no-barang-preview">
                        <input type="hidden" name="is_manual" id="is_manual" value="<?= ($current_mode == 'manual') ? '1' : '0' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="w-100">
                                <div class="label"><i class="fas fa-barcode"></i> <span id="label-no-barang"><?= ($current_mode == 'manual') ? 'Atur Nomor Barang Secara Manual:' : 'Nomor Barang Otomatis (akan digenerate saat simpan):' ?></span></div>
                                <div id="no-barang-display" class="value" style="<?= ($current_mode == 'manual' && isset($_SESSION['ac_format'])) ? '' : (($current_mode == 'manual') ? 'display:none;' : '') ?>"><?= $next_no_barang ?></div>
                                <div id="no-barang-input-wrapper" style="display:none;">
                                    <input type="text" id="no_barang_manual_field" class="form-control form-control-lg font-weight-bold text-primary" value="<?= $next_no_barang ?>" style="font-family: 'Courier New', monospace; border: 2px solid #3498db;">
                                </div>
                                <input type="hidden" name="no_barang_final" id="no_barang_final" value="<?= $next_no_barang ?>">
                            </div>
                            <div class="text-nowrap ml-3 d-flex flex-column" style="gap: 5px;">
                                <button type="button" id="btn-toggle-manual" class="btn btn-sm btn-ice-blue w-100"><i class="fas fa-edit"></i> <?= ($current_mode == 'manual') ? 'Ganti Format' : 'Atur Manual' ?></button>
                                <button type="button" id="btn-apply-manual" class="btn btn-sm btn-success w-100" style="display:none;"><i class="fas fa-check"></i> Gunakan</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-4"><label>Merk</label><input type="text" name="merk" class="form-control" required></div>
                      <div class="form-group col-md-4"><label>Tipe</label><input type="text" name="tipe" class="form-control"></div>
                      <div class="form-group col-md-4"><label>PK</label><input type="text" name="kapasitas" class="form-control"></div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-4"><label>No. Seri</label><input type="text" name="no_seri" class="form-control"></div>
                      <div class="form-group col-md-4"><label>Tahun Beli</label><input type="number" name="tahun_beli" class="form-control" value="<?= date('Y') ?>"></div>
                      <div class="form-group col-md-4"><label>Lokasi</label>
                        <select name="lokasi" class="form-control" required>
                          <?php mysqli_data_seek($lokasi_query, 0); while ($r = mysqli_fetch_assoc($lokasi_query)) : ?>
                            <option value="<?= $r['nama_unit'] ?>"><?= $r['nama_unit'] ?></option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-6"><label>Kondisi</label><select name="kondisi" class="form-control"><option value="Baik">Baik</option><option value="Perlu Servis">Perlu Servis</option><option value="Rusak Berat">Rusak Berat</option></select></div>
                      <div class="form-group col-md-6"><label>Status</label><select name="status" class="form-control"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select></div>
                    </div>
                    <div class="form-group"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="2"></textarea></div>
                    <button type="submit" name="simpan" class="btn btn-ice-blue btn-lg"><i class="fas fa-save"></i> Simpan Data Barang</button>
                    <button type="reset" class="btn btn-secondary btn-lg"><i class="fas fa-redo"></i> Reset Form</button>
                  </form>
                </div>
                <div class="tab-pane fade <?= ($active_tab == 'data') ? 'show active' : '' ?>" id="data" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-striped table-md">
                      <thead><tr><th>No</th><th>Kode AC</th><th>Identitas</th><th>Lokasi</th><th>Aksi</th></tr></thead>
                      <tbody>
                        <?php $n=1; while ($d=mysqli_fetch_assoc($data_ac_query)): ?>
                        <tr>
                          <td><?= $n++ ?></td><td class="font-weight-bold text-primary"><?= $d['kode_ac'] ?></td>
                          <td><?= $d['merk'] ?> (<?= $d['kapasitas'] ?> PK)</td><td><?= $d['lokasi'] ?></td>
                          <td>
                            <button class="btn btn-warning btn-sm editBtn" 
                                    data-id="<?= $d['id'] ?>" 
                                    data-kode="<?= $d['kode_ac'] ?>"
                                    data-merk="<?= $d['merk'] ?>"
                                    data-tipe="<?= $d['tipe'] ?>"
                                    data-kapasitas="<?= $d['kapasitas'] ?>"
                                    data-noseri="<?= $d['no_seri'] ?>"
                                    data-tahun="<?= $d['tahun_beli'] ?>"
                                    data-lokasi="<?= $d['lokasi'] ?>"
                                    data-kondisi="<?= $d['kondisi'] ?>"
                                    data-status="<?= $d['status'] ?>"
                                    data-keterangan="<?= htmlspecialchars($d['keterangan']) ?>">
                              <i class="fas fa-edit"></i>
                            </button>
                            <a href="hapus_barang_ac.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></a>
                          </td>
                        </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="tab-pane fade <?= ($active_tab == 'laporan') ? 'show active' : '' ?>" id="laporan" role="tabpanel">
                  <div class="card"><div class="card-header bg-success text-white"><h5>Rekap Kondisi</h5></div><div class="card-body"><ul class="list-group">
                    <?php mysqli_data_seek($rekap_kondisi, 0); while($kon = mysqli_fetch_assoc($rekap_kondisi)): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><?= $kon['kondisi'] ?> <span class="badge badge-success badge-pill"><?= $kon['jumlah'] ?></span></li>
                    <?php endwhile; ?>
                  </ul></div></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editForm">
            <div class="modal-content">
                <div class="modal-header bg-warning"><h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Barang AC</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="e_id">
                    <div class="row">
                        <div class="form-group col-md-6"><label>Kode AC</label><input type="text" name="kode_ac" id="e_kode" class="form-control" readonly></div>
                        <div class="form-group col-md-6"><label>Merk</label><input type="text" name="merk" id="e_merk" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4"><label>Tipe</label><input type="text" name="tipe" id="e_tipe" class="form-control"></div>
                        <div class="form-group col-md-4"><label>PK</label><input type="text" name="kapasitas" id="e_kapasitas" class="form-control"></div>
                        <div class="form-group col-md-4"><label>No Seri</label><input type="text" name="no_seri" id="e_noseri" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6"><label>Tahun Beli</label><input type="number" name="tahun_beli" id="e_tahun" class="form-control"></div>
                        <div class="form-group col-md-6"><label>Lokasi</label>
                            <select name="lokasi" id="e_lokasi" class="form-control">
                                <?php mysqli_data_seek($lokasi_query, 0); while($u=mysqli_fetch_assoc($lokasi_query)): ?><option value="<?= $u['nama_unit'] ?>"><?= $u['nama_unit'] ?></option><?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6"><label>Kondisi</label><select name="kondisi" id="e_kondisi" class="form-control"><option value="Baik">Baik</option><option value="Perlu Servis">Perlu Servis</option><option value="Rusak Berat">Rusak Berat</option></select></div>
                        <div class="form-group col-md-6"><label>Status</label><select name="status" id="e_status" class="form-control"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select></div>
                    </div>
                    <div class="form-group"><label>Keterangan</label><textarea name="keterangan" id="e_keterangan" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning text-white font-weight-bold">UPDATE DATA</button></div>
            </div>
        </form>
    </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<!-- 💎 SWEETALERT2 FOR PREMIUM POPUPS 💎 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/stisla.js"></script>
<script src="assets/js/scripts.js"></script>
<script>
$(function(){
  // 💎 FLASH MESSAGE AUTO SHOW SWEETALERT 💎
  var toastContent = $('#notif-toast').html();
  if (toastContent) {
      Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          html: toastContent,
          timer: 3000,
          showConfirmButton: false,
          showClass: { popup: 'animate__animated animate__fadeInDown' },
          hideClass: { popup: 'animate__animated animate__fadeOutUp' }
      });
  }

  function syncState(format, mode) {
      $.post('data_barang_ac.php', { action: 'save_state', format: format, mode: mode });
  }

  $('#btn-toggle-manual').on('click', function() {
      var wrapper = $('#no-barang-input-wrapper');
      var display = $('#no-barang-display');
      var label = $('#label-no-barang');
      var btn = $(this);
      if (wrapper.is(':hidden')) {
          wrapper.show(); display.hide();
          label.text('Atur Nomor Barang Secara Manual:');
          btn.html('<i class="fas fa-magic"></i> Pakai Otomatis').removeClass('btn-ice-blue').addClass('btn-secondary');
          $('#btn-apply-manual').fadeIn(); $('#no_barang_manual_field').focus();
      } else {
          wrapper.hide(); display.show();
          label.text('Nomor Barang Otomatis (akan digenerate saat simpan):');
          btn.html('<i class="fas fa-edit"></i> Atur Manual').removeClass('btn-secondary').addClass('btn-ice-blue');
          $('#btn-apply-manual').hide();
          syncState('', 'auto'); location.reload();
      }
  });

  $('#btn-apply-manual').on('click', function() {
      var val = $('#no_barang_manual_field').val();
      if(val.trim() == '') {
          Swal.fire({ icon: 'error', title: 'Oops...', text: 'Nomor barang tidak boleh kosong!' });
          return;
      }
      $('#no-barang-display').text(val).show(); $('#no-barang-input-wrapper').hide(); $(this).hide();
      $('#btn-toggle-manual').html('<i class="fas fa-edit"></i> Ganti Format').removeClass('btn-secondary').addClass('btn-ice-blue');
      $('#no_barang_final').val(val); syncState(val, 'manual');
      
      Swal.fire({
          icon: 'success',
          title: 'Format Diterapkan',
          text: 'Nomor barang manual berhasil dikunci!',
          timer: 1500,
          showConfirmButton: false
      });
  });

  $(document).on('click','.editBtn',function(){
    $('#e_id').val($(this).data('id')); 
    $('#e_kode').val($(this).data('kode'));
    $('#e_merk').val($(this).data('merk'));
    $('#e_tipe').val($(this).data('tipe'));
    $('#e_kapasitas').val($(this).data('kapasitas'));
    $('#e_noseri').val($(this).data('noseri'));
    $('#e_tahun').val($(this).data('tahun'));
    $('#e_lokasi').val($(this).data('lokasi'));
    $('#e_kondisi').val($(this).data('kondisi'));
    $('#e_status').val($(this).data('status'));
    $('#e_keterangan').val($(this).data('keterangan'));
    $('#editModal').modal('show');
  });

  $('#editForm').submit(function(e){
    e.preventDefault();
    $.post('update_barang_ac.php', $(this).serialize(), function(r){
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: r,
            confirmButtonColor: '#3498db'
        }).then(() => {
            window.location.href = 'data_barang_ac.php?tab=data';
        });
    });
  });

  // Percantik Konfirmasi Hapus
  $(document).on('click', '.btn-hapus', function(e) {
      e.preventDefault();
      var url = $(this).attr('href');
      Swal.fire({
          title: 'Yakin hapus data?',
          text: "Data yang dihapus tidak bisa dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Ya, Hapus!',
          cancelButtonText: 'Batal'
      }).then((result) => {
          if (result.isConfirmed) {
              window.location.href = url;
          }
      });
  });

  // 💎 SMART TAB PERSISTENCE (AJAX + SESSION) 💎
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      var target = $(e.target).attr("href").replace('#', '');
      
      // Simpan ke Session via AJAX agar saat refresh tetap di tab ini
      $.post('data_barang_ac.php', { action: 'save_state', tab: target });
      
      // Update URL tanpa reload (opsional tapi bagus buat bookmark)
      var url = new URL(window.location.href);
      url.searchParams.set('tab', target);
      window.history.replaceState(null, null, url.href);
  });

  $('button[type="reset"]').on('click', function() {
      $('#no-barang-input-wrapper').hide(); $('#no-barang-display').show();
      $('#label-no-barang').text('Nomor Barang Otomatis (akan digenerate saat simpan):');
      $('#btn-toggle-manual').html('<i class="fas fa-edit"></i> Atur Manual').removeClass('btn-secondary').addClass('btn-ice-blue');
  });
});
</script>
</body>
</html>
