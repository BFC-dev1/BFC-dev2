<?php
include("../conexion_modulos.php");

/*
=========================
FILTROS
=========================
*/
$fecha = $_GET['fecha'] ?? date("Y-m-d");
$categoria_id = $_GET['categoria_id'] ?? "";

/*
=========================
QUERY BASE
=========================
*/
$sql = "
SELECT d.nombre, a.estado, a.fecha
FROM asistencia a
JOIN deportista d ON d.id = a.deportista_id
WHERE a.fecha = :fecha
";

$params = [
    ":fecha" => $fecha
];

/*
=========================
FILTRO POR CATEGORÍA
=========================
*/
if (!empty($categoria_id)) {
    $sql .= " AND d.categoria_id = :cat";
    $params[":cat"] = $categoria_id;
}

$sql .= " ORDER BY d.nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);

/*
=========================
NOMBRE ARCHIVO
=========================
*/
$filename = "reporte_asistencias_" . $fecha . ".csv";

/*
=========================
HEADERS CSV
=========================
*/
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/*
=========================
OUTPUT CSV
=========================
*/
$output = fopen("php://output", "w");

/* BOM UTF-8 (para que Excel no rompa tildes) */
fwrite($output, "\xEF\xBB\xBF");

/* ENCABEZADOS */
fputcsv($output, ["Deportista", "Estado", "Fecha"]);

/*
=========================
DATA
=========================
*/
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, [
        $row["nombre"],
        $row["estado"],
        $row["fecha"]
    ]);
}

fclose($output);
exit;