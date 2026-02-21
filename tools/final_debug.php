<?php
require_once "conf/config.php";

$sql = "SELECT id, username, password FROM users WHERE id = 2";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$username_bin = $row['username'];
$password_bin = $row['password'];

$stmt = mysqli_prepare($conn, "SELECT AES_DECRYPT(?, 'bex') as user, AES_DECRYPT(?, 'bara') as pass");
mysqli_stmt_bind_param($stmt, "ss", $username_bin, $password_bin);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$dec = mysqli_fetch_assoc($res);

echo "Decrypted 'admin' with 'bex': " . $dec['user'] . "\n";
echo "Decrypted Password with 'bara': " . $dec['pass'] . "\n";
?>
