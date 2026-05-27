<?php

include("../conexion_modulos.php");

/*
=================================================
QUERY EXPORTAR DEPORTISTAS
=================================================
*/
$data = $conexion->query("

    SELECT 

        d.id,
        d.tipo_documento,
        d.documento,
        d.telefono,
        d.nombre,
        d.fecha_nacimiento,
        d.entrenador,
        d.estado,

        c.nombre AS categoria,

        ud.acudiente,
        ud.parentesco

    FROM deportista d

    LEFT JOIN categoria c
        ON d.categoria_id = c.id

    LEFT JOIN usuario_deportista ud
        ON ud.deportista_id = d.id

    ORDER BY d.nombre ASC

");

/*
=================================================
NOMBRE ARCHIVO
=================================================
*/
$filename = "deportistas_" . date("Y-m-d_H-i-s") . ".csv";

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
ABRIR OUTPUT
=================================================
*/
$output = fopen("php://output", "w");

/*
=================================================
UTF-8 BOM
EVITA PROBLEMAS CON TILDES EN EXCEL
=================================================
*/
fwrite($output, "\xEF\xBB\xBF");

/*
=================================================
ENCABEZADOS CSV
=================================================
*/
fputcsv($output, [

    
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
RECORRER DATA
=================================================
*/
while($row = $data->fetch(PDO::FETCH_ASSOC)){

    fputcsv($output, [

        
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

}

/*
=================================================
CERRAR OUTPUT
=================================================
*/
fclose($output);

exit;

?>