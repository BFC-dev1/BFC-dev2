<?php
/*
=========================================================
MÓDULO: ASISTENCIA DE ENTRENADORES
SISTEMA: BELLAVISTA FC
ARCHIVO: guardar_asistencia_entrenador.php
=========================================================

RESPONSABILIDADES:

- Registrar una nueva asistencia de un entrenador.
- Permitir múltiples marcaciones para el mismo entrenador
  en la misma fecha.
- Determinar automáticamente el estado:
    - abierta
    - cerrada
    - ausente
- Validar las horas de entrada y salida.
- Evitar jornadas con 0 horas.
- Mantener intacta la información de pago.
- Permitir que MySQL calcule horas_trabajadas.

IMPORTANTE:

Cada llamada a este archivo CREA una NUEVA marcación.

La edición de una marcación existente se realiza mediante:

    actualizar_asistencia_entrenador.php

La eliminación de una marcación existente se realiza mediante:

    eliminar_asistencia_entrenador.php
=========================================================
*/

require_once(__DIR__ . "/../../includes/config.php");
include("../conexion_modulos.php");

header('Content-Type: application/json; charset=utf-8');


/*
=========================================================
1. RECIBIR DATOS
=========================================================
*/

$usuario_id = $_POST['usuario_id'] ?? null;
$fecha      = $_POST['fecha'] ?? null;

$hora_entrada = !empty($_POST['hora_entrada'])
    ? date("H:i:s", strtotime($_POST['hora_entrada']))
    : null;

$hora_salida = !empty($_POST['hora_salida'])
    ? date("H:i:s", strtotime($_POST['hora_salida']))
    : null;

$observaciones = !empty($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;

$estado_input = $_POST['estado'] ?? null;


/*
=========================================================
2. VALIDAR DATOS OBLIGATORIOS
=========================================================
*/

if (empty($usuario_id) || empty($fecha)) {

    echo json_encode([
        "status"  => "error",
        "mensaje" => "Datos requeridos incompletos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
=========================================================
3. VALIDAR ESTADO RECIBIDO
=========================================================
*/

$estados_validos = [
    'abierta',
    'cerrada',
    'ausente'
];

if (
    !empty($estado_input) &&
    !in_array($estado_input, $estados_validos, true)
) {

    echo json_encode([
        "status"  => "error",
        "mensaje" => "Estado de asistencia no válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
=========================================================
4. DETERMINAR ESTADO Y VALIDAR HORARIOS
=========================================================
*/

try {

    /*
    -----------------------------------------------------
    CASO: AUSENTE
    -----------------------------------------------------
    */

    if ($estado_input === 'ausente') {

        $estado       = 'ausente';
        $hora_entrada = null;
        $hora_salida  = null;
    }

    /*
    -----------------------------------------------------
    CASO: TIENE ENTRADA Y SALIDA
    -----------------------------------------------------
    */

    elseif ($hora_entrada && $hora_salida) {

        /*
        Validar que la salida sea posterior a la entrada.
        */

        if ($hora_salida <= $hora_entrada) {

            echo json_encode([
                "status"  => "error",
                "mensaje" => "La hora de salida debe ser posterior a la hora de entrada."
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $estado = 'cerrada';
    }

    /*
    -----------------------------------------------------
    CASO: SOLO TIENE ENTRADA
    -----------------------------------------------------
    */

    elseif ($hora_entrada) {

        $estado = 'abierta';
    }

    /*
    -----------------------------------------------------
    CASO: NO TIENE HORAS
    -----------------------------------------------------
    */

    else {

        $estado = 'ausente';
    }


    /*
    =====================================================
    5. INSERTAR NUEVA MARCACIÓN
    =====================================================

    IMPORTANTE:

    NO utilizamos:

        ON DUPLICATE KEY UPDATE

    porque ahora un entrenador puede tener múltiples
    registros el mismo día.

    Ejemplo:

        08:00 - 12:00
        14:00 - 18:00
        19:00 - 21:00

    Cada período tendrá un ID diferente.

    Tampoco modificamos:

        estado_pago
        egreso_id
        pagado

    Esos campos serán administrados posteriormente por
    el proceso financiero.

    La columna horas_trabajadas queda fuera del INSERT
    porque MySQL la calcula automáticamente.
    =====================================================
    */

    $stmt = $conexion->prepare("
        INSERT INTO asistencia_entrenador
        (
            usuario_id,
            fecha,
            hora_entrada,
            hora_salida,
            estado,
            observaciones
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $usuario_id,
        $fecha,
        $hora_entrada,
        $hora_salida,
        $estado,
        $observaciones
    ]);


    /*
    =====================================================
    6. OBTENER ID DE LA NUEVA MARCACIÓN
    =====================================================
    */

    $nuevo_id = $conexion->lastInsertId();


    /*
    =====================================================
    7. RESPUESTA EXITOSA
    =====================================================
    */

    echo json_encode([
        "status"       => "ok",
        "mensaje"      => "Asistencia guardada correctamente.",
        "id"           => (int)$nuevo_id,
        "estado"       => $estado
    ], JSON_UNESCAPED_UNICODE);


} catch (PDOException $e) {

    /*
    =====================================================
    8. ERROR DE BASE DE DATOS
    =====================================================
    */

    echo json_encode([
        "status"  => "error",
        "mensaje" => "Error BD: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

