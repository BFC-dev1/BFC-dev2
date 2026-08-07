<?php

if(session_status() === PHP_SESSION_NONE){

    session_start();

}

include("../conexion_modulos.php");

require_once(__DIR__ . "/../auditoria/funciones/registrar_auditoria.php");


if(isset($_GET['id'])){

    $id = $_GET['id'];

    $stm = $conexion->prepare("
        SELECT estado
        FROM usuario
        WHERE id = :id
    ");

    $stm->execute([
        ":id" => $id
    ]);

    $usuario = $stm->fetch(PDO::FETCH_ASSOC);

    if($usuario){

        $nuevo_estado =
            ($usuario['estado'] == 'activo')
            ? 'inactivo'
            : 'activo';

            /*
=============================================
PREPARAR CAMBIOS PARA AUDITORÍA

Se almacenan el valor anterior y el nuevo
valor del estado del usuario.

=============================================
*/

$cambios = [

    "estado"=>[

        "antes"=>$usuario["estado"],

        "despues"=>$nuevo_estado

    ]

];

        $update = $conexion->prepare("
            UPDATE usuario
            SET estado = :estado
            WHERE id = :id
        ");

        $update->execute([
            ":estado" => $nuevo_estado,
            ":id" => $id
        ]);

        /*
=============================================
REGISTRAR AUDITORÍA

Se registra el cambio de estado realizado
sobre el usuario.

=============================================
*/

registrarAuditoria(

    $conexion,

    "usuario",

    $id,

    "EDITAR",

    $cambios,

    "Cambio de estado del usuario"

);

    
    }

}