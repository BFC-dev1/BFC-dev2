<?php

/*
=================================================
INICIAR SESIÓN
=================================================
*/
if(session_status() === PHP_SESSION_NONE){

    session_start();

}

/*
=================================================
CONEXIÓN
=================================================
*/
include("../../modulos/conexion_modulos.php");

/*
=================================================
VALIDAR FECHAS
=================================================
*/

if(
    !isset($_POST["fecha_desde"]) ||
    !isset($_POST["fecha_hasta"])
){

    header("Location:index.php");
    exit;

}

$fecha_desde = $_POST["fecha_desde"];
$fecha_hasta = $_POST["fecha_hasta"];

/*
=================================================
ELIMINAR TODOS LOS REGISTROS
DEL RANGO SELECCIONADO
=================================================
*/

$stmt = $conexion->prepare("

DELETE FROM auditoria

WHERE fecha >= :desde

AND fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)

");

$stmt->execute([

    ":desde" => $fecha_desde,
    ":hasta" => $fecha_hasta

]);

/*
=================================================
VOLVER AL LISTADO
=================================================
*/

header("Location:index.php?eliminado=1");

exit;

?>