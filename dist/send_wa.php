<?php
include 'koneksi.php'; // koneksi ke DB
date_default_timezone_set('Asia/Jakarta');

/**
 * Kirim WhatsApp melalui gateway
 * @param string $nomor Format: 628xxxxxxxxx
 * @param string $pesan Pesan yang akan dikirim
 * @return bool true jika berhasil, false jika gagal
 */
function sendWA($nomor, $pesan) {
    global $conn, $conn_wa;

    if (empty($nomor) || empty($pesan)) {
        error_log("WA gagal: nomor atau pesan kosong.");
        return false;
    }

    // Load koneksi khusus database WA
    include_once 'koneksi_wa.php'; 
    
    if (!isset($conn_wa) || $conn_wa->connect_error) {
        error_log("WA gagal: Koneksi ke gateway database bermasalah.");
        return false;
    }

    // Format nomor: hilangkan karakter kecuali angka, @, dan - (penting untuk Group ID)
    $nowa = preg_replace('/[^0-9@\-]/', '', $nomor);
    
    // Jika diawali 0, ubah ke 62
    if (substr($nowa, 0, 1) == '0') {
        $nowa = '62' . substr($nowa, 1);
    }
    
    // Tambahkan suffix jika belum ada @
    if (strpos($nowa, '@') === false) {
        // Jika mengandung tanda hubung '-', biasanya itu Group ID
        if (strpos($nowa, '-') !== false) {
            $nowa .= '@g.us';
        } else {
            $nowa .= '@c.us';
        }
    }

    $tanggal_jam = date('Y-m-d H:i:s');
    $status_wa   = 'ANTRIAN';
    $source      = 'KHANZA'; // Samakan dengan KHANZA
    $sender      = 'NODEJS';  // Samakan dengan NODEJS atau DELPHI
    $type        = 'TEXT';

    // Query INSERT sesuai schema di database.sql (lowercase)
    $sql = "INSERT INTO wa_outbox (nowa, pesan, tanggal_jam, status, source, sender, type) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn_wa->prepare($sql);

    if (!$stmt) {
        error_log("WA gagal prepare: " . $conn_wa->error);
        return false;
    }

    $stmt->bind_param("sssssss", $nowa, $pesan, $tanggal_jam, $status_wa, $source, $sender, $type);
    
    if ($stmt->execute()) {
        $success = ($stmt->affected_rows > 0);
        $stmt->close();
        return $success;
    } else {
        error_log("WA gagal execute: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Contoh pemanggilan fungsi:
 * include 'send_wa.php';
 * sendWA('6283199354543', 'Tes pesan WA dari PHP');
 */
?>







