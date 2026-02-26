<?php
include 'security.php';
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tiket_id = intval($_POST['tiket_id']);
    $now = date('Y-m-d H:i:s');

    if (isset($_POST['validasi'])) {
        $status = 'Diterima';
    } elseif (isset($_POST['tolak'])) {
        $status = 'Ditolak';
    } else {
        echo "<script>alert('Aksi tidak dikenali.'); window.history.back();</script>";
        exit;
    }

    $update = mysqli_query($conn, "UPDATE tiket_sarpras 
        SET status_validasi = '$status', waktu_validasi = '$now' 
        WHERE id = '$tiket_id'");

    if ($update) {
        // --- Ambil Data Tiket & Setting buat Notif ---
        $queryTiket = mysqli_query($conn, "SELECT * FROM tiket_sarpras WHERE id = '$tiket_id'");
        if ($dataTiket = mysqli_fetch_assoc($queryTiket)) {
            $nomor_tiket = $dataTiket['nomor_tiket'];
            $nama        = $dataTiket['nama'];
            $jabatan     = $dataTiket['jabatan'];
            $unit_kerja  = $dataTiket['unit_kerja'];
            $kategori    = $dataTiket['kategori'];
            $kendala     = $dataTiket['kendala'];
            $tanggal     = $dataTiket['tanggal_input'];

            // Ambil token dan chat_id dari web_settings
            $resultSetting = mysqli_query($conn, "SELECT setting_key, setting_value FROM web_settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
            $settings = [];
            while ($rowS = mysqli_fetch_assoc($resultSetting)) {
                $settings[$rowS['setting_key']] = $rowS['setting_value'];
            }
            $token = $settings['telegram_bot_token'] ?? '';
            $chat_id = $settings['telegram_chat_id'] ?? '';

            if ($token && $chat_id) {
                $pesan = "<b>✅ SARPRAS SERVICE REQUEST VALIDATION</b>\n";
                $pesan .= "📄 Nomor: <b>$nomor_tiket</b>\n";
                $pesan .= "👤 Nama: $nama\n";
                $pesan .= "💼 Jabatan: $jabatan\n";
                $pesan .= "🏢 Unit: $unit_kerja\n";
                $pesan .= "📂 Kategori: $kategori\n";
                $pesan .= "🛠️ Kendala:\n<pre>$kendala</pre>\n";
                $pesan .= "🗓️ Tanggal: $tanggal\n";
                $pesan .= "📌 Status Validasi: <b>$status</b>\n";
                $pesan .= "⏰ Waktu: $now";

                $url = "https://api.telegram.org/bot$token/sendMessage";
                $data = ['chat_id' => $chat_id, 'text' => $pesan, 'parse_mode' => 'HTML'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }
        echo "<script>alert('Tiket berhasil divalidasi ($status) & notifikasi terkirim.'); window.location.href='order_tiket_sarpras.php';</script>";
    } else {
        echo "<script>alert('Gagal validasi tiket: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Akses tidak valid.'); window.location.href='order_tiket_sarpras.php';</script>";
}
?>







