<?php
session_start();
include 'koneksi.php';
include 'send_wa.php'; // fungsi sendWA()
date_default_timezone_set('Asia/Jakarta');

if (isset($_POST['simpan'])) {
    $nik        = $_POST['nik'];
    $nama       = $_POST['nama'];
    $jabatan    = $_POST['jabatan'];
    $unit_kerja = $_POST['unit_kerja'];
    $kategori   = $_POST['kategori'];
    $lokasi     = $_POST['lokasi'];
    $kendala    = mysqli_real_escape_string($conn, $_POST['kendala']);
    $user_id    = $_SESSION['user_id'];
    $tanggal    = date('Y-m-d H:i:s');

    // Ambil nomor urut terakhir hari ini
    $today = date('Y-m-d');
    $cekNomor = mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket_sarpras WHERE DATE(tanggal_input) = '$today'");
    $dataNomor = mysqli_fetch_assoc($cekNomor);
    $noUrut = $dataNomor['total'] + 1;

    // Format nomor tiket: TKT0001/SARPRAS/DD/MM/YYYY
    $nomor_tiket = 'TKT' . str_pad($noUrut, 4, '0', STR_PAD_LEFT) . '/SARPRAS/' . date('d') . '/' . date('m') . '/' . date('Y');

    // Default status
    $status = 'Menunggu';
    $status_validasi = 'Belum Validasi';

    // Simpan ke database
    $query = "INSERT INTO tiket_sarpras (
                user_id, nik, nama, jabatan, unit_kerja,
                kategori, lokasi, kendala, nomor_tiket, tanggal_input, status, status_validasi
              ) VALUES (
                '$user_id', '$nik', '$nama', '$jabatan', '$unit_kerja',
                '$kategori', '$lokasi', '$kendala', '$nomor_tiket', '$tanggal', '$status', '$status_validasi'
              )";

    if (mysqli_query($conn, $query)) {

        // === Kirim Telegram ===
        $token_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = 'telegram_bot_token' LIMIT 1"));
        $chatid_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = 'telegram_chat_id' LIMIT 1"));
        $token = $token_row['setting_value'] ?? '';
        $chat_id = $chatid_row['setting_value'] ?? '';

        $pesan_telegram  = "<b>📢 SARPRAS SERVICE REQUEST</b>\n\n";
        $pesan_telegram .= "🆔 <b>SR Number:</b> <code>$nomor_tiket</code>\n";
        $pesan_telegram .= "👤 <b>Nama:</b> $nama\n";
        $pesan_telegram .= "💼 <b>Jabatan:</b> $jabatan\n";
        $pesan_telegram .= "🏢 <b>Unit:</b> $unit_kerja\n";
        $pesan_telegram .= "📂 <b>Kategori:</b> $kategori\n";
        $pesan_telegram .= "📍 <b>Lokasi:</b> $lokasi\n";
        $pesan_telegram .= "📝 <b>Kendala:</b>\n<pre>$kendala</pre>\n";
        $pesan_telegram .= "📅 <b>Tanggal:</b> $tanggal\n";

        if ($token && $chat_id) {
            $url = "https://api.telegram.org/bot$token/sendMessage";
            $data = ['chat_id'=>$chat_id, 'text'=>$pesan_telegram, 'parse_mode'=>'HTML'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }

        // === Kirim WhatsApp ===
        $pesan_wa  = "🛠️ *SARPRAS SERVICE REQUEST*\n";
        $pesan_wa .= "SR Number: $nomor_tiket\n";
        $pesan_wa .= "Nama: $nama\nJabatan: $jabatan\nUnit Kerja: $unit_kerja\n";
        $pesan_wa .= "Kategori: $kategori\nLokasi: $lokasi\nKendala: $kendala\n";
        $pesan_wa .= "Tanggal: $tanggal";

        // 1. Kirim ke atasan user (jika ada)
        $row_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT atasan_id, no_hp FROM users WHERE id = $user_id"));
        $atasan_id = $row_user['atasan_id'] ?? 0;
        if ($atasan_id) {
            $row_atasan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT no_hp FROM users WHERE id = $atasan_id"));
            if (!empty($row_atasan['no_hp'])) {
                sendWA($row_atasan['no_hp'], $pesan_wa);
            }
        }

        // 2. Kirim ke grup WA (grup Sarpras)
        $row_grup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key='wa_group_sarpras' LIMIT 1"));
        $id_grup = $row_grup['setting_value'] ?? '';
        if ($id_grup) {
            sendWA($id_grup, $pesan_wa);
        }

        echo "
        <html>
        <head>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&display=swap' rel='stylesheet'>
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
        </head>
        <body style='background: #F0F9FF;'>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'SUCCESS!',
                    html: 'Sarpras Service Request berhasil disimpan & dikirim ke Telegram & WA.<br><div class=\"sr-number\">$nomor_tiket</div>',
                    confirmButtonText: 'OKE MANTAP!',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'order_tiket_sarpras.php';
                    }
                });
            </script>
        </body>
        </html>";
    } else {
        $error = addslashes(mysqli_error($conn));
        echo "<script>
            alert('Gagal menyimpan tiket: $error');
            window.history.back();
        </script>";
    }
}
?>







