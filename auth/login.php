<?php
session_start();

// Conexión a la base de datos
include("../includes/conexion.php");

// Verifica la conexión
if ($conn->connect_error) {
    die("Error de conexión.");
}

// Procesa el login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (empty($_POST['usuario']) || empty($_POST['password'])) {
        $error = "Completa todos los campos.";
    } else {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Preparar la consulta para evitar SQL injection
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();





    // Verifica la contraseña usando password_verify
    if ($admin) {
    if (password_verify($password, $admin['password'])) {
    session_regenerate_id(true);
        $_SESSION['admin'] = $admin['usuario'];
        header("Location: /BFC-dev1.github.io/pages/index.php");
        exit;
    } else {
        $error = "Contraseña incorrecta.";
    }

    } else {
        $error = "Usuario no existe.";
    }
if (isset($stmt)) {
            $stmt->close();
        }
    } // ← cierra el else (campos llenos)
} // ← cierra el if POST

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bellavista FC</title>
    <link rel="stylesheet" href="../assets/estilo.css">
    <style>
        /* Solo estilo básico para el login */
        .login-form {
            width: 300px;
            margin: 100px auto;
            padding: 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .login-form input {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
        }
        .login-form button {
            width: 100%;
            padding: 8px;
            background: #0A4FA3;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .login-form button:hover {
            background: #083a7c;
        }
    </style>
</head>
<body>

<?php include("../includes/header.php"); ?>

<!-- ===== LOGIN FORM ===== -->
<div class="login-form">
    <h2>Iniciar sesión</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="login.php">
        <label for="usuario">Usuario:</label>
        <input type="text" name="usuario" id="usuario" 
        value="<?php echo isset($usuario) ? $usuario : ''; ?>" required>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Entrar</button>

<p style="text-align:center; margin-top:10px;">
    <a href="../auth/registro.php">Crear cuenta</a>
</p>
    </form>
</div>

<!-- ===== SCRIPT DEL MENU DESPLEGABLE ===== -->
<script>
function toggleMenu() {
    const menu = document.getElementById("menu");
    menu.classList.toggle("activo");
}

document.addEventListener("click", function(e) {
    const menu = document.getElementById("menu");
    const icon = document.querySelector(".menu-icon");

    if (!menu.contains(e.target) && !icon.contains(e.target)) {
        menu.classList.remove("activo");
    }
});
</script>

</body>
</html>