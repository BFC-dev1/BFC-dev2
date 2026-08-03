<?php

include("../conexion_modulos.php");

$categoria_id = $_GET['categoria_id'] ?? 0;


$stmt = $conexion->prepare("
SELECT 
    u.id,
    u.nombre

FROM usuario u

INNER JOIN entrenador_categoria ec
ON u.id = ec.usuario_id

WHERE ec.categoria_id = :categoria_id
AND u.estado = 'activo'
AND u.rol_id = 3

ORDER BY u.nombre ASC
");


$stmt->execute([
    ":categoria_id"=>$categoria_id
]);


echo json_encode(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);