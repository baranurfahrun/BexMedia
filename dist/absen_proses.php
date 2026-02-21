<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// Get action from URL
$action = $_GET['action'] ?? '';

// Master Coordinate (Office Location)
// Note: Coordinate below is from FixPoint default. 
// Can be customized via settings later.
$office_lat = -5.148152;   // Example BexMedia Location
$office_lng = 119.431206;  // Example BexMedia Location
$radius     = 200;         // allowed radius in meters

// Haversine function to calculate distance in meters
function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 999999;
    $earth_radius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earth_radius * $c);
}

if ($action == 'check_geofence') {
    $data = json_decode(file_get_contents("php://input"), true);
    $lat = floatval($data['latitude'] ?? 0);
    $lng = floatval($data['longitude'] ?? 0);
    
    $distance = getDistance($lat, $lng, $office_lat, $office_lng);
    $success = ($distance <= $radius);
    
    echo json_encode([
        'success' => $success,
        'distance' => $distance,
        'radius' => $radius
    ]);
    exit;
}

if ($action == 'submit') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = intval($_SESSION['user_id'] ?? 0);
    $type = trim($data['type'] ?? '');
    $image = $data['image'] ?? '';
    $lat = floatval($data['latitude'] ?? 0);
    $lng = floatval($data['longitude'] ?? 0);

    if (!$user_id || !$type || !$image) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    // Geofence check on server
    $distance = getDistance($lat, $lng, $office_lat, $office_lng);
    if ($distance > $radius) {
        echo json_encode(['success' => false, 'message' => "Anda berada di luar radius ($distance m)."]);
        exit;
    }

    // Save Image
    $upload_dir = "absen_foto/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_name = $user_id . '_' . $type . '_' . date('Ymd_His') . '.jpg';
    $file_path = $upload_dir . $file_name;
    
    $image_parts = explode(";base64,", $image);
    $image_base64 = base64_decode($image_parts[1]);
    
    if (!file_put_contents($file_path, $image_base64)) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan foto.']);
        exit;
    }

    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');

    // Mapping field for jam
    $jam_field = 'jam_masuk';
    if ($type == 'keluar') $jam_field = 'jam_keluar';
    if ($type == 'istirahat_masuk') $jam_field = 'istirahat_masuk';
    if ($type == 'istirahat_keluar') $jam_field = 'istirahat_keluar';

    // Insert into DB
    // Assuming table structure: id, user_id, tanggal, jam_masuk, jam_keluar, istirahat_masuk, istirahat_keluar, status, foto, latitude, longitude
    $sql = "INSERT INTO absensi (user_id, tanggal, $jam_field, status, foto, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssdd", $user_id, $tanggal, $jam, $type, $file_name, $lat, $lng);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

if ($action == 'history') {
    $user_id = intval($_SESSION['user_id'] ?? 0);
    $tanggal = date('Y-m-d');
    
    $sql = "SELECT COALESCE(jam_masuk, jam_keluar, istirahat_masuk, istirahat_keluar) as jam, status, foto, latitude, longitude 
            FROM absensi 
            WHERE user_id = ? AND tanggal = ? 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $tanggal);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $history = [];
    while ($row = $res->fetch_assoc()) {
        $history[] = $row;
    }
    echo json_encode($history);
    exit;
}
?>
