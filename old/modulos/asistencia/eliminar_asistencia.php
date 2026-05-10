<?php
include("../conexion_modulos.php");

if($_POST){

    $deportista_id = $_POST['deportista_id'] ?? "";
    $fecha = $_POST['fecha'] ?? "";

    try {

        $stm = $conexion->prepare("
            DELETE FROM asistencia 
            WHERE deportista_id = :deportista_id 
            AND fecha = :fecha
        ");

        $stm->bindParam(":deportista_id", $deportista_id);
        $stm->bindParam(":fecha", $fecha);
        $stm->execute();

        echo json_encode([
            "status" => "ok",
            "mensaje" => "Registro eliminado"
        ]);

    } catch (Exception $e) {

        echo json_encode([
            "status" => "error",
            "mensaje" => $e->getMessage()
        ]);

    }
}
