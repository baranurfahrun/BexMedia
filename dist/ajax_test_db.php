<?php
/**
 * ajax_test_db.php - Testing database connection for Settings page
 */
require_once "../conf/config.php";
checkLogin();

header('Content-Type: application/json');

$host = $_POST['host'] ?? '';
$user = $_POST['user'] ?? '';
$pass = $_POST['pass'] ?? '';
$name = $_POST['name'] ?? '';

if (empty($host) || empty($user) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Host, Username, dan Database Name wajib diisi!']);
    exit;
}

// Bypass error reporting to catch connection error manually
mysqli_report(MYSQLI_REPORT_OFF);

$start_time = microtime(true);
$test_conn = mysqli_init();
mysqli_options($test_conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10); // Naikkan jadi 10 detik jaga-jaga network lambat

$connected = @mysqli_real_connect($test_conn, $host, $user, $pass, $name);

if (!$connected) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal terhubung (Timeout 5s): ' . mysqli_connect_error()
    ]);
} else {
    $end_time = microtime(true);
    $latency = round(($end_time - $start_time) * 1000, 2);
    
    // Check if we can query at least one table
    $table_check = mysqli_query($test_conn, "SHOW TABLES LIMIT 1");
    $tables = mysqli_num_rows($table_check);
    
    mysqli_close($test_conn);
    
    echo json_encode([
        'success' => true, 
        'message' => "Koneksi Berhasil! Latensi: {$latency}ms. Terdeteksi setidaknya satu tabel.",
        'latency' => $latency
    ]);
}
