<?php
include("../conexion_modulos.php");

$fecha = $_GET['fecha'];

$stmt = $conexion->prepare("
SELECT deportista_id, estado 
FROM asistencia 
WHERE fecha = :fecha
");

$stmt->execute([":fecha"=>$fecha]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));