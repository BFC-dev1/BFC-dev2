<?php

session_start();

include("../conexion_modulos.php");

/*
=================================================
DATOS POST (INFO PARTIDO)
=================================================
*/
$fecha = $_POST['fecha'] ?? "";
$rival = $_POST['rival'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";

/*
=================================================
CONVOCADOS DESDE SESIÓN
=================================================
*/
$jugadores = $_SESSION['convocados'] ?? [];

/*
=================================================
VALIDACION
=================================================
*/
if(empty($jugadores)){

    die("Debes seleccionar al menos un jugador.");

}

/*
=================================================
IDS PARA QUERY
=================================================
*/
$ids = array_keys($jugadores);

$placeholders = implode(",", array_fill(0, count($ids), "?"));

/*
=================================================
CONSULTAR JUGADORES
=================================================
*/
$sql = "

SELECT 

    d.tipo_documento,
    d.documento,
    d.telefono,
    d.nombre,
    d.fecha_nacimiento,
    d.foto,
    d.estado,

    c.nombre AS categoria,

    ud.acudiente,
    ud.parentesco,
    ud.entrenador

FROM deportista d

LEFT JOIN categoria c
    ON c.id = d.categoria_id

LEFT JOIN usuario_deportista ud
    ON ud.deportista_id = d.id

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

/*
=================================================
UTF-8 BOM
=================================================
*/
fwrite($output, "\xEF\xBB\xBF");

/*
=================================================
ENCABEZADO REPORTE
=================================================
*/
fputcsv($output, ["BELLAVISTA FC"]);
fputcsv($output, ["CONVOCATORIA OFICIAL"]);
fputcsv($output, []);

fputcsv($output, ["Fecha Partido", $fecha]);
fputcsv($output, ["Rival", $rival]);

/*
=================================================
CONSULTAR CATEGORIA
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
COLUMNAS COMPLETAS
=================================================
*/
fputcsv($output, [

    "N°",
    "Tipo Documento",
    "Documento",
    "Telefono",
    "Nombre",
    "Fecha Nacimiento",
    "Categoria",
    "Entrenador",
    "Acudiente",
    "Parentesco",
    "Estado"

]);

/*
=================================================
DATA
=================================================
*/
$contador = 1;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

    fputcsv($output, [

        $contador,
        $row["tipo_documento"],
        $row["documento"],
        $row["telefono"],
        $row["nombre"],
        $row["fecha_nacimiento"],
        $row["categoria"],
        $row["entrenador"],
        $row["acudiente"],
        $row["parentesco"],
        $row["estado"]

    ]);

    $contador++;

}

/*
=================================================
CERRAR OUTPUT
=================================================
*/
fclose($output);

exit;

?>