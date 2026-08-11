<?php

include("../conexion_modulos.php");

/* =================================================
   FILTRO DE ESTADO

   Valores recibidos desde reportes.php:

   ""  = Todos
   "1" = Activos
   "0" = Inactivos
================================================= */

$estado = $_GET['estado'] ?? '';


/* =================================================
   CONSULTA BASE
================================================= */

$sql = "

    SELECT 

        u.nombre,
        u.tipo_documento,
        u.documento,
        u.telefono,
        u.correo,
        u.usuario,
        u.password,
        u.estado,

        r.nombre AS rol

    FROM usuario u

    LEFT JOIN rol r
        ON u.rol_id = r.id

";


/* =================================================
   APLICAR FILTRO DE ESTADO

   El formulario utiliza:

   1 = Activos
   0 = Inactivos

   La base de datos utiliza:

   'activo'
   'inactivo'
================================================= */

$params = [];

if ($estado === '1') {

    $sql .= " WHERE u.estado = :estado ";

    $params[':estado'] = 'activo';

}

elseif ($estado === '0') {

    $sql .= " WHERE u.estado = :estado ";

    $params[':estado'] = 'inactivo';

}


/* =================================================
   ORDENAR RESULTADOS
================================================= */

$sql .= " ORDER BY u.nombre ASC ";


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

   $data seguirá funcionando con el código
   que ya tienes debajo.
================================================= */

$data = $stmt;

/*
=================================================
NOMBRE ARCHIVO
=================================================
*/
$filename = "usuarios_" . date("Y-m-d_H-i-s") . ".csv";

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
CREAR OUTPUT CSV
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

    "Nombre",
    "Tipo Documento",
    "Documento",
    "Telefono",
    "Correo",
    "Usuario",
    "Password",
    "Rol",
    "Estado"

]);

/*
=================================================
RECORRER DATA
=================================================
*/
while($row = $data->fetch(PDO::FETCH_ASSOC)){

    fputcsv($output, [

        $row["nombre"],
        $row["tipo_documento"],
        $row["documento"],
        $row["telefono"],
        $row["correo"],
        $row["usuario"],
        $row["password"],
        $row["rol"],
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