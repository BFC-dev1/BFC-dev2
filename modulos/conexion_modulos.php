<?php 

$servidor = "localhost";
$db = "bellavistafc";
$username = "root";
$password = "";

try {

    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$db;charset=utf8",
        $username,
        $password
    );

    // Mostrar errores de PDO
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {

    die($e->getMessage());

}
?>