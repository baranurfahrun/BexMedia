<?php
require_once "../conf/config.php";
write_log("LOGOUT", "User " . ($_SESSION['username'] ?? 'UNKNOWN') . " logout.");
session_unset();
session_destroy();
header("Location: ../login.php");
exit;
?>







