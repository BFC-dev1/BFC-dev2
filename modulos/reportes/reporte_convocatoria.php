<?php
session_start();
include("../conexion_modulos.php");

/*
=================================================
DATOS POST (solo info del partido)
=================================================
*/
$fecha = $_POST['fecha'] ?? "";
$rival = $_POST['rival'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";

/*
=================================================
CONVOCADOS DESDE SESIÓN (CORREGIDO)
=================================================
*/
$jugadores = $_SESSION['convocados'] ?? [];

/*
=================================================
VALIDACION
=================================================
*/
if (empty($jugadores)) {
    die("Debes seleccionar al menos un jugador.");
}

/*
=================================================
IDS PARA QUERY
=================================================
*/
$ids = array_keys($jugadores);

$placeholders = implode(",", array_fill(0, count($ids), "?"));

$sql = "
SELECT 
    d.nombre,
    d.documento,
    c.nombre AS categoria
FROM deportista d
LEFT JOIN categoria c ON c.id = d.categoria_id
WHERE d.id IN ($placeholders)
ORDER BY d.nombre ASC
";

$stmt = $conexion->prepare($sql);
$stmt->execute($ids);

/*
=================================================
NOMBRE ARCHIVO
=================================================
*/
$filename = "convocatoria_" . $fecha . ".csv";

/*
=================================================
HEADERS CSV
=================================================
*/
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/*
=================================================
OUTPUT CSV
=================================================
*/
$output = fopen("php://output", "w");

/* UTF-8 BOM */
fwrite($output, "\xEF\xBB\xBF");

/*
=================================================
ENCABEZADO
=================================================
*/
fputcsv($output, ["BELLAVISTA FC"]);
fputcsv($output, ["CONVOCATORIA OFICIAL"]);
fputcsv($output, []);

fputcsv($output, ["Fecha Partido", $fecha]);
fputcsv($output, ["Rival", $rival]);

/*
=================================================
CATEGORIA
=================================================
*/
$stmtCat = $conexion->prepare("
    SELECT nombre
    FROM categoria
    WHERE id = ?
");

$stmtCat->execute([$categoria_id]);

$categoria = $stmtCat->fetch(PDO::FETCH_ASSOC);

fputcsv($output, ["Categoría", $categoria['nombre'] ?? ""]);
fputcsv($output, []);

/*
=================================================
COLUMNAS
=================================================
*/
fputcsv($output, [
    "N°",
    "Jugador",
    "Documento"
]);

/*
=================================================
DATA
=================================================
*/
$contador = 1;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, [
        $contador,
        $row['nombre'],
        $row['documento']
    ]);

    $contador++;
}

fclose($output);
exit;
?>