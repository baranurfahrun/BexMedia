<?php
include 'security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $user_id = (int)$_POST['user_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $shift_id = (int)$_POST['shift_id']; // 0 if delete
    
    $tgl_parts = explode('-', $tanggal);
    if (count($tgl_parts) !== 3) {
        echo json_encode(['success' => false, 'message' => 'Format tanggal salah']);
        exit;
    }
    
    $tahun = (int)$tgl_parts[0];
    $bulan = (int)$tgl_parts[1];
    
    if ($shift_id === 0) {
        // Hapus
        $stmt = $conn->prepare("DELETE FROM jadwal_dinas WHERE user_id = ? AND tanggal = ?");
        $stmt->bind_param("is", $user_id, $tanggal);
        $res = $stmt->execute();
        $stmt->close();
        
        if ($res) {
            write_log("JADWAL_API_HAPUS", "Hapus jadwal user_id=$user_id tgl=$tanggal");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
    } else {
        // Simpan / Update
        $stmt = $conn->prepare("INSERT INTO jadwal_dinas (user_id, tanggal, bulan, tahun, jam_kerja_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE jam_kerja_id=VALUES(jam_kerja_id), created_by=VALUES(created_by), created_at=NOW()");
        
        $stmt->bind_param("isiiis", $user_id, $tanggal, $bulan, $tahun, $shift_id, $nama_user);
        $res = $stmt->execute();
        $stmt->close();
        
        if ($res) {
            // Get shift data for return
            $q = mysqli_query($conn, "SELECT nama_jam, warna FROM jam_kerja WHERE id=$shift_id");
            $shift = mysqli_fetch_assoc($q);
            
            write_log("JADWAL_API_SIMPAN", "Simpan jadwal user_id=$user_id tgl=$tanggal shift=$shift_id");
            echo json_encode([
                'success' => true,
                'nama' => $shift['nama_jam'],
                'warna' => $shift['warna']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
    }
    exit;
}
