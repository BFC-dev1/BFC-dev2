<?php

// ======================================================
// PROCESAR PAGO DE MENSUALIDAD
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// DOMPDF
// ======================================================

require_once(
    __DIR__ . "/../comprobantes_pago/dompdf/autoload.inc.php"
);

use Dompdf\Dompdf;


// ======================================================
// CONFIGURACIÓN
// ======================================================

require_once(
    __DIR__ . "/../../../includes/config.php"
);

require_once(
    __DIR__ . "/../../../includes/conexion.php"
);


// ======================================================
// WHATSAPP
// ======================================================

require_once(
    __DIR__ . "/../../notificaciones/enviar_plantilla_whatsapp.php"
);


// ======================================================
// AUDITORÍA
// ======================================================

require_once(
    __DIR__ . "/../../auditoria/funciones/registrar_auditoria.php"
);


// ======================================================
// VERIFICAR MÉTODO
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        $url_base .
        "/modulos/financiero/mensualidades/index.php"
    );

    exit;
}


// ======================================================
// DATOS RECIBIDOS
// ======================================================

$id_cuota = isset($_POST['id_cuota'])
    ? (int) $_POST['id_cuota']
    : 0;

$monto_pagado = isset($_POST['monto_pagado'])
    ? (float) $_POST['monto_pagado']
    : 0;

$metodo_pago = trim(
    $_POST['metodo_pago'] ?? ''
);


// ======================================================
// USUARIO
// ======================================================

$id_usuario =
    $_SESSION['usuario_id'] ??
    null;


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
        urlencode(
            "Error: el monto pagado debe ser mayor que cero."
        )
    );

    exit;
}


if ($metodo_pago === '') {

    header(
        "Location: index.php?msg=" .
        urlencode(
            "Error: debes seleccionar un método de pago."
        )
    );

    exit;
}


// ======================================================
// PROCESAR
// ======================================================

try {

    $conexion->beginTransaction();


    // ==================================================
    // OBTENER CUOTA
    // ==================================================
    //
    // IMPORTANTE:
    //
    // El acudiente se obtiene desde:
    //
    // usuario_deportista.acudiente
    //
    // NO desde deportista.acudiente
    //
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

            d.nombre AS deportista_nombre,
            d.documento AS documento_deportista,
            d.telefono AS telefono,

            ud.acudiente AS acudiente_nombre

        FROM cuotas_mensuales c

        INNER JOIN deportista d
            ON d.id = c.id_deportista

        LEFT JOIN usuario_deportista ud
            ON ud.deportista_id = c.id_deportista

        WHERE c.id = ?

        FOR UPDATE

    ");

    $stmt->execute([
        $id_cuota
    ]);

    $cuota = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    // ==================================================
    // VALIDAR CUOTA
    // ==================================================

    if (!$cuota) {

        throw new Exception(
            "La cuota seleccionada no existe."
        );
    }


    // ==================================================
    // VALIDAR ESTADO
    // ==================================================

    if ($cuota['estado'] === 'pagado') {

        throw new Exception(
            "Esta cuota ya se encuentra registrada como pagada."
        );
    }


    // ==================================================
    // VALIDAR MONTO
    // ==================================================

    $monto_cuota =
        (float) $cuota['monto'];


    if (
        abs(
            $monto_pagado -
            $monto_cuota
        ) > 0.01
    ) {

        throw new Exception(
            "El monto pagado ($" .
            number_format(
                $monto_pagado,
                2
            ) .
            ") no coincide con el valor de la cuota ($" .
            number_format(
                $monto_cuota,
                2
            ) .
            ")."
        );
    }


    // ==================================================
    // ESTADO ANTERIOR
    // ==================================================

    $estado_anterior =
        $cuota['estado'];


    // ==================================================
    // ACTUALIZAR CUOTA
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
    // REGISTRAR TRANSACCIÓN
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

        VALUES
        (
            ?,
            ?,
            NOW(),
            ?,
            ?
        )

    ");

    $stmt->execute([

        $id_cuota,
        $monto_pagado,
        $metodo_pago,
        $id_usuario

    ]);


    $id_transaccion =
        (int) $conexion->lastInsertId();


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


    $nombre_mes =
        $meses[
            (int) $cuota['mes']
        ] ?? $cuota['mes'];


    // ==================================================
    // CONCEPTO
    // ==================================================

    $concepto =
        "Mensualidad " .
        $nombre_mes .
        " " .
        $cuota['anio'] .
        " - " .
        $cuota['deportista_nombre'];


    // ==================================================
    // DESCRIPCIÓN
    // ==================================================

    $descripcion_movimiento =
        "Pago de cuota mensual. " .
        "Deportista: " .
        $cuota['deportista_nombre'] .
        ". Cuota ID: " .
        $id_cuota .
        ".";


    // ==================================================
    // MOVIMIENTO FINANCIERO
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


    $stmt->execute([

        $concepto,
        $monto_pagado,
        $metodo_pago,
        $id_cuota,
        $id_usuario,
        $descripcion_movimiento

    ]);


    // ==================================================
    // AUDITORÍA
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
        number_format(
            $monto_pagado,
            2
        ) .
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
    // CONFIRMAR
    // ==================================================

    $conexion->commit();


    // ==================================================
    // PREPARAR ACUDIENTE
    // ==================================================

    $acudiente_nombre =
        trim(
            $cuota['acudiente_nombre'] ?? ''
        );


    if ($acudiente_nombre === '') {

        $acudiente_nombre =
            'No registrado';
    }


    // ==================================================
    // GENERAR PDF
    // ==================================================

    $comprobante_resultado = [

        'ok'      => false,
        'archivo' => null,
        'ruta'    => null,
        'error'   => null

    ];


    try {

        // ==================================================
        // FECHA
        // ==================================================

        $fecha_comprobante =
            date('Y-m-d H:i:s');

        $fecha_archivo =
            date('Ymd');

        $anio_archivo =
            date('Y');


        // ==================================================
        // DOCUMENTO
        // ==================================================

        $documento_deportista =
            preg_replace(
                '/[^0-9A-Za-z_-]/',
                '',
                trim(
                    $cuota['documento_deportista'] ?? ''
                )
            );


        if ($documento_deportista === '') {

            throw new Exception(
                'El deportista no tiene documento registrado.'
            );
        }


        // ==================================================
        // NÚMERO COMPROBANTE
        // ==================================================

        $numero_comprobante =
            'BFC-COMP-' .
            $fecha_archivo .
            '-' .
            $documento_deportista;


        // ==================================================
        // CARPETA PDF
        // ==================================================

        $carpeta_pdf =
            __DIR__ .
            '/../comprobantes_pago/comprobantes/pdf/' .
            $anio_archivo .
            '/';


        // ==================================================
        // CREAR CARPETA
        // ==================================================

        if (!is_dir($carpeta_pdf)) {

            if (
                !mkdir(
                    $carpeta_pdf,
                    0775,
                    true
                )
            ) {

                throw new Exception(
                    'No fue posible crear la carpeta PDF.'
                );
            }
        }


        // ==================================================
        // ARCHIVO
        // ==================================================

        $nombre_archivo =
            $numero_comprobante .
            '.pdf';


        $ruta_pdf =
            $carpeta_pdf .
            $nombre_archivo;


        // ==================================================
        // EVITAR SOBRESCRIBIR
        // ==================================================

        if (file_exists($ruta_pdf)) {

            $contador = 1;

            do {

                $nombre_archivo =
                    $numero_comprobante .
                    '_' .
                    str_pad(
                        (string) $contador,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ) .
                    '.pdf';

                $ruta_pdf =
                    $carpeta_pdf .
                    $nombre_archivo;

                $contador++;

            } while (
                file_exists($ruta_pdf)
            );
        }


        // ==================================================
        // DATOS PDF
        // ==================================================

        $pdf_numero =
            htmlspecialchars(
                $numero_comprobante,
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_fecha =
            htmlspecialchars(
                date(
                    'd/m/Y H:i',
                    strtotime(
                        $fecha_comprobante
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_deportista =
            htmlspecialchars(
                $cuota['deportista_nombre'],
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_documento =
            htmlspecialchars(
                $cuota['documento_deportista'],
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_acudiente =
            htmlspecialchars(
                $acudiente_nombre,
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_concepto =
            htmlspecialchars(
                'Mensualidad ' .
                $nombre_mes .
                ' ' .
                $cuota['anio'],
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_metodo_pago =
            htmlspecialchars(
                $metodo_pago,
                ENT_QUOTES,
                'UTF-8'
            );


        $pdf_valor =
            '$' .
            number_format(
                $monto_pagado,
                0,
                ',',
                '.'
            );


        // ==================================================
        // HTML PDF
        // ==================================================

        $html = '

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="UTF-8">

            <style>

                body {

                    font-family: Arial, sans-serif;

                    margin: 40px;

                    color: #222;

                }


                .encabezado {

                    text-align: center;

                    margin-bottom: 30px;

                }


                .club {

                    font-size: 26px;

                    font-weight: bold;

                }


                .titulo {

                    font-size: 18px;

                    margin-top: 8px;

                }


                .linea {

                    border-bottom:
                        1px solid #999;

                    margin: 20px 0;

                }


                .dato {

                    margin-bottom: 12px;

                    font-size: 14px;

                }


                .total {

                    margin-top: 30px;

                    padding: 15px;

                    border: 1px solid #999;

                    text-align: center;

                    font-size: 20px;

                    font-weight: bold;

                }


                .estado {

                    margin-top: 20px;

                    text-align: center;

                    font-size: 16px;

                    font-weight: bold;

                }


                .pie {

                    margin-top: 40px;

                    text-align: center;

                    font-size: 12px;

                }

            </style>

        </head>


        <body>


            <div class="encabezado">

                <div class="club">
                    BELLAVISTA FC
                </div>

                <div class="titulo">
                    COMPROBANTE DE PAGO
                </div>

            </div>


            <div class="linea"></div>


            <div class="dato">

                <strong>
                    No. comprobante:
                </strong>

                ' . $pdf_numero . '

            </div>


            <div class="dato">

                <strong>
                    Fecha:
                </strong>

                ' . $pdf_fecha . '

            </div>


            <div class="dato">

                <strong>
                    Deportista:
                </strong>

                ' . $pdf_deportista . '

            </div>


            <div class="dato">

                <strong>
                    Documento:
                </strong>

                ' . $pdf_documento . '

            </div>


            <div class="dato">

                <strong>
                    Acudiente:
                </strong>

                ' . $pdf_acudiente . '

            </div>


            <div class="dato">

                <strong>
                    Concepto:
                </strong>

                ' . $pdf_concepto . '

            </div>


            <div class="dato">

                <strong>
                    Método de pago:
                </strong>

                ' . $pdf_metodo_pago . '

            </div>


            <div class="total">

                TOTAL PAGADO

                <br><br>

                ' . $pdf_valor . '

            </div>


            <div class="estado">

                ESTADO: PAGADO

            </div>


            <div class="pie">

                Gracias por realizar su pago.

                <br>

                Bellavista FC

            </div>


        </body>

        </html>

        ';


        // ==================================================
        // DOMPDF
        // ==================================================

        $dompdf =
            new Dompdf();


        $dompdf->loadHtml(
            $html
        );


        $dompdf->setPaper(
            'A4',
            'portrait'
        );


        $dompdf->render();


        // ==================================================
        // GUARDAR
        // ==================================================

        $pdf_generado =
            $dompdf->output();


        if (
            file_put_contents(
                $ruta_pdf,
                $pdf_generado
            ) === false
        ) {

            throw new Exception(
                'No fue posible guardar el PDF.'
            );
        }


        // ==================================================
        // VERIFICAR
        // ==================================================

        if (
            !file_exists($ruta_pdf) ||
            filesize($ruta_pdf) <= 0
        ) {

            throw new Exception(
                'El PDF no fue generado correctamente.'
            );
        }


        $comprobante_resultado = [

            'ok' => true,

            'archivo' =>
                $nombre_archivo,

            'ruta' =>
                $ruta_pdf,

            'error' =>
                null

        ];


    } catch (Throwable $e) {

        $comprobante_resultado = [

            'ok' => false,

            'archivo' => null,

            'ruta' => null,

            'error' =>
                $e->getMessage()

        ];

    }


    // ==================================================
    // WHATSAPP
    // ==================================================

    $whatsapp_resultado = null;


    if (!empty($cuota['telefono'])) {

        $telefono_whatsapp =
            preg_replace(
                '/[^0-9]/',
                '',
                $cuota['telefono']
            );


        if (
            strlen($telefono_whatsapp) === 10
        ) {

            $telefono_whatsapp =
                '57' .
                $telefono_whatsapp;
        }


        $parametros_whatsapp = [

            $acudiente_nombre,

            $cuota['deportista_nombre'],

            $nombre_mes .
            ' ' .
            $cuota['anio'],

            number_format(
                $monto_pagado,
                0,
                ',',
                '.'
            )

        ];


        $whatsapp_resultado =
            enviarPlantillaWhatsApp(

                $telefono_whatsapp,

                'bellavista_comprobante_pago',

                'es',

                $parametros_whatsapp,

                $comprobante_resultado['ruta']

            );

    } else {

        $whatsapp_resultado = [

            'ok' => false,

            'error' =>
                'El deportista no tiene teléfono registrado.'

        ];

    }


    // ==================================================
    // MENSAJE FINAL
    // ==================================================

    $mensaje_final =
        "Pago registrado correctamente.";


    if (
        $comprobante_resultado['ok']
    ) {

        $mensaje_final .=
            " Comprobante PDF generado correctamente.";

    } else {

        $mensaje_final .=
            " Advertencia: el PDF no pudo generarse. " .
            (
                $comprobante_resultado['error']
                ??
                'Error desconocido.'
            );

    }


    if (
        !$whatsapp_resultado ||
        !$whatsapp_resultado['ok']
    ) {

        $mensaje_final .=
            " WhatsApp no fue enviado.";

    }


    // ==================================================
    // REGRESAR
    // ==================================================

    header(
        "Location: index.php?msg=" .
        urlencode(
            $mensaje_final
        )
    );

    exit;


} catch (Throwable $e) {

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        $url_base .
        "/modulos/financiero/mensualidades/index.php?msg=" .
        urlencode(
            "Error al registrar pago: " .
            $e->getMessage()
        )
    );

    exit;
}