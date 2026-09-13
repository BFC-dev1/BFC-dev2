<?php
/*
=========================================================
MÓDULO: REPORTES - DETALLE DE ENTRENADORES (AJAX)
SISTEMA: BELLAVISTA FC
ARCHIVO: obtener_detalle_entrenador.php
=========================================================
*/

// Limpiar cualquier salida previa del búfer
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/../../includes/config.php");
include(__DIR__ . "/../conexion_modulos.php");

$usuario_id   = $_GET['usuario_id'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-t');

if (empty($usuario_id)) {
    echo json_encode(["status" => "error", "mensaje" => "ID de usuario requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conexion->prepare("
        SELECT 
            DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha_formateada,
            TIME_FORMAT(hora_entrada, '%h:%i %p') AS hora_entrada,
            TIME_FORMAT(hora_salida, '%h:%i %p') AS hora_salida,
            horas_trabajadas,
            estado,
            observaciones,
            pagado
        FROM asistencia_entrenador
        WHERE usuario_id = ? AND fecha BETWEEN ? AND ?
        ORDER BY fecha DESC
    ");
    $stmt->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "ok", "data" => $detalles], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error SQL: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;