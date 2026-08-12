<?php

/*
=========================================================
MÓDULO FINANCIERO - EGRESOS
SISTEMA: BELLAVISTA FC
ARCHIVO: guardar_egreso.php
=========================================================

RESPONSABILIDADES:

- Verificar sesión y permisos.
- Recibir los datos enviados por nuevo_egreso.php.
- Validar la información.
- Validar categoría.
- Validar método de pago.
- Insertar el egreso en egresos_financieros.
- Guardar el usuario que realizó el registro.
- Registrar la operación en auditoría.
- Redirigir al listado de egresos.

TABLA:

egresos_financieros
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
VALIDAR PERMISO DE GESTIÓN DE EGRESOS
---------------------------------------------------------
*/

if (!tiene_permiso('egresos')) {

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

$fecha = trim($_POST['fecha'] ?? '');

$categoria = trim($_POST['categoria'] ?? '');

$concepto = trim($_POST['concepto'] ?? '');

$monto = trim($_POST['monto'] ?? '');

$metodo_pago = trim($_POST['metodo_pago'] ?? '');

$observacion = trim($_POST['observacion'] ?? '');


/*
=========================================================
6. CATEGORÍAS PERMITIDAS
=========================================================

Las categorías pertenecen directamente al campo
"categoria" de egresos_financieros.

NO se utiliza una tabla categoria_egreso.
=========================================================
*/

$categorias_permitidas = [

    'Pago de deportistas',

    'Pago de entrenadores',

    'Pago de administración',

    'Compra de balones y material deportivo',

    'Compra de uniformes',

    'Mantenimiento de instalaciones',

    'Servicios públicos',

    'Transporte',

    'Arbitraje y competencias',

    'Inscripciones y torneos',

    'Publicidad y comunicaciones',

    'Papelería e insumos',

    'Equipos y tecnología',

    'Gastos bancarios',

    'Otros gastos'

];


/*
=========================================================
7. MÉTODOS DE PAGO PERMITIDOS
=========================================================
*/

$metodos_pago_permitidos = [

    'Efectivo',

    'Transferencia bancaria',

    'Nequi',

    'Daviplata',

    'PSE',

    'Tarjeta débito',

    'Tarjeta crédito',

    'Cheque'

];


/*
=========================================================
8. VALIDAR CAMPOS OBLIGATORIOS
=========================================================
*/

if (
    empty($fecha) ||
    empty($categoria) ||
    empty($concepto) ||
    empty($monto) ||
    empty($metodo_pago)
) {

    header("Location: nuevo_egreso.php?error=campos");

    exit;
}


/*
=========================================================
9. VALIDAR FECHA
=========================================================
*/

$fecha_objeto = DateTime::createFromFormat('Y-m-d', $fecha);

if (
    !$fecha_objeto ||
    $fecha_objeto->format('Y-m-d') !== $fecha
) {

    header("Location: nuevo_egreso.php?error=fecha");

    exit;
}


/*
=========================================================
10. VALIDAR CATEGORÍA
=========================================================
*/

if (!in_array($categoria, $categorias_permitidas, true)) {

    header("Location: nuevo_egreso.php?error=categoria");

    exit;
}


/*
=========================================================
11. VALIDAR MÉTODO DE PAGO
=========================================================
*/

if (!in_array($metodo_pago, $metodos_pago_permitidos, true)) {

    header("Location: nuevo_egreso.php?error=metodo");

    exit;
}


/*
=========================================================
12. VALIDAR CONCEPTO
=========================================================
*/

if (mb_strlen($concepto) > 255) {

    header("Location: nuevo_egreso.php?error=concepto");

    exit;
}


/*
=========================================================
13. VALIDAR MONTO
=========================================================
*/

if (
    !is_numeric($monto) ||
    (float)$monto <= 0
) {

    header("Location: nuevo_egreso.php?error=monto");

    exit;
}


/*
=========================================================
14. NORMALIZAR MONTO
=========================================================
*/

$monto = number_format(
    (float)$monto,
    2,
    '.',
    ''
);


/*
=========================================================
15. USUARIO QUE REALIZA EL REGISTRO
=========================================================

El sistema actual utiliza:

$_SESSION['id']

para identificar al usuario que realiza
la operación financiera.
=========================================================
*/

$usuario_id = $_SESSION['id'] ?? null;


/*
=========================================================
16. INSERTAR EGRESO
=========================================================
*/

try {

    /*
    -----------------------------------------------------
    INICIAR TRANSACCIÓN
    -----------------------------------------------------
    */

    $conexion->beginTransaction();


    /*
    -----------------------------------------------------
    INSERTAR EN egresos_financieros
    -----------------------------------------------------
    */

    $stmt = $conexion->prepare("

        INSERT INTO egresos_financieros
        (
            fecha,
            categoria,
            concepto,
            monto,
            metodo_pago,
            observacion,
            usuario_id
        )

        VALUES
        (
            :fecha,
            :categoria,
            :concepto,
            :monto,
            :metodo_pago,
            :observacion,
            :usuario_id
        )

    ");


    /*
    -----------------------------------------------------
    EJECUTAR INSERT
    -----------------------------------------------------
    */

    $stmt->execute([

        ':fecha' =>
            $fecha,

        ':categoria' =>
            $categoria,

        ':concepto' =>
            $concepto,

        ':monto' =>
            $monto,

        ':metodo_pago' =>
            $metodo_pago,

        ':observacion' =>
            !empty($observacion)
                ? $observacion
                : null,

        ':usuario_id' =>
            $usuario_id

    ]);


    /*
    =====================================================
    17. OBTENER ID DEL EGRESO
    =====================================================
    */

    $egreso_id = $conexion->lastInsertId();


    /*
    =====================================================
    18. PREPARAR AUDITORÍA
    =====================================================
    */

    $cambios = [

        'fecha' => [

            'antes' => null,

            'despues' => $fecha

        ],

        'categoria' => [

            'antes' => null,

            'despues' => $categoria

        ],

        'concepto' => [

            'antes' => null,

            'despues' => $concepto

        ],

        'monto' => [

            'antes' => null,

            'despues' => $monto

        ],

        'metodo_pago' => [

            'antes' => null,

            'despues' => $metodo_pago

        ],

        'observacion' => [

            'antes' => null,

            'despues' => $observacion

        ],

        'usuario_id' => [

            'antes' => null,

            'despues' => $usuario_id

        ]

    ];


    /*
    =====================================================
    19. REGISTRAR AUDITORÍA
    =====================================================
    */

    registrarAuditoria(

        $conexion,

        'egresos_financieros',

        $egreso_id,

        'CREAR',

        $cambios,

        'Registro de egreso financiero: ' . $concepto

    );


    /*
    =====================================================
    20. CONFIRMAR TRANSACCIÓN
    =====================================================
    */

    $conexion->commit();


    /*
    =====================================================
    21. REDIRECCIÓN
    =====================================================
    */

    header(
        "Location: index.php?registrado=1"
    );

    exit;


}


/*
=========================================================
22. MANEJO DE ERRORES
=========================================================
*/

catch (Exception $e) {


    /*
    -----------------------------------------------------
    SI LA TRANSACCIÓN SIGUE ABIERTA, DESHACER CAMBIOS
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
        "Location: nuevo_egreso.php?error=guardar"
    );

    exit;

}

?>