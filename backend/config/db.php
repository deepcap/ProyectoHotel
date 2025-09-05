<?php
// backend/config/db.php

$host = "127.0.0.1";
$user = "root"; 
$pass = "";
$db   = "HotelDB";
$port = 3306;          // puerto MySQL (default 3306)

$mysqli = new mysqli($host, $user, $pass, $db, $port);

if ($mysqli->connect_errno) {
    http_response_code(500);
    exit("Error de conexión a MySQL: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
