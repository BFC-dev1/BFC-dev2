<?php

// ======================================================
// INICIAR SESIÓN
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// CARGAR CONFIGURACIÓN Y CONEXIÓN
// ======================================================

require_once(__DIR__ . "/../../../includes/config.php");
require_once(__DIR__ . "/../../../includes/conexion.php");


// ======================================================
// CARGAR FUNCIÓN DE AUDITORÍA
// ======================================================

require_once(
    __DIR__ . "/../../auditoria/funciones/registrar_auditoria.php"
);


// ======================================================
// VERIFICAR MÉTODO DE ENVÍO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;
}


// ======================================================
// RECIBIR DATOS
// ======================================================

$id_cuota = isset($_POST['id_cuota'])
    ? (int) $_POST['id_cuota']
    : 0;

$monto_pagado = isset($_POST['monto_pagado'])
    ? (float) $_POST['monto_pagado']
    : 0;

$metodo_pago = trim($_POST['metodo_pago'] ?? '');


// ======================================================
// USUARIO QUE REGISTRA EL PAGO
// ======================================================
//
// En el sistema estamos utilizando:
// $_SESSION["usuario_id"]
// ======================================================

$id_usuario = $_SESSION["usuario_id"] ?? null;


// ======================================================
// VALIDACIONES
// ======================================================

if ($id_cuota <= 0) {

    header(
        "Location: index.php?msg=" .
        urlencode("Error: cuota no válida.")
    );

    exit;
}


if ($monto_pagado <= 0) {

    header(
        "Location: index.php?msg=" .
        urlencode("Error: el monto pagado debe ser mayor que cero.")
    );

    exit;
}


if ($metodo_pago === '') {

    header(
        "Location: index.php?msg=" .
        urlencode("Error: debes seleccionar un método de pago.")
    );

    exit;
}


// ======================================================
// PROCESAR PAGO
// ======================================================

try {

    // ==================================================
    // INICIAR TRANSACCIÓN
    // ==================================================

    $conexion->beginTransaction();


    // ==================================================
    // OBTENER LA CUOTA
    // ==================================================
    //
    // FOR UPDATE bloquea el registro mientras se procesa.
    // ==================================================

    $stmt = $conexion->prepare("
        SELECT
            c.id,
            c.id_deportista,
            c.mes,
            c.anio,
            c.monto,
            c.fecha_vencimiento,
            c.fecha_pago,
            c.estado,
            d.nombre AS deportista_nombre
        FROM cuotas_mensuales c
        INNER JOIN deportista d
            ON d.id = c.id_deportista
        WHERE c.id = ?
        FOR UPDATE
    ");

    $stmt->execute([
        $id_cuota
    ]);

    $cuota = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==================================================
    // VERIFICAR EXISTENCIA
    // ==================================================

    if (!$cuota) {

        throw new Exception(
            "La cuota seleccionada no existe."
        );
    }


    // ==================================================
    // VERIFICAR QUE NO ESTÉ PAGADA
    // ==================================================

    if ($cuota['estado'] === 'pagado') {

        throw new Exception(
            "Esta cuota ya se encuentra registrada como pagada."
        );
    }


    // ==================================================
    // VALIDAR MONTO
    // ==================================================

    $monto_cuota = (float) $cuota['monto'];

    if (abs($monto_pagado - $monto_cuota) > 0.01) {

        throw new Exception(
            "El monto pagado ($" .
            number_format($monto_pagado, 2) .
            ") no coincide con el valor de la cuota ($" .
            number_format($monto_cuota, 2) .
            ")."
        );
    }


    // ==================================================
    // GUARDAR ESTADO ANTERIOR
    // ==================================================

    $estado_anterior = $cuota['estado'];


    // ==================================================
    // ACTUALIZAR CUOTA
    // ==================================================
    //
    // Cambiamos:
    //
    // estado       → pagado
    // fecha_pago   → fecha actual
    //
    // ==================================================

    $stmt = $conexion->prepare("
        UPDATE cuotas_mensuales
        SET
            estado = 'pagado',
            fecha_pago = CURDATE()
        WHERE id = ?
    ");

    $stmt->execute([
        $id_cuota
    ]);


    // ==================================================
    // REGISTRAR EL PAGO
    // ==================================================

    $stmt = $conexion->prepare("
        INSERT INTO transacciones_pago
        (
            id_cuota,
            monto_pagado,
            fecha_pago,
            metodo_pago,
            id_usuario_registra
        )
        VALUES (?, ?, NOW(), ?, ?)
    ");

    $stmt->execute([
        $id_cuota,
        $monto_pagado,
        $metodo_pago,
        $id_usuario
    ]);


    // ==================================================
    // NOMBRE DEL MES
    // ==================================================

    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    $nombre_mes = $meses[(int) $cuota['mes']]
        ?? $cuota['mes'];


    // ==================================================
    // CREAR CONCEPTO DEL MOVIMIENTO
    // ==================================================

    $concepto =
        "Mensualidad " .
        $nombre_mes .
        " " .
        $cuota['anio'] .
        " - " .
        $cuota['deportista_nombre'];


    // ==================================================
    // REGISTRAR MOVIMIENTO FINANCIERO
    // ==================================================
    //
    // El pago de una mensualidad es un INGRESO.
    // ==================================================

    $descripcion_movimiento =
        "Pago de cuota mensual. " .
        "Deportista: " .
        $cuota['deportista_nombre'] .
        ". Cuota ID: " .
        $id_cuota .
        ".";


    $stmt = $conexion->prepare("
        INSERT INTO movimientos
        (
            tipo,
            origen,
            concepto,
            monto,
            fecha,
            metodo_pago,
            referencia_id,
            id_usuario,
            descripcion
        )
        VALUES
        (
            'INGRESO',
            'MENSUALIDAD',
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $concepto,
        $monto_pagado,
        $metodo_pago,
        $id_cuota,
        $id_usuario,
        $descripcion_movimiento
    ]);


    // ==================================================
    // REGISTRAR AUDITORÍA
    // ==================================================

    $cambios = [
        'estado' => [
            'anterior' => $estado_anterior,
            'nuevo'    => 'pagado'
        ]
    ];


    $descripcion_auditoria =
        "Pago registrado de la mensualidad " .
        $nombre_mes .
        " " .
        $cuota['anio'] .
        " del deportista " .
        $cuota['deportista_nombre'] .
        ". Monto: $" .
        number_format($monto_pagado, 2) .
        ". Método de pago: " .
        $metodo_pago .
        ".";


    registrarAuditoria(
        $conexion,
        'cuotas_mensuales',
        $id_cuota,
        'CAMBIAR_ESTADO',
        $cambios,
        $descripcion_auditoria
    );


    // ==================================================
    // CONFIRMAR TRANSACCIÓN
    // ==================================================

    $conexion->commit();


    // ==================================================
    // REGRESAR A MENSUALIDADES
    // ==================================================

    header(
        "Location: index.php?msg=" .
        urlencode(
            "Pago registrado correctamente. " .
            "La cuota fue marcada como pagada."
        )
    );

    exit;


} catch (Exception $e) {

    // ==================================================
    // DESHACER TODO SI OCURRE UN ERROR
    // ==================================================

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }


    // ==================================================
    // REGRESAR CON ERROR
    // ==================================================

header(
    "Location: " .
    $url_base .
    "/modulos/financiero/mensualidades/index.php?msg=" .
    urlencode(
        "Error al registrar pago: " . $e->getMessage()
    )
);
exit;
}