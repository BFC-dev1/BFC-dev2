<?php

include("../conexion_modulos.php");

/*
=================================================
QUERY EXPORTAR DEPORTISTAS
=================================================
*/
/* =================================================
   FILTROS RECIBIDOS DESDE reportes.php

   estado:

   ""  = Todos
   "1" = Activos
   "0" = Inactivos

   categoria_id:

   ""  = Todas
   ID   = Categoría seleccionada
================================================= */

$estado = $_GET['estado'] ?? '';

$categoria_id = $_GET['categoria_id'] ?? '';



/* =================================================
   CONSULTA BASE
================================================= */

$sql = "

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

";


/* =================================================
   CONDICIONES DE FILTRO
================================================= */

$condiciones = [];

$params = [];


/* =================================================
   FILTRO DE ESTADO

   El formulario utiliza:

   1 = Activos
   0 = Inactivos

   La BD utiliza:

   'activo'
   'inactivo'
================================================= */

if ($estado === '1') {

    $condiciones[] = "d.estado = :estado";

    $params[':estado'] = 'activo';

}

elseif ($estado === '0') {

    $condiciones[] = "d.estado = :estado";

    $params[':estado'] = 'inactivo';

}


/* =================================================
   FILTRO DE CATEGORÍA
================================================= */

if ($categoria_id !== '' && ctype_digit($categoria_id)) {

    $condiciones[] = "d.categoria_id = :categoria_id";

    $params[':categoria_id'] = (int)$categoria_id;

}


/* =================================================
   AGREGAR WHERE

   Solo se agrega si existe al menos un filtro.
================================================= */

if (!empty($condiciones)) {

    $sql .= " WHERE " . implode(" AND ", $condiciones);

}


/* =================================================
   ORDENAR RESULTADOS
================================================= */

$sql .= " ORDER BY d.nombre ASC ";


/* =================================================
   PREPARAR CONSULTA
================================================= */

$stmt = $conexion->prepare($sql);


/* =================================================
   EJECUTAR CONSULTA
================================================= */

$stmt->execute($params);


/* =================================================
   RESULTADO

   $data mantiene el mismo funcionamiento
   que tenía el código original.
================================================= */

$data = $stmt;

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