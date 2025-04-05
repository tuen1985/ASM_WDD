<?php
$connect = mysqli_connect('localhost', 'root', '', 'sdlcsql');

if (!$connect) {
    echo "Kết nối thất bại";
} else {
    echo "";
}
?>