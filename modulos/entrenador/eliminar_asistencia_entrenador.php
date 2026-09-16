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

$id = $_POST['id'] ?? null;

if (empty($id)) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "ID de registro no proporcionado."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conexion->prepare("
        DELETE FROM asistencia_entrenador 
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    echo json_encode(["status" => "ok"], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error BD: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}