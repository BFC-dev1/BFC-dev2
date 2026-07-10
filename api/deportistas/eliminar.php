<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include("../../modulos/conexion_modulos.php");

$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || !isset($datos['id'])) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Debe enviar el ID del deportista"
    ]);

    exit;
}

$stmt = $conexion->prepare("
    SELECT id
    FROM deportista
    WHERE id = :id
");

$stmt->execute([
    ":id" => $datos['id']
]);

if (!$stmt->fetch()) {

    echo json_encode([
        "success" => false,
        "mensaje" => "El deportista no existe"
    ]);

    exit;
}

$delete = $conexion->prepare("
    DELETE FROM deportista
    WHERE id = :id
");

$delete->execute([
    ":id" => $datos['id']
]);

echo json_encode([
    "success" => true,
    "mensaje" => "Deportista eliminado correctamente"
]);