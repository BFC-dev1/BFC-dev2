<?php
// conexion.php
$host = "localhost";
$user = "root";
$password = ""; // sin contraseña
$dbname = "bellavistafc";

try {
    // Crear la conexión PDO
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);

    // Configurar PDO para que lance excepciones en caso de error
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si hay error, mostrar mensaje
    die("Error de conexión: " . $e->getMessage());
}
?>
