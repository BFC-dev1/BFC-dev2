<?php
include("../conexion_modulos.php");

header("Content-Type: application/json");

// 🔥 VALIDAR QUE SEA POST
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode([
        "status" => "error",
        "mensaje" => "Método no permitido"
    ]);
    exit;
}

// 🔥 RECIBIR DATOS
$deportista_id = $_POST['deportista_id'] ?? "";
$estado = $_POST['estado'] ?? "";
$fecha = $_POST['fecha'] ?? "";

// 🔥 VALIDACIONES
if(empty($deportista_id) || empty($estado) || empty($fecha)){
    echo json_encode([
        "status" => "error",
        "mensaje" => "Datos incompletos"
    ]);
    exit;
}

// 🔥 VALIDAR ESTADO PERMITIDO
$estados_validos = ["presente", "ausente", "tarde"];
if(!in_array($estado, $estados_validos)){
    echo json_encode([
        "status" => "error",
        "mensaje" => "Estado inválido"
    ]);
    exit;
}

try {

    // 🔍 VERIFICAR SI YA EXISTE
    $stmt = $conexion->prepare("
        SELECT id 
        FROM asistencia 
        WHERE deportista_id = ? AND fecha = ?
    ");
    $stmt->execute([$deportista_id, $fecha]);

    if($stmt->fetch()){

        // 🔄 UPDATE
        $update = $conexion->prepare("
            UPDATE asistencia 
            SET estado = ?
            WHERE deportista_id = ? AND fecha = ?
        ");
        $update->execute([$estado, $deportista_id, $fecha]);

        echo json_encode([
            "status" => "ok",
            "accion" => "actualizado",
            "deportista_id" => $deportista_id,
            "estado" => $estado
        ]);

    } else {

        // ➕ INSERT
        $insert = $conexion->prepare("
            INSERT INTO asistencia (deportista_id, fecha, estado)
            VALUES (?, ?, ?)
        ");
        $insert->execute([$deportista_id, $fecha, $estado]);

        echo json_encode([
            "status" => "ok",
            "accion" => "guardado",
            "deportista_id" => $deportista_id,
            "estado" => $estado
        ]);
    }

} catch (Exception $e){

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ]);
}
