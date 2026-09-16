<?php
require_once(__DIR__ . "/../../includes/config.php");
include("../conexion_modulos.php");

header('Content-Type: application/json; charset=utf-8');

$id           = $_POST['id'] ?? null;
$hora_entrada = !empty($_POST['hora_entrada']) ? date("H:i:s", strtotime($_POST['hora_entrada'])) : null;
$hora_salida  = !empty($_POST['hora_salida']) ? date("H:i:s", strtotime($_POST['hora_salida'])) : null;
$observaciones = !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null;

if (empty($id)) {
    echo json_encode(["status" => "error", "mensaje" => "ID de registro no proporcionado."]);
    exit;
}

try {
    $estado = 'abierta';
    if ($hora_entrada && $hora_salida) {
        if ($hora_salida <= $hora_entrada) {
            echo json_encode(["status" => "error", "mensaje" => "La hora de salida debe ser posterior a la de entrada."]);
            exit;
        }
        $estado = 'cerrada';
    } elseif (!$hora_entrada && !$hora_salida) {
        $estado = 'ausente';
    }

    $stmt = $conexion->prepare("
        UPDATE asistencia_entrenador 
        SET hora_entrada = ?, hora_salida = ?, observaciones = ?, estado = ? 
        WHERE id = ?
    ");
    $stmt->execute([$hora_entrada, $hora_salida, $observaciones, $estado, $id]);

    echo json_encode(["status" => "ok", "mensaje" => "Registro actualizado."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error BD: " . $e->getMessage()]);
}