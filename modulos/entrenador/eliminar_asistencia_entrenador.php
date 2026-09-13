<?php
/*
=========================================================
MÓDULO: ASISTENCIA DE ENTRENADORES
SISTEMA: BELLAVISTA FC
ARCHIVO: eliminar_asistencia_entrenador.php
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");
include("../conexion_modulos.php");

header('Content-Type: application/json; charset=utf-8');

$usuario_id = $_POST['usuario_id'] ?? null;
$fecha      = $_POST['fecha'] ?? null;

if (empty($usuario_id) || empty($fecha)) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Datos requeridos incompletos."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conexion->prepare("
        DELETE FROM asistencia_entrenador 
        WHERE usuario_id = ? AND fecha = ?
    ");
    $stmt->execute([$usuario_id, $fecha]);

    echo json_encode(["status" => "ok"], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error BD: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}