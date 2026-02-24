<?php
require_once __DIR__ . "/../conf/config.php";
if (!$conn) die("DB Error");

// 1. Pastikan Shift LEPAS ada di database
$check = mysqli_query($conn, "SELECT id FROM jam_kerja WHERE kode='LEPAS'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO jam_kerja (kode, nama_jam, jam_mulai, jam_selesai, warna) VALUES ('LEPAS', 'Lepas Malam', '00:00:00', '00:00:00', '#64748B')");
    echo "Added LEPAS shift back to database.\n";
} else {
    echo "LEPAS shift already exists.\n";
}

// 2. Pastikan yang lain (P, S, M, L) juga ada
$defaults = [
    ['PAGI', 'Pagi', '09:00:00', '14:00:00', '#3B82F6'],
    ['SIANG', 'Siang', '14:00:00', '21:00:00', '#10B981'],
    ['MALAM', 'Malam', '21:00:00', '09:00:00', '#8B5CF6'],
    ['LIBUR', 'Libur', '00:00:00', '00:00:00', '#94A3B8']
];

foreach ($defaults as $d) {
    mysqli_query($conn, "INSERT IGNORE INTO jam_kerja (kode, nama_jam, jam_mulai, jam_selesai, warna) VALUES ('{$d[0]}', '{$d[1]}', '{$d[2]}', '{$d[3]}', '{$d[4]}')");
}

echo "Database preparation complete.";
?>
