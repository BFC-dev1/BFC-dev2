<?php

include("../conexion_modulos.php");

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

        $update = $conexion->prepare("
            UPDATE usuario
            SET estado = :estado
            WHERE id = :id
        ");

        $update->execute([
            ":estado" => $nuevo_estado,
            ":id" => $id
        ]);

        echo "ok";
    }

}