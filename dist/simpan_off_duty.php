<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'koneksi.php';
include 'send_wa.php'; 
include 'send_wa_grup.php'; 
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Logika simpan - Cek REQUEST_METHOD karena tombol submit 'simpan' sering tidak terkirim via jQuery .submit()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (
        empty($_POST['nik']) || empty($_POST['nama']) || empty($_POST['jabatan']) ||
        empty($_POST['unit_kerja']) || empty($_POST['kategori']) || empty($_POST['keterangan'])
    ) {
        $_SESSION['flash_message'] = "warning:Harap lengkapi semua field yang berbintang!";
        header("Location: off_duty.php");
        exit;
    }

    $nik        = $_POST['nik'];
    $nama       = $_POST['nama'];
    $jabatan    = $_POST['jabatan'];
    $unit_kerja = $_POST['unit_kerja'];
    $kategori   = $_POST['kategori'];
    $petugas    = $_POST['petugas'] ?? '-';
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $tanggal    = date('Y-m-d H:i:s');

    // Nomor tiket
    $bulan = date('m');
    $tahun = date('Y');
    $cek_terakhir = mysqli_query($conn, 
        "SELECT no_tiket FROM laporan_off_duty 
         WHERE no_tiket LIKE 'TKT%/IT-OFFDUTY/$bulan/$tahun' 
         ORDER BY id DESC LIMIT 1"
    );
    $last = mysqli_fetch_assoc($cek_terakhir);
    if ($last) {
        preg_match('/TKT(\d+)\/IT-OFFDUTY\/\d+\/\d+/', $last['no_tiket'], $matches);
        $urutan = str_pad(intval($matches[1]) + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $urutan = '0001';
    }
    $no_tiket = "TKT{$urutan}/IT-OFFDUTY/{$bulan}/{$tahun}";

    $user_id = $_SESSION['user_id'];
    $query_user = mysqli_query($conn, "SELECT nama_lengkap, atasan_id, no_hp FROM users WHERE id = $user_id");
    $data_user  = mysqli_fetch_assoc($query_user);
    $nama_input = $data_user['nama_lengkap'] ?? 'Tidak Diketahui';

    // Simpan ke DB
    $query = "INSERT INTO laporan_off_duty 
        (no_tiket, nik, nama, jabatan, unit_kerja, kategori, petugas, keterangan, tanggal, user_id, nama_input)
        VALUES 
        ('$no_tiket', '$nik', '$nama', '$jabatan', '$unit_kerja', '$kategori', '$petugas', '$keterangan', '$tanggal', '$user_id', '$nama_input')";

    if (mysqli_query($conn, $query)) {
        // --- Telegram Notification ---
        $token = get_setting('telegram_bot_token');
        $chat_id = get_setting('telegram_chat_id');
        
        $pesan_telegram  = "<b>📢 LAPORAN OFF DUTY BARU</b>\n\n";
        $pesan_telegram .= "🎫 <b>No Tiket:</b> $no_tiket\n";
        $pesan_telegram .= "👤 <b>Nama:</b> $nama\n";
        $pesan_telegram .= "📂 <b>Kategori:</b> $kategori\n";
        $pesan_telegram .= "📝 <b>Ket:</b> $keterangan\n";

        if (!empty($token) && !empty($chat_id)) {
            $url = "https://api.telegram.org/bot$token/sendMessage";
            $data = ['chat_id'=>$chat_id,'text'=>$pesan_telegram,'parse_mode'=>'HTML'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_exec($ch);
            curl_close($ch);
        }

        // --- WhatsApp Notification ---
        $pesan_wa  = "📝 *LAPORAN OFF-DUTY*\nNo Tiket: $no_tiket\nNama: $nama\nKategori: $kategori\n\nKeterangan:\n$keterangan";
        
        // Atasan
        $atasan_id = $data_user['atasan_id'] ?? 0;
        if ($atasan_id) {
            $row_atasan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT no_hp FROM users WHERE id = $atasan_id"));
            if (!empty($row_atasan['no_hp'])) {
                @sendWA($row_atasan['no_hp'], $pesan_wa);
            }
        }

        // Grup IT
        $row_grup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM wa_setting WHERE nama='wa_group_it' LIMIT 1"));
        if ($id_grup = ($row_grup['nilai'] ?? '')) {
            @sendWA($id_grup, $pesan_wa);
        }

        $_SESSION['flash_message'] = "success:Laporan Off-Duty berhasil dikirim!";
        header("Location: off_duty.php");
        exit;
    } else {
        $error = addslashes(mysqli_error($conn));
        $_SESSION['flash_message'] = "error:Gagal menyimpan data ke database: $error";
        header("Location: off_duty.php");
        exit;
    }
} else {
    // Jika bukan POST, balikkan saja
    header("Location: off_duty.php");
    exit;
}
?>
