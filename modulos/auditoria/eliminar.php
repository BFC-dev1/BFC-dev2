<?php

if(session_status() === PHP_SESSION_NONE){

    session_start();

}

include("../../modulos/conexion_modulos.php");

if(!isset($_GET["operacion"])){

    header("Location:index.php");
    exit;

}

$operacion = $_GET["operacion"];

$stmt = $conexion->prepare("

DELETE FROM auditoria

WHERE operacion_id = :operacion

");

$stmt->execute([

    ":operacion" => $operacion

]);

header("Location:index.php");
exit;