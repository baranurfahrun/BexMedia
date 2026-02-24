<?php
require_once __DIR__ . "/../conf/config.php";
if (!$conn) die("DB Error");

// 1. Dapatkan ID LIBUR
$resL = mysqli_query($conn, "SELECT id FROM jam_kerja WHERE kode='LIBUR' LIMIT 1");
$libur_id = ($rL = mysqli_fetch_assoc($resL)) ? $rL['id'] : 0;

if (!$libur_id) die("Critical Error: Shift LIBUR not found.");

// 2. Dapatkan ID-ID bermasalah (LEPAS, MID_P, MID_S)
$resBad = mysqli_query($conn, "SELECT id FROM jam_kerja WHERE kode IN ('LEPAS', 'MID_P', 'MID_S') OR nama_jam LIKE 'Lepas%'");
$bad_ids = [];
while($rb = mysqli_fetch_assoc($resBad)) $bad_ids[] = $rb['id'];

if (!empty($bad_ids)) {
    $ids_str = implode(',', $bad_ids);
    
    // 3. Update jadwal_dinas yang pakai ID bermasalah ke LIBUR
    mysqli_query($conn, "UPDATE jadwal_dinas SET jam_kerja_id = $libur_id WHERE jam_kerja_id IN ($ids_str)");
    echo "Updated " . mysqli_affected_rows($conn) . " schedule entries to LIBUR.\n";
    
    // 4. Hapus shift bermasalah
    mysqli_query($conn, "DELETE FROM jam_kerja WHERE id IN ($ids_str)");
    echo "Deleted " . mysqli_affected_rows($conn) . " problematic shifts from database.\n";
} else {
    echo "No problematic shifts found in database.\n";
}
?>
