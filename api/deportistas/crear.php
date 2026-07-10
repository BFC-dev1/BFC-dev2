<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include("../../modulos/conexion_modulos.php");

$datos = json_decode(file_get_contents("php://input"), true);

if(!$datos){
    echo json_encode([
        "success" => false,
        "mensaje" => "No se recibieron datos"
    ]);
    exit;
}

$stmt = $conexion->prepare("
SELECT id
FROM deportista
WHERE documento = :documento
");

$stmt->execute([
    ":documento" => $datos['documento']
]);

if($stmt->fetch()){
    echo json_encode([
        "success" => false,
        "mensaje" => "El documento ya está registrado"
    ]);
    exit;
}

$insert = $conexion->prepare("
INSERT INTO deportista(
    tipo_documento,
    documento,
    telefono,
    nombre,
    fecha_nacimiento,
    categoria_id,
    estado
)
VALUES(
    :tipo_documento,
    :documento,
    :telefono,
    :nombre,
    :fecha_nacimiento,
    :categoria_id,
    'activo'
)
");

$insert->execute([
    ":tipo_documento" => $datos['tipo_documento'],
    ":documento" => $datos['documento'],
    ":telefono" => $datos['telefono'],
    ":nombre" => $datos['nombre'],
    ":fecha_nacimiento" => $datos['fecha_nacimiento'],
    ":categoria_id" => $datos['categoria_id']
]);

echo json_encode([
    "success" => true,
    "mensaje" => "Deportista creado correctamente"
]);