<?php
require_once "conf/config.php";
echo "Syncing menus...\n";

global $conn;
$dist_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "dist";
echo "Dist Path: $dist_path\n";
if (!is_dir($dist_path)) {
    echo "Dist directory not found!\n";
    exit;
}

$files = scandir($dist_path);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'logout.php') {
        echo "Found file: $file\n";
        $full_path = $dist_path . DIRECTORY_SEPARATOR . $file;
        $content = file_get_contents($full_path);
        
        $display_name = $file;
        if (preg_match('/\/\/ MenuName:\s*(.*)/', $content, $matches)) {
            $display_name = trim($matches[1]);
        }
        echo "Display Name: $display_name\n";
        
        $res = safe_query("INSERT INTO web_menus (file_name, display_name) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE display_name = ?", [$file, $display_name, $display_name]);
        if ($res) {
            echo "Successfully synced to DB.\n";
        } else {
            echo "Failed to sync to DB: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nFinal DB Content:\n";
$res = mysqli_query($conn, "SELECT * FROM web_menus");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
