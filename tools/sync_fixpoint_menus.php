<?php
// tools/sync_fixpoint_menus.php
// Script to scan dist/ and populate the 'menu' table for FixPoint permission system.

require_once "../conf/config.php";

$dist_path = __DIR__ . "/../dist";
$files = scandir($dist_path);

echo "Starting Menu Sync...\n";

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($dist_path . "/" . $file);
        
        $menu_name = "";
        // Extract // MenuName: tag
        if (preg_match('/\/\/ MenuName:\s*(.*)/', $content, $matches)) {
            $menu_name = trim($matches[1]);
        } else {
            // Fallback: use filename and beautify it
            $menu_name = ucwords(str_replace("_", " ", pathinfo($file, PATHINFO_FILENAME)));
        }
        
        $file_name = $file;
        
        // Check if menu exists
        $check = mysqli_query($conn, "SELECT id FROM menu WHERE file_menu = '$file_name'");
        if (mysqli_num_rows($check) == 0) {
            echo "Adding: $menu_name ($file_name)\n";
            mysqli_query($conn, "INSERT INTO menu (nama_menu, file_menu) VALUES ('$menu_name', '$file_name')");
        } else {
            // Update name in case it changed
            mysqli_query($conn, "UPDATE menu SET nama_menu = '$menu_name' WHERE file_menu = '$file_name'");
        }
    }
}

// Special case: Give admin access to ALL menus by default
$admin_res = mysqli_query($conn, "SELECT id FROM users WHERE username = AES_ENCRYPT('admin', 'bex') OR username = 'admin' LIMIT 1");
if ($admin_row = mysqli_fetch_assoc($admin_res)) {
    $admin_id = $admin_row['id'];
    echo "Updating Admin permissions (ID: $admin_id)...\n";
    
    $all_menus = mysqli_query($conn, "SELECT id FROM menu");
    while ($m = mysqli_fetch_assoc($all_menus)) {
        $menu_id = $m['id'];
        mysqli_query($conn, "INSERT IGNORE INTO akses_menu (user_id, menu_id) VALUES ('$admin_id', '$menu_id')");
    }
}

echo "Sync Complete!\n";
