<?php
require_once("../../includes/config.php");
require_once("../../includes/verificar_roles.php");
include("../conexion_modulos.php");

header('Content-Type: application/json');

$usuario_id = $_GET['usuario_id'] ?? null;

if (!$usuario_id) {
    echo json_encode(['status' => 'error', 'mensaje' => 'ID de entrenador no proporcionado.']);
    exit;
}

try {
    // Filtramos por estado 'cerrada' y que además NO estén pagadas (pagado = 0 o NULL)
    $stmt = $conexion->prepare("
        SELECT id, fecha, hora_entrada, hora_salida, horas_trabajadas, pagado, egreso_id 
        FROM asistencia_entrenador 
        WHERE usuario_id = ? 
          AND estado = 'cerrada' 
          AND (pagado = 0 OR pagado IS NULL)
        ORDER BY fecha DESC
    ");
    $stmt->execute([$usuario_id]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear la fecha para que se vea amigable en la tabla
    foreach ($asistencias as &$row) {
        $row['fecha_formateada'] = date('d/m/Y', strtotime($row['fecha']));
    }

    echo json_encode(['status' => 'ok', 'data' => $asistencias]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error en base de datos: ' . $e->getMessage()]);
}