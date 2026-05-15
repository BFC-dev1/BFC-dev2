<?php
session_start();

// Conexión PDO
include("../includes/conexion.php");

$error = "";

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Validar campos
    if (empty($usuario) || empty($password)) {

        $error = "Completa todos los campos.";

    } else {

        // Buscar usuario
        $sql = "SELECT * FROM usuario WHERE usuario = :usuario";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":usuario", $usuario);

        $stmt->execute();

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar usuario
        if ($admin) {

            // Verificar contraseña
            if ($password == $admin['password']) {

                session_regenerate_id(true);

                $_SESSION['usuario'] = $admin['usuario'];
                $_SESSION['id_usuario'] = $admin['id'];

                // Redireccionar
                header("Location: /BFC-dev2/modulos/Dashboard/index.php");
                exit;

            } else {

                $error = "Contraseña incorrecta.";

            }

        } else {

            $error = "Usuario no encontrado.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bellavista FC</title>

    <link rel="stylesheet" href="/BFC-dev2/assets/estilo.css">

    <style>

        .login-form{
            width:300px;
            margin:100px auto;
            padding:25px;
            border:1px solid #ccc;
            border-radius:8px;
            background:#f9f9f9;
        }

        .login-form input{
            width:100%;
            margin-bottom:10px;
            padding:8px;
        }

        .login-form button{
            width:100%;
            padding:8px;
            background:#0A4FA3;
            color:white;
            border:none;
            cursor:pointer;
        }

        .login-form button:hover{
            background:#083a7c;
        }

    </style>
</head>

<body>

<?php include("../includes/header.php"); ?>

<div class="login-form">

    <h2>Iniciar sesión</h2>

    <?php if(!empty($error)): ?>

        <p style="color:red;">
            <?php echo $error; ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>Usuario</label>

        <input
            type="text"
            name="usuario"
            required
        >

        <label>Contraseña</label>

        <input
            type="password"
            name="password"
            required
        >

        <button type="submit">
            Entrar
        </button>

        <p style="text-align:center; margin-top:10px;">
            <a href="../auth/registro.php">
                Crear cuenta
            </a>
        </p>

    </form>

</div>

</body>
</html>