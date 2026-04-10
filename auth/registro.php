<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../includes/conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (empty($_POST['usuario']) || empty($_POST['password'])) {
        $error = "Completa todos los campos.";
    } else {

        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM administradores WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
    $error = "El usuario ya existe.";
    $stmt->close();
    } else {

         $stmt->close();

            // Encriptar contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO administradores (usuario, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $usuario, $passwordHash);

            if ($stmt->execute()) {
                $success = "Usuario registrado correctamente.";
            } else {
                $error = "Error al registrar.";
            }
            $stmt->close(); 
        }

    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
</head>
<body>

<h2>Registro de Usuario</h2>

<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

<form method="POST">
    <input type="text" name="usuario" placeholder="Usuario" required><br><br>
    <input type="password" name="password" placeholder="Contraseña" required><br><br>
    <button type="submit">Registrarse</button>
</form>

<a href="../auth/login.php">Ir al login</a>

</body>
</html>