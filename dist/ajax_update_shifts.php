<?php
include 'security.php';

header('Content-Type: application/json');

if (!isset($_POST['shifts']) || !is_array($_POST['shifts'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$success = true;
foreach ($_POST['shifts'] as $s) {
    if (!isset($s['id'])) continue;
    
    $id = (int)$s['id'];
    $st = mysqli_real_escape_string($conn, $s['start'] . ':00');
    $en = mysqli_real_escape_string($conn, $s['end'] . ':00');
    $cl = mysqli_real_escape_string($conn, $s['color']);
    
    $query = "UPDATE jam_kerja SET jam_mulai='$st', jam_selesai='$en', warna='$cl' WHERE id=$id";
    if (!mysqli_query($conn, $query)) {
        $success = false;
    }
}

echo json_encode(['success' => $success]);
