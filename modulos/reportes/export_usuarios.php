<?php
include("../conexion_modulos.php");

/*
=========================
QUERY
=========================
*/
$data = $conexion->query("
    SELECT id, nombre, estado 
    FROM usuario 
    ORDER BY nombre ASC
");

/*
=========================
NOMBRE ARCHIVO
=========================
*/
$filename = "usuarios.csv";

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

/* BOM UTF-8 (evita problemas con tildes en Excel) */
fwrite($output, "\xEF\xBB\xBF");

/* Encabezados */
fputcsv($output, ["ID", "Nombre", "Estado"]);

/* Data */
while ($row = $data->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, [
        $row["id"],
        $row["nombre"],
        $row["estado"]
    ]);
}

fclose($output);
exit;