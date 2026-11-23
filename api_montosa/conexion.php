<?php
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$port = 4000;
$user = "3HbgrKLmYo5Cgas.root";
$pass = "eAK7F0EQ8HYF1xcV";
$db   = "montosa";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, "/etc/ssl/certs/ca-certificates.crt", NULL, NULL);

if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die(json_encode(["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()]));
}
$conn->set_charset("utf8mb4");
?>