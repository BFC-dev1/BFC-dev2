<?php
session_start();
include 'conexion.php'; // nuestra conexión funcionando

$usuario = $_POST['usuario'];
$contraseña = $_POST['contraseña'];

// Buscar usuario en la BD
$sql = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($fila = $resultado->fetch_assoc()) {
    // Verificar contraseña
    if (password_verify($contraseña, $fila['contraseña'])) {
        $_SESSION['usuario'] = $fila['usuario'];
        header("Location: dashboard.php"); // redirige a página interna
        exit();
    } else {
        echo "Contraseña incorrecta.";
    }
} else {
    echo "Usuario no encontrado.";
}

$stmt->close();
$conn->close();
?>
