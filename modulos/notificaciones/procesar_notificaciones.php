<?php

/**
 * ================================================================
 * PROCESAR NOTIFICACIONES WHATSAPP - BELLAVISTA FC
 * ================================================================
 *
 * Procesa:
 *
 * 1. Recordatorio 3 días antes
 * 2. Vencimiento el día de vencimiento
 * 3. Mensualidad vencida
 *
 * CONTROL ANTI-DUPLICADOS:
 * Cada cuota solo puede recibir una vez cada tipo
 * de notificación.
 *
 * IMPORTANTE:
 * - Solo procesa cuotas pendientes.
 * - Por ahora procesa UNA sola notificación por ejecución.
 * - No modifica mensualidades.
 * - No registra pagos.
 * - No toca las plantillas.
 * ================================================================
 */


/* ================================================================
   CONFIGURACIÓN
   ================================================================ */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modulos/conexion_modulos.php';
require_once __DIR__ . '/enviar_plantilla_whatsapp.php';


/* ================================================================
   FECHA ACTUAL
   ================================================================ */

$hoy = new DateTime(date('Y-m-d'));


/* ================================================================
   NOMBRES DE LOS MESES
   ================================================================ */

$nombres_meses = [
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


/* ================================================================
   OBTENER CUOTAS PENDIENTES
   ================================================================ */

$stmt = $conexion->prepare("
    SELECT
        c.id,
        c.id_deportista,
        c.mes,
        c.anio,
        c.monto,
        c.fecha_vencimiento,
        c.estado,

        d.nombre AS deportista_nombre,
        d.telefono AS telefono

    FROM cuotas_mensuales c

    INNER JOIN deportista d
        ON d.id = c.id_deportista

    WHERE c.estado = 'pendiente'
      AND c.fecha_vencimiento IS NOT NULL

    ORDER BY c.fecha_vencimiento ASC
");

$stmt->execute();

$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ================================================================
   CONTADORES
   ================================================================ */

$total_cuotas = count($cuotas);

$recordatorios = 0;
$vencimientos = 0;
$vencidas = 0;
$omitidas = 0;


/* ================================================================
   PROCESAR CUOTAS
   ================================================================ */

foreach ($cuotas as $cuota) {


    /* ------------------------------------------------------------
       FECHA DE VENCIMIENTO
       ------------------------------------------------------------ */

    try {

        $fecha_vencimiento = new DateTime(
            $cuota['fecha_vencimiento']
        );

    } catch (Exception $e) {

        $omitidas++;
        continue;
    }


    /* ------------------------------------------------------------
       DIFERENCIA EN DÍAS
       
       Ejemplos:

       3  = faltan 3 días
       0  = vence hoy
      -1  = venció ayer
      -2  = venció hace 2 días
       ------------------------------------------------------------ */

    $diferencia_dias = (int) $hoy->diff(
        $fecha_vencimiento
    )->format('%r%a');


    /* ------------------------------------------------------------
       DATOS DE LA CUOTA
       ------------------------------------------------------------ */

    $mes  = (int) $cuota['mes'];
    $anio = (int) $cuota['anio'];

    $periodo =
        ($nombres_meses[$mes] ?? 'Mes')
        . ' '
        . $anio;


    /* ------------------------------------------------------------
       VALOR
       ------------------------------------------------------------ */

    $valor = number_format(
        (float) $cuota['monto'],
        0,
        ',',
        '.'
    );


    /* ------------------------------------------------------------
       TELÉFONO
       ------------------------------------------------------------ */

    $telefono = preg_replace(
        '/[^0-9]/',
        '',
        $cuota['telefono'] ?? ''
    );


    /*
     * Si tiene 10 dígitos colombianos,
     * agregamos el código de país 57.
     */

    if (strlen($telefono) === 10) {
        $telefono = '57' . $telefono;
    }


    /* ============================================================
       1. RECORDATORIO — 3 DÍAS ANTES
       ============================================================ */

    if ($diferencia_dias === 3) {


        /* --------------------------------------------------------
           COMPROBAR ANTI-DUPLICADOS
           -------------------------------------------------------- */

        $check_notificacion = $conexion->prepare("
            SELECT id
            FROM whatsapp_notificaciones
            WHERE cuota_id = ?
              AND tipo_notificacion = 'recordatorio'
            LIMIT 1
        ");

        $check_notificacion->execute([
            $cuota['id']
        ]);

        $notificacion_ya_enviada =
            $check_notificacion->fetchColumn();


        /* --------------------------------------------------------
           SI YA FUE ENVIADA, CONTINUAR
           -------------------------------------------------------- */

        if ($notificacion_ya_enviada) {
            continue;
        }


        /* --------------------------------------------------------
           VERIFICAR TELÉFONO
           -------------------------------------------------------- */

        if (empty($telefono)) {

            $omitidas++;
            continue;
        }


        /* --------------------------------------------------------
           VARIABLES DE LA PLANTILLA
           -------------------------------------------------------- */

        $parametros_whatsapp = [
            'Carlos',
            $cuota['deportista_nombre'],
            $periodo,
            $valor
        ];


        /* --------------------------------------------------------
           ENVIAR WHATSAPP
           -------------------------------------------------------- */

        $resultado = enviarPlantillaWhatsApp(
            $telefono,
            'bellavista_recordatorio_pago',
            'en',
            $parametros_whatsapp
        );


        /* --------------------------------------------------------
           ENVÍO EXITOSO
           -------------------------------------------------------- */

        if (!empty($resultado['ok'])) {


            /* ----------------------------------------------------
               REGISTRAR NOTIFICACIÓN
               ---------------------------------------------------- */

            $registrar_notificacion = $conexion->prepare("
                INSERT INTO whatsapp_notificaciones
                (
                    cuota_id,
                    tipo_notificacion
                )
                VALUES (?, 'recordatorio')
            ");

            $registrar_notificacion->execute([
                $cuota['id']
            ]);


            $recordatorios++;


            echo "<pre>";

            echo "============================================\n";
            echo " WHATSAPP ENVIADO CORRECTAMENTE\n";
            echo "============================================\n\n";

            echo "Tipo: RECORDATORIO 3 DÍAS ANTES\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Período: "
                . $periodo
                . "\n";

            echo "Valor: $"
                . $valor
                . "\n";

            echo "Fecha vencimiento: "
                . $cuota['fecha_vencimiento']
                . "\n";

            echo "Días para vencimiento: "
                . $diferencia_dias
                . "\n";

            echo "Plantilla: "
                . "bellavista_recordatorio_pago"
                . "\n";

            echo "Idioma: en\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

            echo "\n============================================\n";
            echo " NOTIFICACIÓN REGISTRADA\n";
            echo "============================================\n";

            echo "</pre>";


            /*
             * Por ahora detenemos el proceso después
             * del primer envío exitoso.
             */

            break;


        } else {


            /* ----------------------------------------------------
               ERROR EN EL ENVÍO
               ---------------------------------------------------- */

            echo "<pre>";

            echo "============================================\n";
            echo " ERROR AL ENVIAR WHATSAPP\n";
            echo "============================================\n\n";

            echo "Tipo: RECORDATORIO 3 DÍAS ANTES\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Plantilla: "
                . "bellavista_recordatorio_pago"
                . "\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

echo "Error: ";

if (isset($resultado['error'])) {

    if (is_array($resultado['error'])) {

        echo json_encode(
            $resultado['error'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

    } else {

        echo $resultado['error'];
    }

} else {

    echo "Error desconocido";
}

echo "\n";

            echo "</pre>";

            break;
        }
    }


    /* ============================================================
       2. VENCIMIENTO — HOY
       ============================================================ */

    if ($diferencia_dias === 0) {


        /* --------------------------------------------------------
           COMPROBAR ANTI-DUPLICADOS
           -------------------------------------------------------- */

        $check_notificacion = $conexion->prepare("
            SELECT id
            FROM whatsapp_notificaciones
            WHERE cuota_id = ?
              AND tipo_notificacion = 'vencimiento'
            LIMIT 1
        ");

        $check_notificacion->execute([
            $cuota['id']
        ]);

        $notificacion_ya_enviada =
            $check_notificacion->fetchColumn();


        if ($notificacion_ya_enviada) {
            continue;
        }


        /* --------------------------------------------------------
           VERIFICAR TELÉFONO
           -------------------------------------------------------- */

        if (empty($telefono)) {

            $omitidas++;
            continue;
        }


        /* --------------------------------------------------------
           VARIABLES
           -------------------------------------------------------- */

        $parametros_whatsapp = [
            'Carlos',
            $cuota['deportista_nombre'],
            $periodo,
            $valor
        ];


        /* --------------------------------------------------------
           ENVIAR
           -------------------------------------------------------- */

        $resultado = enviarPlantillaWhatsApp(
            $telefono,
            'bellavista_vencimiento_mensualidad',
            'es',
            $parametros_whatsapp
        );


        /* --------------------------------------------------------
           ENVÍO EXITOSO
           -------------------------------------------------------- */

        if (!empty($resultado['ok'])) {


            /* ----------------------------------------------------
               REGISTRAR
               ---------------------------------------------------- */

            $registrar_notificacion = $conexion->prepare("
                INSERT INTO whatsapp_notificaciones
                (
                    cuota_id,
                    tipo_notificacion
                )
                VALUES (?, 'vencimiento')
            ");

            $registrar_notificacion->execute([
                $cuota['id']
            ]);


            $vencimientos++;


            echo "<pre>";

            echo "============================================\n";
            echo " WHATSAPP ENVIADO CORRECTAMENTE\n";
            echo "============================================\n\n";

            echo "Tipo: VENCIMIENTO HOY\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Período: "
                . $periodo
                . "\n";

            echo "Valor: $"
                . $valor
                . "\n";

            echo "Fecha vencimiento: "
                . $cuota['fecha_vencimiento']
                . "\n";

            echo "Plantilla: "
                . "bellavista_vencimiento_mensualidad"
                . "\n";

            echo "Idioma: es\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

            echo "\n============================================\n";
            echo " NOTIFICACIÓN REGISTRADA\n";
            echo "============================================\n";

            echo "</pre>";


            break;


        } else {


            /* ----------------------------------------------------
               ERROR
               ---------------------------------------------------- */

            echo "<pre>";

            echo "============================================\n";
            echo " ERROR AL ENVIAR WHATSAPP\n";
            echo "============================================\n\n";

            echo "Tipo: VENCIMIENTO HOY\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Plantilla: "
                . "bellavista_vencimiento_mensualidad"
                . "\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

            echo "Error: "
                . ($resultado['error'] ?? 'Error desconocido')
                . "\n";

            echo "</pre>";

            break;
        }
    }


    /* ============================================================
       3. MENSUALIDAD VENCIDA
       CONTROL ANTI-DUPLICADOS
       ============================================================ */

    if ($diferencia_dias < 0) {


        /* --------------------------------------------------------
           COMPROBAR SI YA SE ENVIÓ
           -------------------------------------------------------- */

        $check_notificacion = $conexion->prepare("
            SELECT id
            FROM whatsapp_notificaciones
            WHERE cuota_id = ?
              AND tipo_notificacion = 'vencida'
            LIMIT 1
        ");

        $check_notificacion->execute([
            $cuota['id']
        ]);

        $notificacion_ya_enviada =
            $check_notificacion->fetchColumn();


        /* --------------------------------------------------------
           SI YA FUE ENVIADA
           -------------------------------------------------------- */

        if ($notificacion_ya_enviada) {
            continue;
        }


        /* --------------------------------------------------------
           VERIFICAR TELÉFONO
           -------------------------------------------------------- */

        if (empty($telefono)) {

            $omitidas++;
            continue;
        }


        /* --------------------------------------------------------
           VARIABLES
           -------------------------------------------------------- */

        $parametros_whatsapp = [
            'Carlos',
            $cuota['deportista_nombre'],
            $periodo,
            $valor
        ];


        /* --------------------------------------------------------
           ENVIAR
           -------------------------------------------------------- */

        $resultado = enviarPlantillaWhatsApp(
            $telefono,
            'bellavista_mensualidad_vencida',
            'es',
            $parametros_whatsapp
        );


        /* --------------------------------------------------------
           ENVÍO EXITOSO
           -------------------------------------------------------- */

        if (!empty($resultado['ok'])) {


            /* ----------------------------------------------------
               REGISTRAR
               ---------------------------------------------------- */

            $registrar_notificacion = $conexion->prepare("
                INSERT INTO whatsapp_notificaciones
                (
                    cuota_id,
                    tipo_notificacion
                )
                VALUES (?, 'vencida')
            ");

            $registrar_notificacion->execute([
                $cuota['id']
            ]);


            $vencidas++;


            echo "<pre>";

            echo "============================================\n";
            echo " WHATSAPP ENVIADO CORRECTAMENTE\n";
            echo "============================================\n\n";

            echo "Tipo: MENSUALIDAD VENCIDA\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Período: "
                . $periodo
                . "\n";

            echo "Valor: $"
                . $valor
                . "\n";

            echo "Fecha vencimiento: "
                . $cuota['fecha_vencimiento']
                . "\n";

            echo "Días vencida: "
                . abs($diferencia_dias)
                . "\n";

            echo "Plantilla: "
                . "bellavista_mensualidad_vencida"
                . "\n";

            echo "Idioma: es\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

            echo "\n============================================\n";
            echo " NOTIFICACIÓN REGISTRADA\n";
            echo "============================================\n";

            echo "</pre>";


            break;


        } else {


            /* ----------------------------------------------------
               ERROR
               ---------------------------------------------------- */

            echo "<pre>";

            echo "============================================\n";
            echo " ERROR AL ENVIAR WHATSAPP\n";
            echo "============================================\n\n";

            echo "Tipo: MENSUALIDAD VENCIDA\n";

            echo "Cuota ID: "
                . $cuota['id']
                . "\n";

            echo "Deportista: "
                . $cuota['deportista_nombre']
                . "\n";

            echo "Teléfono: "
                . $telefono
                . "\n";

            echo "Plantilla: "
                . "bellavista_mensualidad_vencida"
                . "\n";

            echo "HTTP: "
                . ($resultado['http_code'] ?? 'N/D')
                . "\n";

echo "Error: ";

if (isset($resultado['error'])) {

    if (is_array($resultado['error'])) {

        echo json_encode(
            $resultado['error'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

    } else {

        echo $resultado['error'];
    }

} else {

    echo "Error desconocido";
}

echo "\n";

            echo "</pre>";

            break;
        }
    }
}


/* ================================================================
   FINAL
   ================================================================ */

if (
    $recordatorios === 0 &&
    $vencimientos === 0 &&
    $vencidas === 0
) {

    echo "<pre>";

    echo "============================================\n";
    echo " NO SE ENVIÓ NINGÚN WHATSAPP NUEVO\n";
    echo "============================================\n\n";

    echo "Cuotas pendientes encontradas: "
        . $total_cuotas
        . "\n";

    echo "Recordatorios (3 días antes): "
        . $recordatorios
        . "\n";

    echo "Vencimientos (hoy): "
        . $vencimientos
        . "\n";

    echo "Mensualidades vencidas: "
        . $vencidas
        . "\n";

    echo "Cuotas omitidas: "
        . $omitidas
        . "\n";

    echo "</pre>";
}
?>
