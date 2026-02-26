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
$excl   = $_POST['exclude_ids'] ?? $_POST['exclude_ids[]'] ?? []; 
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
$incl_ids = !empty($excl) ? implode(',', array_map('intval', $excl)) : "0";
$incl_str = "AND id IN ($incl_ids)";

$qEmp = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE unit_kerja = '".mysqli_real_escape_string($conn, $unit)."' $incl_str");
$employees = [];
while($row = mysqli_fetch_assoc($qEmp)) $employees[] = $row;

if (count($employees) < 1) {
    echo json_encode(['success' => true, 'message' => 'Tidak ada pegawai terpilih untuk pekan ini.', 'total_assigned' => 0]);
    exit;
}

// 4. PROSES PENYUSUNAN JADWAL (Chain-Locked Quota-Aware)
$result_schedule = [];

// Identifikasi ID Shift Utama
$shifts_by_kode = [];
$qJ = mysqli_query($conn, "SELECT id, kode FROM jam_kerja");
while($rj = mysqli_fetch_assoc($qJ)) $shifts_by_kode[$rj['kode']] = (int)$rj['id'];

$p_id = $shifts_by_kode['PAGI'] ?? 0;
$s_id = $shifts_by_kode['SIANG'] ?? 0;
$m_id = $shifts_by_kode['MALAM'] ?? 0;
$lep_id = $shifts_by_kode['LEPAS'] ?? 0;
$l_id = $shifts_by_kode['LIBUR'] ?? 0;

// Ambil histori shift (t-1 dan t-2)
$yesterday_date = date('Y-m-d', strtotime("$tahun-$bulan-$start_day -1 day"));
$day_before_date = date('Y-m-d', strtotime("$tahun-$bulan-$start_day -2 day"));

$user_history_1 = []; // t-1
$user_history_2 = []; // t-2

$qH1 = mysqli_query($conn, "SELECT user_id, jam_kerja_id FROM jadwal_dinas WHERE tanggal='$yesterday_date'");
while($rh = mysqli_fetch_assoc($qH1)) $user_history_1[$rh['user_id']] = (int)$rh['jam_kerja_id'];

$qH2 = mysqli_query($conn, "SELECT user_id, jam_kerja_id FROM jadwal_dinas WHERE tanggal='$day_before_date'");
while($rh = mysqli_fetch_assoc($qH2)) $user_history_2[$rh['user_id']] = (int)$rh['jam_kerja_id'];

// State tracking
$user_last = $user_history_1;
$user_prev = $user_history_2;

// Urutkan pegawai secara konsisten di awal saja sebagai starting point
usort($employees, function($a, $b) { return (int)$a['id'] - (int)$b['id']; });

for ($day = $start_day; $day <= $end_day; $day++) {
    $current_date = sprintf("%04d-%02d-%02d", $tahun, $bulan, $day);
    
    // 1. Klasifikasikan pegawai berdasarkan HAK MEREKA hari ini
    $candidates_pagi  = []; 
    $candidates_siang = []; 
    $candidates_malam = []; 
    $must_lepas = [];       
    
    foreach ($employees as $emp) {
        $uid = $emp['id'];
        $y   = $user_last[$uid] ?? 0;
        
        if ($y == $m_id) {
            $must_lepas[] = $uid;
        } 
        elseif ($y == $p_id) {
            $candidates_siang[] = $uid;
        }
        elseif ($y == $s_id) {
            $candidates_malam[] = $uid;
        }
        else {
            // Termasuk yang kemarin Lepas Malam atau Libur
            $candidates_pagi[] = $uid;
        }
    }
    
    // --- FITUR FAIRNESS: Urutkan candidates_pagi berdasarkan lama menunggu ---
    usort($candidates_pagi, function($a, $b) use ($wait_counts) {
        return $wait_counts[$b] - $wait_counts[$a]; // Yang nunggu lama di depan
    });
    
    $assigned_today = [];
    
    // 2. Assign yang OTOMATIS (Lepas)
    foreach ($must_lepas as $uid) {
        $result_schedule[] = ['user_id' => $uid, 'tanggal' => $current_date, 'jam_kerja_id' => $lep_id];
        $assigned_today[$uid] = $lep_id;
        $wait_counts[$uid] = 0;
    }
    
    // 3. Isi Quota P, S, M (Chain-Locked)
    $workflow = [
        ['id' => $p_id, 'pool' => &$candidates_pagi],
        ['id' => $s_id, 'pool' => &$candidates_siang],
        ['id' => $m_id, 'pool' => &$candidates_malam]
    ];
    
    foreach ($workflow as $index => &$wf) {
        $sid = $wf['id'];
        $q_needed = (int)($quota[$sid] ?? 0);
        
        // --- PRIORITAS: Selalu urutkan pool Pagi berdasarkan yang paling lama menunggu ---
        if ($sid == $p_id) {
            usort($wf['pool'], function($a, $b) use ($wait_counts) {
                return $wait_counts[$b] - $wait_counts[$a];
            });
        }
        
        // JUMPSTART: Di hari pertama, jika pool kosong, tarik dari pool Pagi
        if ($day === $start_day && empty($wf['pool']) && !empty($candidates_pagi)) {
            $wf['pool'] = &$candidates_pagi;
        }
        
        for ($i = 0; $i < $q_needed; $i++) {
            $pick = array_shift($wf['pool']);
            if ($pick) {
                $result_schedule[] = ['user_id' => $pick, 'tanggal' => $current_date, 'jam_kerja_id' => $sid];
                $assigned_today[$pick] = $sid;
                $wait_counts[$pick] = 0;
                
                // Jika tadi narik dari candidates_pagi, hapus dari sana juga
                if (($key = array_search($pick, $candidates_pagi)) !== false) unset($candidates_pagi[$key]);
            }
        }
    }
    
    // 4. Sisanya (yang benar-benar tidak bertugas) WAJIB LIBUR
    foreach ($employees as $emp) {
        $uid = $emp['id'];
        if (!isset($assigned_today[$uid])) {
            $result_schedule[] = ['user_id' => $uid, 'tanggal' => $current_date, 'jam_kerja_id' => $l_id];
            $assigned_today[$uid] = $l_id;
            $wait_counts[$uid]++; // Tambah poin menunggu agar besok diprioritaskan
        }
    }
    
    // Update history pergerakan buat loop besok
    foreach ($employees as $emp) {
        $uid = $emp['id'];
        $user_prev[$uid] = $user_last[$uid] ?? 0;
        $user_last[$uid] = $assigned_today[$uid];
    }
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
