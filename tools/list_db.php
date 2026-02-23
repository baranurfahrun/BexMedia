<?php
$c = mysqli_connect('localhost', 'root', '');
$r = mysqli_query($c, 'SHOW DATABASES');
while($row = mysqli_fetch_row($r)) {
    echo $row[0] . "\n";
}
