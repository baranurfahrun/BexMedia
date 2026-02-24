<?php
require_once "conf/config.php";
$res = mysqli_query($conn, "SELECT * FROM web_dokter_audit_log ORDER BY id DESC LIMIT 5");
while($r = mysqli_fetch_assoc($res)) {
    echo "ID: " . $r['id'] . " | Action: " . $r['action'] . " | Desc: " . $r['description'] . "\n";
}
?>
