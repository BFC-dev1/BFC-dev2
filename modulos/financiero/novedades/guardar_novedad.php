<?php

/*
=========================================================
MÓDULO FINANCIERO - NOVEDADES
SISTEMA: BELLAVISTA FC
ARCHIVO: guardar_novedad.php
=========================================================

RESPONSABILIDADES:

- Verificar sesión y permisos.
- Recibir los datos enviados por nueva_novedad.php.
- Validar la información.
- Validar tipo de novedad.
- Validar deportista.
- Insertar la novedad en novedades_financieras.
- Guardar el usuario que realizó el registro.
- Registrar la operación en auditoría.
- Redirigir al listado de novedades.

TABLA:

novedades_financieras
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
VALIDAR PERMISO DE GESTIÓN DE NOVEDADES
---------------------------------------------------------
*/

if (!tiene_permiso('novedades')) {

    header("Location: " . $url_base . "/index.php");

    exit;
}


/*
=========================================================
2. AUDITORÍA
=========================================================
*/

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");


/*
=========================================================
3. CONEXIÓN A BASE DE DATOS
=========================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=========================================================
4. VERIFICAR MÉTODO HTTP
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");

    exit;
}


/*
=========================================================
5. RECIBIR DATOS DEL FORMULARIO
=========================================================
*/

$id_deportista = trim($_POST['id_deportista'] ?? '');

$tipo = trim($_POST['tipo'] ?? '');

$concepto = trim($_POST['concepto'] ?? '');

$monto = trim($_POST['monto'] ?? '');

$fecha = trim($_POST['fecha'] ?? '');

$observacion = trim($_POST['observacion'] ?? '');


/*
=========================================================
6. NORMALIZAR ID DEL DEPORTISTA
=========================================================
*/

if ($id_deportista === '') {

    $id_deportista = null;

} else {

    $id_deportista = (int) $id_deportista;

}


/*
=========================================================
7. VALIDAR TIPO DE NOVEDAD
=========================================================
*/

$tipos_permitidos = [

    'DESCUENTO',
    'RECARGO',
    'BECA',
    'EXONERACION',
    'AJUSTE'

];


if (!in_array($tipo, $tipos_permitidos, true)) {

    header(
        "Location: nueva_novedad.php?error=tipo"
    );

    exit;
}


/*
=========================================================
8. VALIDAR CONCEPTO
=========================================================
*/

if ($concepto === '') {

    header(
        "Location: nueva_novedad.php?error=concepto"
    );

    exit;
}


/*
---------------------------------------------------------
VALIDAR LONGITUD DEL CONCEPTO
---------------------------------------------------------
*/

if (mb_strlen($concepto) > 255) {

    header(
        "Location: nueva_novedad.php?error=concepto_largo"
    );

    exit;
}


/*
=========================================================
9. VALIDAR MONTO
=========================================================
*/

if ($monto === '' || !is_numeric($monto)) {

    header(
        "Location: nueva_novedad.php?error=monto"
    );

    exit;
}


$monto = (float) $monto;


if ($monto < 0) {

    header(
        "Location: nueva_novedad.php?error=monto"
    );

    exit;
}


/*
=========================================================
10. FORMATEAR MONTO
=========================================================
*/

$monto = number_format(
    $monto,
    2,
    '.',
    ''
);


/*
=========================================================
11. VALIDAR FECHA
=========================================================
*/

$fecha_obj = DateTime::createFromFormat(
    'Y-m-d',
    $fecha
);


if (
    !$fecha_obj ||
    $fecha_obj->format('Y-m-d') !== $fecha
) {

    header(
        "Location: nueva_novedad.php?error=fecha"
    );

    exit;
}


/*
=========================================================
12. VALIDAR DEPORTISTA
=========================================================
*/

if ($id_deportista !== null) {

    $sql_deportista = "
        SELECT id
        FROM deportista
        WHERE id = :id
        LIMIT 1
    ";


    $stmt_deportista =
        $conexion->prepare($sql_deportista);


    $stmt_deportista->execute([

        ':id' => $id_deportista

    ]);


    $deportista_existe =
        $stmt_deportista->fetchColumn();


    if (!$deportista_existe) {

        header(
            "Location: nueva_novedad.php?error=deportista"
        );

        exit;
    }
}


/*
=========================================================
13. OBTENER USUARIO ACTUAL
=========================================================
*/

$usuario_id = $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? null;


/*
=========================================================
14. INICIAR TRANSACCIÓN
=========================================================
*/

try {

    $conexion->beginTransaction();


    /*
    =====================================================
    15. INSERTAR NOVEDAD
    =====================================================
    */

    $sql = "
        INSERT INTO novedades_financieras (

            id_deportista,
            tipo,
            concepto,
            monto,
            fecha,
            estado,
            observacion,
            id_usuario

        )
        VALUES (

            :id_deportista,
            :tipo,
            :concepto,
            :monto,
            :fecha,
            'ACTIVA',
            :observacion,
            :id_usuario

        )
    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([

        ':id_deportista' => $id_deportista,

        ':tipo' => $tipo,

        ':concepto' => $concepto,

        ':monto' => $monto,

        ':fecha' => $fecha,

        ':observacion' =>
            $observacion !== ''
                ? $observacion
                : null,

        ':id_usuario' => $usuario_id

    ]);


    /*
    =====================================================
    16. OBTENER ID DE LA NOVEDAD
    =====================================================
    */

    $novedad_id = $conexion->lastInsertId();


    /*
    =====================================================
    17. PREPARAR AUDITORÍA
    =====================================================
    */

    $cambios = [

        'id_deportista' => [

            'antes' => null,

            'despues' => $id_deportista

        ],

        'tipo' => [

            'antes' => null,

            'despues' => $tipo

        ],

        'concepto' => [

            'antes' => null,

            'despues' => $concepto

        ],

        'monto' => [

            'antes' => null,

            'despues' => $monto

        ],

        'fecha' => [

            'antes' => null,

            'despues' => $fecha

        ],

        'estado' => [

            'antes' => null,

            'despues' => 'ACTIVA'

        ],

        'observacion' => [

            'antes' => null,

            'despues' => $observacion

        ],

        'id_usuario' => [

            'antes' => null,

            'despues' => $usuario_id

        ]

    ];


    /*
    =====================================================
    18. REGISTRAR AUDITORÍA
    =====================================================
    */

    registrarAuditoria(

        $conexion,

        'novedades_financieras',

        $novedad_id,

        'CREAR',

        $cambios,

        'Registro de novedad financiera: ' . $concepto

    );


    /*
    =====================================================
    19. CONFIRMAR TRANSACCIÓN
    =====================================================
    */

    $conexion->commit();


    /*
    =====================================================
    20. REDIRECCIÓN
    =====================================================
    */

    header(
        "Location: index.php?registrado=1"
    );

    exit;

}


/*
=========================================================
21. MANEJO DE ERRORES
=========================================================
*/

catch (Exception $e) {


    /*
    -----------------------------------------------------
    SI LA TRANSACCIÓN SIGUE ABIERTA,
    DESHACER CAMBIOS
    -----------------------------------------------------
    */

    if ($conexion->inTransaction()) {

        $conexion->rollBack();

    }


    /*
    -----------------------------------------------------
    REGRESAR AL FORMULARIO
    -----------------------------------------------------
    */

    header(
        "Location: nueva_novedad.php?error=guardar"
    );

    exit;

}

?>