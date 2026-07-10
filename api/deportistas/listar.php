<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include("../../modulos/conexion_modulos.php");

$stm = $conexion->prepare("
    SELECT
        d.*,
        c.nombre AS categoria_nombre,
        ud.acudiente AS acudiente_nombre
    FROM deportista d
    LEFT JOIN categoria c
        ON d.categoria_id = c.id
    LEFT JOIN usuario_deportista ud
        ON ud.deportista_id = d.id
    ORDER BY d.id DESC
");

$stm->execute();

$deportistas = $stm->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(
    $deportistas,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);