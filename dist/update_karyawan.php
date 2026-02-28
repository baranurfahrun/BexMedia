<?php
/**
 * update_karyawan.php - AJAX endpoint update data karyawan
 * Jangan include security.php langsung (bisa blokir AJAX)
 */
ob_start();
require_once __DIR__ . '/../conf/config.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    // Replikasi session mapping dari security.php
    if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        $u_esc = mysqli_real_escape_string($conn, $_SESSION['username']);
        $res_u = mysqli_query($conn, "SELECT id FROM users WHERE username = AES_ENCRYPT('$u_esc', 'bex') LIMIT 1");
        if ($row_u = mysqli_fetch_assoc($res_u)) {
            $_SESSION['user_id'] = $row_u['id'];
        }
    }

    $is_logged_in = (
        (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) ||
        !empty($_SESSION['user_id'])
    );

    if (!$is_logged_in) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID karyawan tidak valid: ' . $id]);
        exit;
    }

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
        exit;
    }

    // --- Sanitasi input utama ---
    $nik          = mysqli_real_escape_string($conn, trim($_POST['nik'] ?? ''));
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
    $jabatan      = mysqli_real_escape_string($conn, trim($_POST['jabatan'] ?? ''));
    $unit_kerja   = mysqli_real_escape_string($conn, trim($_POST['unit_kerja'] ?? ''));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp        = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $status_raw   = $_POST['status'] ?? 'active';
    $status       = in_array($status_raw, ['active', 'pending', 'blocked']) ? $status_raw : 'active';

    if (empty($nama_lengkap)) {
        echo json_encode(['success' => false, 'message' => 'Nama lengkap tidak boleh kosong.']);
        exit;
    }

    // --- Cek duplikasi email (jika email diisi) ---
    if (!empty($email)) {
        $cekEmail = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id LIMIT 1");
        if ($cekEmail && mysqli_num_rows($cekEmail) > 0) {
            echo json_encode(['success' => false, 'message' => "Email '$email' sudah digunakan oleh user lain. Gunakan email yang berbeda."]);
            exit;
        }
    }

    // --- Cek duplikasi NIK (jika NIK diisi) ---
    if (!empty($nik)) {
        $cekNik = mysqli_query($conn, "SELECT id FROM users WHERE nik = '$nik' AND id != $id LIMIT 1");
        if ($cekNik && mysqli_num_rows($cekNik) > 0) {
            echo json_encode(['success' => false, 'message' => "NIK '$nik' sudah digunakan oleh karyawan lain."]);
            exit;
        }
    }

    // 1. Update tabel users
    $updateEmail = !empty($email) ? "email = '$email'," : "email = email,";
    $updateNik   = !empty($nik)   ? "nik = '$nik',"   : "nik = nik,";

    $qUser = "UPDATE users SET 
        $updateNik
        nama_lengkap = '$nama_lengkap',
        jabatan      = '$jabatan',
        unit_kerja   = '$unit_kerja',
        $updateEmail
        status       = '$status'
        WHERE id = $id";

    if (!mysqli_query($conn, $qUser)) {
        echo json_encode(['success' => false, 'message' => 'Gagal update data utama: '. mysqli_error($conn)]);
        exit;
    }

    // --- Sanitasi data pribadi ---
    $jk_raw     = $_POST['jenis_kelamin'] ?? '';
    $jk         = in_array($jk_raw, ['L', 'P']) ? "'$jk_raw'" : 'NULL';
    $tmpat      = mysqli_real_escape_string($conn, trim($_POST['tempat_lahir'] ?? ''));
    $tgl_lahir  = !empty($_POST['tanggal_lahir']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_lahir']) . "'" : 'NULL';
    $ktp        = mysqli_real_escape_string($conn, trim($_POST['no_ktp'] ?? ''));
    $agama      = mysqli_real_escape_string($conn, trim($_POST['agama'] ?? ''));
    $st_nikah   = mysqli_real_escape_string($conn, trim($_POST['status_pernikahan'] ?? ''));
    $alamat     = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));

    // 2. UPSERT informasi_pribadi
    $cekIp = mysqli_query($conn, "SELECT id FROM informasi_pribadi WHERE user_id = $id LIMIT 1");
    if ($cekIp && mysqli_num_rows($cekIp) > 0) {
        mysqli_query($conn, "UPDATE informasi_pribadi SET
            jenis_kelamin = $jk,
            tempat_lahir  = '$tmpat',
            tanggal_lahir = $tgl_lahir,
            no_ktp        = '$ktp',
            no_hp         = '$no_hp',
            agama         = '$agama',
            status_pernikahan = '$st_nikah',
            alamat        = '$alamat'
            WHERE user_id = $id");
    } else {
        mysqli_query($conn, "INSERT INTO informasi_pribadi 
            (user_id, jenis_kelamin, tempat_lahir, tanggal_lahir, no_ktp, no_hp, agama, status_pernikahan, alamat)
            VALUES ($id, $jk, '$tmpat', $tgl_lahir, '$ktp', '$no_hp', '$agama', '$st_nikah', '$alamat')");
    }

    // --- Data kesehatan ---
    $allowed_gol  = ['A', 'B', 'AB', 'O'];
    $allowed_vaks = ['Belum', 'Vaksin 1', 'Vaksin 2', 'Booster'];
    $gol_darah    = mysqli_real_escape_string($conn, trim($_POST['gol_darah'] ?? ''));
    $st_vaksin    = mysqli_real_escape_string($conn, trim($_POST['status_vaksinasi'] ?? ''));
    $no_bpjs_kes  = mysqli_real_escape_string($conn, trim($_POST['no_bpjs_kesehatan'] ?? ''));
    $no_bpjs_tk   = mysqli_real_escape_string($conn, trim($_POST['no_bpjs_kerja'] ?? ''));

    // 3. UPSERT riwayat_kesehatan
    $cekK = mysqli_query($conn, "SELECT id FROM riwayat_kesehatan WHERE user_id = $id LIMIT 1");
    if ($cekK && mysqli_num_rows($cekK) > 0) {
        mysqli_query($conn, "UPDATE riwayat_kesehatan SET
            gol_darah         = '$gol_darah',
            status_vaksinasi  = '$st_vaksin',
            no_bpjs_kesehatan = '$no_bpjs_kes',
            no_bpjs_kerja     = '$no_bpjs_tk'
            WHERE user_id = $id");
    } else {
        mysqli_query($conn, "INSERT INTO riwayat_kesehatan
            (user_id, gol_darah, status_vaksinasi, no_bpjs_kesehatan, no_bpjs_kerja)
            VALUES ($id, '$gol_darah', '$st_vaksin', '$no_bpjs_kes', '$no_bpjs_tk')");
    }

    echo json_encode(['success' => true, 'message' => 'Data karyawan berhasil diperbarui.']);

} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')'
    ]);
}
exit;
?>
