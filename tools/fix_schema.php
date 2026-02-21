<?php
require_once "conf/config.php";

echo "DB Name: " . get_setting('name_bex', 'bexmedia') . "\n";

$queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS nik VARCHAR(30) UNIQUE",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS jabatan VARCHAR(100)",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS unit_kerja VARCHAR(100)",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS atasan_id INT",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'blocked') DEFAULT 'active'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP NULL DEFAULT NULL"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: $q -> " . mysqli_error($conn) . "\n";
    }
}

$res = mysqli_query($conn, "DESCRIBE users");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
