<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // encriptar

    $sql = "INSERT INTO administradores (nombre, usuario, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $usuario, $password);

    if ($stmt->execute()) {
        echo "Usuario creado con éxito.";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<form method="post" action="">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="usuario" placeholder="Usuario" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Crear usuario</button>
</form>
