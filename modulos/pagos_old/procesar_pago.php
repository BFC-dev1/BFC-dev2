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

require_once(__DIR__ . "/../../includes/config.php");
require_once(__DIR__ . "/../../includes/conexion.php");


// ======================================================
// CARGAR FUNCIÓN DE AUDITORÍA
// ======================================================
//
// Desde:
// modulos/financiero/mensualidades/
// subimos a:
// modulos/
// y entramos en:
// auditoria/funciones/
// ======================================================

require_once(__DIR__ . "/../../auditoria/funciones/registrar_auditoria.php");


// ======================================================
// VERIFICAR MÉTODO DE ENVÍO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;
}


// ======================================================
// RECIBIR DATOS DEL FORMULARIO
// ======================================================

$id_cuota     = isset($_POST['id_cuota']) ? (int) $_POST['id_cuota'] : 0;
$monto_pagado = isset($_POST['monto_pagado']) ? (float) $_POST['monto_pagado'] : 0;
$metodo_pago  = trim($_POST['metodo_pago'] ?? '');


// ======================================================
// USUARIO QUE REGISTRA EL PAGO
// ======================================================
//
// En el sistema estamos utilizando:
// $_SESSION["usuario_id"]
//
// Esto permite que el pago, movimiento y auditoría
// queden asociados al mismo usuario.
// ======================================================

$id_usuario = $_SESSION["usuario_id"] ?? null;


// ======================================================
// VALIDACIONES BÁSICAS
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
    // FOR UPDATE bloquea la cuota mientras se procesa
    // el pago y evita que dos procesos puedan pagar
    // simultáneamente la misma cuota.
    // ==================================================

    $stmt = $conexion->prepare("
        SELECT
            c.id,
            c.deportista_id,
            c.mes,
            c.anio,
            c.monto,
            c.estado,
            d.nombre AS deportista_nombre
        FROM cuotas_mensuales c
        INNER JOIN deportista d
            ON d.id = c.deportista_id
        WHERE c.id = ?
        FOR UPDATE
    ");

    $stmt->execute([
        $id_cuota
    ]);

    $cuota = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==================================================
    // VERIFICAR QUE LA CUOTA EXISTA
    // ==================================================

    if (!$cuota) {

        throw new Exception("La cuota seleccionada no existe.");
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
    // VERIFICAR EL MONTO
    // ==================================================
    //
    // Una cuota mensual se considera pagada completamente.
    // Por eso el monto recibido debe coincidir con el monto
    // de la cuota.
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
    // CAMBIAR ESTADO DE LA CUOTA
    // ==================================================

    $stmt = $conexion->prepare("
        UPDATE cuotas_mensuales
        SET estado = 'pagado'
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
    // PREPARAR CONCEPTO DEL MOVIMIENTO
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

    $nombre_mes = $meses[(int)$cuota['mes']] ?? $cuota['mes'];

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
    // Este registro convierte el pago de la mensualidad
    // en un INGRESO dentro del sistema financiero.
    //
    // referencia_id = ID de la cuota que originó
    //                 el movimiento.
    // ==================================================

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

    $descripcion_movimiento =
        "Pago de cuota mensual. " .
        "Deportista: " .
        $cuota['deportista_nombre'] .
        ". Cuota ID: " .
        $id_cuota .
        ".";

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
    //
    // Se registra el cambio de estado:
    //
    // anterior: pendiente/vencido
    // nuevo:    pagado
    //
    // La función registrarAuditoria() utiliza el usuario
    // almacenado en $_SESSION["usuario_id"].
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
    // CONFIRMAR TODA LA OPERACIÓN
    // ==================================================
    //
    // Solo llegamos aquí si:
    //
    // 1. La cuota existe.
    // 2. No estaba pagada.
    // 3. El monto es correcto.
    // 4. Se actualizó la cuota.
    // 5. Se registró la transacción.
    // 6. Se creó el movimiento financiero.
    // 7. Se registró la auditoría.
    //
    // Si cualquiera falla, el catch hace ROLLBACK.
    // ==================================================

    $conexion->commit();


    // ==================================================
    // REGRESAR A MENSUALIDADES
    // ==================================================

    header(
        "Location: index.php?msg=" .
        urlencode(
            "Pago registrado correctamente. " .
            "La cuota fue marcada como pagada y el ingreso fue registrado en movimientos."
        )
    );

    exit;


} catch (Exception $e) {


    // ==================================================
    // DESHACER TODA LA OPERACIÓN
    // ==================================================
    //
    // Si algo falla:
    //
    // - NO se marca la cuota como pagada.
    // - NO queda el registro del pago.
    // - NO queda el movimiento.
    // - NO queda la auditoría.
    //
    // Todo vuelve al estado anterior.
    // ==================================================

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }


    // ==================================================
    // REGRESAR CON EL ERROR
    // ==================================================

    header(
        "Location: index.php?msg=" .
        urlencode(
            "Error al registrar pago: " . $e->getMessage()
        )
    );

    exit;
}