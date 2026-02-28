<?php
/**
 * ajax_get_karyawan.php - AJAX endpoint
 * Replikasi logika session mapping dari security.php tanpa blocker checkAccess()
 */
ob_start();
require_once __DIR__ . '/../conf/config.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    // Sama persis seperti di security.php: map username → user_id jika belum ada
    if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        $u_esc = mysqli_real_escape_string($conn, $_SESSION['username']);
        $res = mysqli_query($conn, "SELECT id FROM users WHERE username = AES_ENCRYPT('$u_esc', 'bex') LIMIT 1");
        if ($row = mysqli_fetch_assoc($res)) {
            $_SESSION['user_id'] = $row['id'];
        }
    }

    // Cek autentikasi - bisa pakai loggedin ATAU user_id
    $is_logged_in = (
        (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) ||
        !empty($_SESSION['user_id'])
    );

    if (!$is_logged_in) {
        echo json_encode(['error' => 'Unauthorized', 'msg' => 'Sesi tidak valid. Silakan login kembali.']);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID', 'msg' => 'ID tidak valid']);
        exit;
    }

    if (!$conn) {
        echo json_encode(['error' => 'DB Error', 'msg' => 'Koneksi database gagal']);
        exit;
    }

    // User dasar
    $user = [];
    $res = mysqli_query($conn, "SELECT id, nik, nama_lengkap AS nama, jabatan, unit_kerja, email, status FROM users WHERE id = $id LIMIT 1");
    if ($res) {
        $user = mysqli_fetch_assoc($res) ?? [];
    }

    if (empty($user)) {
        echo json_encode(['error' => 'Not Found', 'msg' => "User ID=$id tidak ditemukan"]);
        exit;
    }

    // Info pribadi
    $info_pribadi = [];
    $res2 = mysqli_query($conn, "SELECT * FROM informasi_pribadi WHERE user_id = $id LIMIT 1");
    if ($res2) $info_pribadi = mysqli_fetch_assoc($res2) ?? [];

    // Riwayat pekerjaan
    $pekerjaan = [];
    $res3 = mysqli_query($conn, "SELECT * FROM riwayat_pekerjaan WHERE user_id = $id ORDER BY tanggal_mulai DESC");
    if ($res3) while ($row = mysqli_fetch_assoc($res3)) $pekerjaan[] = $row;

    // Riwayat pendidikan
    $pendidikan = [];
    $res4 = mysqli_query($conn, "SELECT * FROM riwayat_pendidikan WHERE user_id = $id ORDER BY tgl_lulus DESC");
    if ($res4) while ($row = mysqli_fetch_assoc($res4)) $pendidikan[] = $row;

    // Kesehatan
    $kesehatan = [];
    $res5 = mysqli_query($conn, "SELECT * FROM riwayat_kesehatan WHERE user_id = $id LIMIT 1");
    if ($res5) $kesehatan = mysqli_fetch_assoc($res5) ?? [];

    // Gabungkan no_hp dari informasi_pribadi ke user jika ada
    if (!empty($info_pribadi['no_hp'])) {
        $user['no_hp'] = $info_pribadi['no_hp'];
    }

    echo json_encode([
        'success'      => true,
        'user'         => $user,
        'info_pribadi' => $info_pribadi,
        'pekerjaan'    => $pekerjaan,
        'pendidikan'   => $pendidikan,
        'kesehatan'    => $kesehatan,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'PHP Exception',
        'msg'   => $e->getMessage() . ' (line ' . $e->getLine() . ')'
    ]);
}
exit;
?>
