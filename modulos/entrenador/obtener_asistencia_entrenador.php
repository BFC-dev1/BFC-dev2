<?php
require_once(__DIR__ . "/../../includes/config.php");
include("../conexion_modulos.php");

header('Content-Type: application/json; charset=utf-8');

$fecha = $_GET['fecha'] ?? date('Y-m-d');

$stmt = $conexion->prepare("
    SELECT 
        usuario_id,
        TIME_FORMAT(hora_entrada, '%H:%i') AS hora_entrada,
        TIME_FORMAT(hora_salida, '%H:%i') AS hora_salida,
        horas_trabajadas,
        estado,
        observaciones
    FROM asistencia_entrenador
    WHERE fecha = ?
    ORDER BY usuario_id ASC
");

$stmt->execute([$fecha]);

echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC),
    JSON_UNESCAPED_UNICODE
);