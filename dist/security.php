<?php
// security.php bridging to BexMedia security
require_once __DIR__ . "/../conf/config.php";

// Map BexMedia session to FixPoint expected variables if needed
if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    // Attempt to get user_id from database if missing in session
    $u = $_SESSION['username'];
    $res = mysqli_query($conn, "SELECT id FROM users WHERE username = AES_ENCRYPT('$u', 'bex')");
    if ($row = mysqli_fetch_assoc($res)) {
        $_SESSION['user_id'] = $row['id'];
    }
}

// Call BexMedia's checkLogin
checkLogin();

// Update activity tracking
$_SESSION['LAST_ACTIVITY'] = time();
?>







