<?php
/**
 * ajax_generate_jadwal.php - Logika Magic Schedule Generator
 */
include 'security.php';

// Proteksi akses (Hanya Admin/Bara)
if (!isset($_SESSION['username']) || !in_array($_SESSION['username'], ['admin', 'bara'])) {
    die(json_encode(['success' => false, 'message' => 'Restricted access']));
}

header('Content-Type: application/json');

// 1. Ambil Parameter
$unit   = $_POST['unit'] ?? '';
$bulan  = (int)($_POST['bulan'] ?? date('m'));
$tahun  = (int)($_POST['tahun'] ?? date('Y'));
$minggu = (int)($_POST['minggu'] ?? 1);
$excl   = $_POST['exclude_ids'] ?? []; // ID pegawai yang libur seminggu
$quota  = $_POST['quota'] ?? []; // Array jam_kerja_id => jumlah (Contoh: [25 => 2])

if (empty($unit) || empty($quota)) {
    die(json_encode(['success' => false, 'message' => 'Unit & Quota wajib diisi!']));
}

// 2. Hitung Rentang Tanggal berdasarkan Minggu
$start_day = (($minggu - 1) * 7) + 1;
$end_day   = $start_day + 6;
$last_day_of_month = (int)date('t', strtotime("$tahun-$bulan-01"));
if ($end_day > $last_day_of_month) $end_day = $last_day_of_month;

// 3. Ambil Daftar Pegawai di Unit tsb
$excl_str = !empty($excl) ? "AND id NOT IN (" . implode(',', array_map('intval', $excl)) . ")" : "";
$qEmp = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE unit_kerja = '".mysqli_real_escape_string($conn, $unit)."' $excl_str");
$employees = [];
while($row = mysqli_fetch_assoc($qEmp)) $employees[] = $row;

if (count($employees) < 1) {
    die(json_encode(['success' => false, 'message' => 'Tidak ada pegawai aktif untuk diacak!']));
}

// 4. PROSES PENGACAKAN (The Algorithm)
$result_schedule = [];
$libur_id = 30; // ID default untuk LIBUR (sesuaikan dengan tabel jam_kerja)

for ($day = $start_day; $day <= $end_day; $day++) {
    $current_date = sprintf("%04d-%02d-%02d", $tahun, $bulan, $day);
    
    // Kocok daftar pegawai setiap hari agar adil
    shuffle($employees);
    
    $assigned_count = 0;
    foreach ($quota as $jam_id => $q_count) {
        for ($i = 0; $i < $q_count; $i++) {
            if (isset($employees[$assigned_count])) {
                $emp_id = $employees[$assigned_count]['id'];
                $result_schedule[] = [
                    'user_id' => $emp_id,
                    'tanggal' => $current_date,
                    'jam_kerja_id' => (int)$jam_id
                ];
                $assigned_count++;
            }
        }
    }
    
    // Pegawai sisanya otomatis Libur (atau jam_id yang tidak terisi)
    // Logika ini bisa dikembangkan jika Anda ingin sisa pegawai tetap tercatat sebagai LIBUR di DB
}

// 5. SIMPAN KE DATABASE (Opsional: Simpan atau Kirim sebagai Preview)
// Di sini saya buatkan untuk langsung simpan agar bisa langsung dilihat hasilnya.
$success_count = 0;
foreach ($result_schedule as $sch) {
    $stmt = $conn->prepare("INSERT INTO jadwal_dinas (user_id, tanggal, bulan, tahun, jam_kerja_id, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE jam_kerja_id=VALUES(jam_kerja_id), created_at=NOW()");
    $stmt->bind_param("isiiis", $sch['user_id'], $sch['tanggal'], $bulan, $tahun, $sch['jam_kerja_id'], $nama_user);
    if ($stmt->execute()) $success_count++;
    $stmt->close();
}

echo json_encode([
    'success' => true, 
    'message' => "Berhasil mengacak jadwal untuk $success_count entri pada Minggu ke-$minggu",
    'total_assigned' => $success_count
]);
