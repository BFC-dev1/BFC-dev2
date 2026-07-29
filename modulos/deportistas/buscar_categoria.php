<?php

include("../../includes/conexion.php");

header('Content-Type: application/json');

$anio = intval($_GET['anio'] ?? 0);

$stmt = $conexion->prepare("
    SELECT id, nombre
    FROM categoria
    WHERE :anio BETWEEN anio_desde AND anio_hasta
    LIMIT 1
");

$stmt->execute([
    ":anio" => $anio
]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($categoria ?: []);

