<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">

<title>Recuperar contraseña</title>

<link rel="stylesheet" href="/BFC-dev2/assets/estilo.css">

<style>

.login-form{
    width:350px;
    margin:80px auto;
    padding:25px;
    border:1px solid #ccc;
    border-radius:8px;
    background:#f9f9f9;
}

.login-form input{
    width:100%;
    padding:8px;
    margin-bottom:10px;
}

.login-form button{
    width:100%;
    padding:10px;
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

<h2>Recuperar contraseña</h2>

<form action="guardar_password.php" method="POST">

<label>Número de documento</label>

<input
type="text"
name="documento"
required
>

<label>Correo electrónico</label>

<input
type="email"
name="correo"
required
>

<label>Nueva contraseña</label>

<input
type="password"
name="password"
required
>

<label>Confirmar contraseña</label>

<input
type="password"
name="confirmar"
required
>

<button type="submit">

Restablecer contraseña

</button>

</form>

<p style="text-align:center;margin-top:15px;">

<a href="login.php">

Volver al inicio de sesión

</a>

</p>

</div>

</body>

</html>