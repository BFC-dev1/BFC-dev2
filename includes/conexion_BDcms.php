<?php

$host = "localhost";
$dbname = "bellavistafc_cms";
$user = "root";
$pass = "";

try {

    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error conexión: " . $e->getMessage());
}