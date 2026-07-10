<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include("../../modulos/conexion_modulos.php");

// Leer JSON enviado desde Postman
$datos = json_decode(file_get_contents("php://input"), true);

// Verificar que se recibieron datos
if (!$datos) {

    echo json_encode([
        "success" => false,
        "mensaje" => "No se recibieron datos"
    ]);

    exit;
}

// Validar campos requeridos
if (
    !isset($datos['id']) ||
    !isset($datos['telefono']) ||
    !isset($datos['nombre'])
) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Datos incompletos"
    ]);

    exit;
}

// Verificar que exista el deportista
$stmt = $conexion->prepare("
    SELECT id
    FROM deportista
    WHERE id = :id
");

$stmt->execute([
    ":id" => $datos['id']
]);

$deportista = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deportista) {

    echo json_encode([
        "success" => false,
        "mensaje" => "El deportista no existe"
    ]);

    exit;
}

// Actualizar información
$update = $conexion->prepare("
    UPDATE deportista
    SET
        telefono = :telefono,
        nombre = :nombre
    WHERE id = :id
");

$update->execute([
    ":telefono" => trim($datos['telefono']),
    ":nombre" => trim($datos['nombre']),
    ":id" => $datos['id']
]);

echo json_encode([
    "success" => true,
    "mensaje" => "Deportista actualizado correctamente",
    "id" => $datos['id']
]);