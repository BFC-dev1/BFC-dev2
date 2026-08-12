<?php

/*
=========================================================
MÓDULO FINANCIERO - ELIMINAR UNIFORME
=========================================================

Archivo:
    /modulos/financiero/uniformes/eliminar_uniforme.php

Función:
    Elimina un registro de la tabla uniformes.

IMPORTANTE:
    Este proyecto utiliza PDO.

    Por eso se utiliza:

        $stmt->execute([
            ':id' => $id
        ]);

    NO utilizar bind_param(), porque pertenece a MySQLi.

=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN
=========================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");


/*
=========================================================
2. VERIFICAR PERMISO DEL MÓDULO
=========================================================
*/

if (!tiene_permiso('financiero_uniformes')) {

    header(
        "Location: " . $url_base . "/index.php"
    );

    exit;
}


/*
=========================================================
3. CONEXIÓN A LA BASE DE DATOS
=========================================================
*/

require_once(
    "../../../modulos/conexion_modulos.php"
);


/*
=========================================================
4. OBTENER ID DEL UNIFORME
=========================================================

El botón del index enviará:

    eliminar_uniforme.php?id=1

=========================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
=========================================================
5. VALIDAR ID
=========================================================
*/

if (!$id || $id <= 0) {

    header(
        "Location: index.php?error=id_invalido"
    );

    exit;
}


/*
=========================================================
6. VERIFICAR QUE EL UNIFORME EXISTA
=========================================================
*/

$stmt = $conexion->prepare("
    SELECT
        id,
        deportista_id,
        tipo_uniforme,
        talla,
        cantidad,
        valor
    FROM uniformes
    WHERE id = :id
    LIMIT 1
");


$stmt->execute([
    ':id' => $id
]);


$uniforme = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
=========================================================
7. SI NO EXISTE EL REGISTRO
=========================================================
*/

if (!$uniforme) {

    header(
        "Location: index.php?error=no_encontrado"
    );

    exit;
}


/*
=========================================================
8. ELIMINAR EL UNIFORME
=========================================================
*/

try {

    /*
    -----------------------------------------------------
    Iniciar transacción
    -----------------------------------------------------
    */

    $conexion->beginTransaction();


    /*
    -----------------------------------------------------
    DELETE
    -----------------------------------------------------
    */

    $stmtEliminar = $conexion->prepare("
        DELETE FROM uniformes
        WHERE id = :id
    ");


    $stmtEliminar->execute([
        ':id' => $id
    ]);


    /*
    -----------------------------------------------------
    Confirmar eliminación
    -----------------------------------------------------
    */

    $conexion->commit();


    /*
    -----------------------------------------------------
    Regresar al index
    -----------------------------------------------------
    */

    header(
        "Location: index.php?eliminado=1"
    );

    exit;


} catch (PDOException $e) {


    /*
    =====================================================
    9. SI OCURRE UN ERROR
    =====================================================
    */

    /*
    Si la transacción está activa,
    deshacer los cambios.
    */

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    /*
    Regresar al index indicando error
    */

    header(
        "Location: index.php?error=eliminar"
    );

    exit;
}