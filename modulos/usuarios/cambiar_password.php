<?php
session_start();

// Cargar la configuración general (define $url_base, $css_url, $img_url)
require_once(__DIR__ . "/../../includes/config.php");

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . $url_base . "/auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    
    <!-- CSS Dinámico (Local y Web) -->
    <link rel="stylesheet" href="<?= $css_url ?>/estilo.css">
    
    <style>
        .login-form {
            width: 380px;
            margin: 80px auto;
            padding: 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            background: #0A4FA3;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .login-form button:hover {
            background: #083a7c;
        }
    </style>
</head>
<body>

<?php include(__DIR__ . "/../../includes/header.php"); ?>

<div class="login-form">
    <h2>Cambiar contraseña</h2>

    <form action="actualizar_password.php" method="POST">
        <label for="actual">Contraseña actual</label>
        <input type="password" id="actual" name="actual" required>

        <label for="password">Nueva contraseña</label>
        <input type="password" id="password" name="password" required>

        <label for="confirmar">Confirmar nueva contraseña</label>
        <input type="password" id="confirmar" name="confirmar" required>

        <button type="submit">Actualizar contraseña</button>
    </form>

    <p style="text-align:center;margin-top:15px;">
        <a href="<?= $url_base ?>/modulos/dashboard/index.php">
            Volver al panel
        </a>
    </p>
</div>

</body>
</html>