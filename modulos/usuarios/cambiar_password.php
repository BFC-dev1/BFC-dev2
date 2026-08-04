
<?php

session_start();

if(!isset($_SESSION['id_usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Cambiar contraseña</title>

<link rel="stylesheet" href="/BFC-dev2/assets/estilo.css">

<style>

.login-form{

    width:380px;
    margin:80px auto;
    padding:25px;
    border:1px solid #ccc;
    border-radius:8px;
    background:#f9f9f9;

}

.login-form input{

    width:100%;
    padding:10px;
    margin-bottom:12px;

}

.login-form button{

    width:100%;
    padding:10px;
    background:#0A4FA3;
    color:#fff;
    border:none;
    cursor:pointer;

}

.login-form button:hover{

    background:#083a7c;

}

</style>

</head>

<body>

<?php include("../../includes/header.php"); ?>

<div class="login-form">

<h2>Cambiar contraseña</h2>

<form action="actualizar_password.php" method="POST">

<label>Contraseña actual</label>

<input
type="password"
name="actual"
required
>

<label>Nueva contraseña</label>

<input
type="password"
name="password"
required
>

<label>Confirmar nueva contraseña</label>

<input
type="password"
name="confirmar"
required
>

<button type="submit">

Actualizar contraseña

</button>

</form>

<p style="text-align:center;margin-top:15px;">

<a href="/BFC-dev2/modulos/dashboard/index.php">

Volver al panel

</a>

</p>

</div>

</body>

</html>