<?php

/*
=========================================================
MÓDULO FINANCIERO - NOVEDADES
SISTEMA: BELLAVISTA FC
ARCHIVO: eliminar_novedad.php

FUNCIÓN:
Eliminar una novedad financiera.

NOTA:
La eliminación se realiza mediante DELETE.
Después de eliminar correctamente se regresa
al index del módulo con ?eliminado=1.
=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN Y PERMISOS
=========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
---------------------------------------------------------
PERMISO
---------------------------------------------------------
*/

if (!tiene_permiso('egresos')) {

    header(
        "Location: " .
        $url_base .
        "/index.php"
    );

    exit;
}


/*
=========================================================
2. CONEXIÓN
=========================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=========================================================
3. OBTENER ID
=========================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
=========================================================
4. VALIDAR ID
=========================================================
*/

if (!$id || $id <= 0) {

    header(
        "Location: index.php?error=1"
    );

    exit;
}


/*
=========================================================
5. VERIFICAR QUE EXISTA
=========================================================
*/

$stmt = $conexion->prepare("

    SELECT id

    FROM novedades_financieras

    WHERE id = :id

    LIMIT 1

");


$stmt->execute([
    ':id' => $id
]);


$novedad = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
---------------------------------------------------------
NO EXISTE
---------------------------------------------------------
*/

if (!$novedad) {

    header(
        "Location: index.php?error=1"
    );

    exit;
}


/*
=========================================================
6. ELIMINAR
=========================================================
*/

try {


    /*
    -----------------------------------------------------
    DELETE
    -----------------------------------------------------
    */

    $stmt = $conexion->prepare("

        DELETE FROM novedades_financieras

        WHERE id = :id

    ");


    $stmt->execute([
        ':id' => $id
    ]);


    /*
    -----------------------------------------------------
    VERIFICAR ELIMINACIÓN
    -----------------------------------------------------
    */

    if (
        $stmt->rowCount() > 0
    ) {

        header(
            "Location: index.php?eliminado=1"
        );

        exit;

    }


    /*
    -----------------------------------------------------
    SI NO SE ELIMINÓ
    -----------------------------------------------------
    */

    header(
        "Location: index.php?error=1"
    );

    exit;


} catch (PDOException $e) {


    /*
    -----------------------------------------------------
    ERROR DE BASE DE DATOS
    -----------------------------------------------------
    */

    header(
        "Location: index.php?error=1"
    );

    exit;

}

?>