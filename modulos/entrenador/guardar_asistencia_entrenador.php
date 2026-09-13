<?php
/*
=========================================================
MÓDULO: ASISTENCIA DE ENTRENADORES
SISTEMA: BELLAVISTA FC
ARCHIVO: guardar_asistencia_entrenador.php
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");
include("../conexion_modulos.php");

header('Content-Type: application/json; charset=utf-8');

$usuario_id = $_POST['usuario_id'] ?? null;
$fecha      = $_POST['fecha'] ?? null;

$hora_entrada = !empty($_POST['hora_entrada'])
    ? date("H:i:s", strtotime($_POST['hora_entrada']))
    : null;

$hora_salida = !empty($_POST['hora_salida'])
    ? date("H:i:s", strtotime($_POST['hora_salida']))
    : null;

$observaciones = !empty($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;

$estado_input = $_POST['estado'] ?? null;

if (empty($usuario_id) || empty($fecha)) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Datos requeridos incompletos."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$estados_validos = ['abierta', 'cerrada', 'ausente'];

if (!empty($estado_input) && !in_array($estado_input, $estados_validos, true)) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Estado de asistencia no válido."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($estado_input === 'ausente') {
        $estado       = 'ausente';
        $hora_entrada = null;
        $hora_salida  = null;
    } else {
        if ($hora_entrada && $hora_salida) {
            $estado = 'cerrada';
        } elseif ($hora_entrada) {
            $estado = 'abierta';
        } else {
            $estado = 'ausente';
        }
    }

    $stmt = $conexion->prepare("
        INSERT INTO asistencia_entrenador
        (
            usuario_id,
            fecha,
            hora_entrada,
            hora_salida,
            estado,
            observaciones
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
        ON DUPLICATE KEY UPDATE
            hora_entrada  = VALUES(hora_entrada),
            hora_salida   = VALUES(hora_salida),
            estado        = VALUES(estado),
            observaciones = VALUES(observaciones)
    ");

    $stmt->execute([
        $usuario_id,
        $fecha,
        $hora_entrada,
        $hora_salida,
        $estado,
        $observaciones
    ]);

    echo json_encode(["status" => "ok"], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error BD: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}