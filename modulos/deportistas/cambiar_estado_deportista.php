<?php

include("../../modulos/conexion_modulos.php");

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $stm = $conexion->prepare("
        SELECT estado
        FROM deportista
        WHERE id = :id
    ");

    $stm->execute([
        ":id" => $id
    ]);

    $deportista = $stm->fetch(PDO::FETCH_ASSOC);

    if($deportista){

        $nuevo_estado =
            ($deportista['estado'] == 'activo')
            ? 'inactivo'
            : 'activo';

        $update = $conexion->prepare("
            UPDATE deportista
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