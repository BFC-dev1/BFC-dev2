<?php

/*
=================================================
REPORTE DE CONVOCATORIA
=================================================

MODO DIAGNÓSTICO

- Genera el PDF en memoria.
- NO guarda el PDF permanentemente.
- Intenta subir el PDF a Meta.
- Intenta enviar la plantilla de WhatsApp.
- Muestra en pantalla las respuestas completas.
- NO descarga el PDF durante esta prueba.

Cuando terminemos el diagnóstico se puede volver
a activar la descarga del PDF.
=================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
=================================================
CONFIGURACIÓN GENERAL
=================================================
*/

require_once(__DIR__ . "/../../includes/config.php");


/*
=================================================
DOMPDF
=================================================
*/

require_once(
    __DIR__ . "/../financiero/comprobantes_pago/dompdf/autoload.inc.php"
);


/*
=================================================
ENVÍO WHATSAPP
=================================================
*/

require_once(
    __DIR__ . "/../notificaciones/enviar_convocatoria_whatsapp.php"
);

use Dompdf\Dompdf;


/*
=================================================
MODO DIAGNÓSTICO
=================================================

TRUE  = muestra diagnóstico
FALSE = descarga PDF normalmente

Por ahora debe permanecer TRUE.
=================================================
*/

$modo_diagnostico = true;


/*
=================================================
VALIDAR MÉTODO
=================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo "<h2>Acceso no permitido</h2>";
    echo "<p>Este archivo debe ejecutarse mediante POST.</p>";

    exit;
}


/*
=================================================
OBTENER CONVOCADOS
=================================================
*/

$deportistas = $_SESSION['convocados'] ?? [];


if (empty($deportistas)) {

    echo "<h2>No hay deportistas convocados</h2>";

    echo "<p>";
    echo "No se encontró información en ";
    echo "<code>\$_SESSION['convocados']</code>.";
    echo "</p>";

    exit;
}


/*
=================================================
DATOS DE LA CONVOCATORIA
=================================================
*/

$fecha = trim($_POST['fecha'] ?? '');
$rival = trim($_POST['rival'] ?? '');
$hora  = trim($_POST['hora'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$notas = trim($_POST['notas'] ?? '');

$categoria_id = $_POST['categoria_id']
    ?? ($_SESSION['convocatoria_categoria_id'] ?? '');


/*
=================================================
GUARDAR DATOS EN SESIÓN
=================================================
*/

$_SESSION['convocatoria_fecha'] = $fecha;
$_SESSION['convocatoria_rival'] = $rival;
$_SESSION['convocatoria_hora']  = $hora;
$_SESSION['convocatoria_lugar'] = $lugar;
$_SESSION['convocatoria_notas'] = $notas;
$_SESSION['convocatoria_categoria_id'] = $categoria_id;


/*
=================================================
VALIDACIONES
=================================================
*/

if ($fecha === '') {

    echo "<h2>Error</h2>";
    echo "<p>La fecha de la convocatoria está vacía.</p>";

    exit;
}


if ($rival === '') {

    echo "<h2>Error</h2>";
    echo "<p>El rival está vacío.</p>";

    exit;
}


if ($hora === '') {

    echo "<h2>Error</h2>";
    echo "<p>La hora está vacía.</p>";

    exit;
}


if ($lugar === '') {

    echo "<h2>Error</h2>";
    echo "<p>El lugar está vacío.</p>";

    exit;
}


/*
=================================================
FORMATEAR FECHA
=================================================
*/

$fecha_formateada = $fecha;

$fecha_objeto = DateTime::createFromFormat('Y-m-d', $fecha);

if ($fecha_objeto !== false) {

    $fecha_formateada = $fecha_objeto->format('d/m/Y');
}


/*
=================================================
FORMATEAR HORA
=================================================
*/

$hora_formateada = $hora;

$hora_objeto = DateTime::createFromFormat('H:i', $hora);

if ($hora_objeto !== false) {

    $minutos = $hora_objeto->format('i');

    if ($minutos === '00') {

        $hora_formateada = $hora_objeto->format('g A');

    } else {

        $hora_formateada = $hora_objeto->format('g:i A');
    }
}


/*
=================================================
FUNCIÓN ESCAPAR HTML
=================================================
*/

function escaparHtml($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
=================================================
OBTENER CATEGORÍAS
=================================================
*/

$categorias = [];

foreach ($deportistas as $deportista) {

    $categoria = trim(
        $deportista['categoria_nombre']
        ?? $deportista['categoria']
        ?? ''
    );

    if ($categoria !== '') {

        $categorias[$categoria] = true;
    }
}

$categorias_texto = implode(
    ', ',
    array_keys($categorias)
);


/*
=================================================
GENERAR TABLA DE DEPORTISTAS
=================================================
*/

$filas_deportistas = '';

$numero = 1;

foreach ($deportistas as $deportista) {

    $nombre = trim(
        $deportista['nombre']
        ?? $deportista['nombre_completo']
        ?? $deportista['deportista']
        ?? ''
    );

    $telefono = trim(
        $deportista['telefono']
        ?? $deportista['celular']
        ?? $deportista['telefono_acudiente']
        ?? ''
    );

    $categoria = trim(
        $deportista['categoria_nombre']
        ?? $deportista['categoria']
        ?? ''
    );

    $entrenador = trim(
        $deportista['entrenador']
        ?? ''
    );

    $acudiente = trim(
        $deportista['acudiente']
        ?? $deportista['nombre_acudiente']
        ?? ''
    );

    $parentesco = trim(
        $deportista['parentesco']
        ?? ''
    );


    $filas_deportistas .= '
        <tr>

            <td class="numero">
                ' . escaparHtml($numero) . '
            </td>

            <td>
                ' . escaparHtml($nombre) . '
            </td>

            <td>
                ' . escaparHtml($telefono) . '
            </td>

            <td>
                ' . escaparHtml($categoria) . '
            </td>

            <td>
                ' . escaparHtml($entrenador) . '
            </td>

            <td>
                ' . escaparHtml($acudiente) . '
            </td>

            <td>
                ' . escaparHtml($parentesco) . '
            </td>

        </tr>
    ';

    $numero++;
}


/*
=================================================
HTML DEL PDF
=================================================
*/

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #222;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.titulo {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 8px;
}

.subtitulo {
    font-size: 12px;
}

.info {
    width: 100%;
    margin-bottom: 20px;
}

.info td {
    padding: 5px;
    border: 1px solid #ccc;
}

.info .label {
    font-weight: bold;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #eeeeee;
    font-weight: bold;
    padding: 6px;
    border: 1px solid #999;
}

td {
    padding: 5px;
    border: 1px solid #ccc;
}

.numero {
    text-align: center;
}

.notas {
    margin-top: 20px;
    border: 1px solid #ccc;
    padding: 10px;
}

</style>

</head>

<body>

<div class="header">

    <div class="titulo">
        BELLAVISTA FC
    </div>

    <div class="subtitulo">
        CONVOCATORIA
    </div>

</div>


<table class="info">

    <tr>

        <td class="label">
            Fecha
        </td>

        <td>
            ' . escaparHtml($fecha_formateada) . '
        </td>

        <td class="label">
            Rival
        </td>

        <td>
            ' . escaparHtml($rival) . '
        </td>

    </tr>


    <tr>

        <td class="label">
            Hora
        </td>

        <td>
            ' . escaparHtml($hora_formateada) . '
        </td>

        <td class="label">
            Lugar
        </td>

        <td>
            ' . escaparHtml($lugar) . '
        </td>

    </tr>


    <tr>

        <td class="label">
            Categoría
        </td>

        <td colspan="3">
            ' . escaparHtml($categorias_texto) . '
        </td>

    </tr>

</table>


<table>

    <thead>

        <tr>

            <th>
                #
            </th>

            <th>
                Jugador
            </th>

            <th>
                Teléfono
            </th>

            <th>
                Categoría
            </th>

            <th>
                Entrenador
            </th>

            <th>
                Acudiente
            </th>

            <th>
                Parentesco
            </th>

        </tr>

    </thead>

    <tbody>

        ' . $filas_deportistas . '

    </tbody>

</table>
';


/*
=================================================
NOTAS
=================================================
*/

if ($notas !== '') {

    $html .= '

    <div class="notas">

        <strong>Notas:</strong><br><br>

        ' . nl2br(escaparHtml($notas)) . '

    </div>

    ';
}


$html .= '

</body>

</html>
';


/*
=================================================
GENERAR PDF CON DOMPDF
=================================================
*/

$dompdf = new Dompdf();


$dompdf->loadHtml($html);


$dompdf->setPaper(
    'A4',
    'portrait'
);


$dompdf->render();


$pdf_generado = $dompdf->output();


/*
=================================================
VALIDAR PDF
=================================================
*/

if (
    empty($pdf_generado) ||
    substr($pdf_generado, 0, 4) !== '%PDF'
) {

    echo "<h2>Error generando PDF</h2>";

    echo "<p>";
    echo "Dompdf no generó un PDF válido.";
    echo "</p>";

    exit;
}


/*
=================================================
NOMBRE DEL PDF
=================================================
*/

$nombre_pdf =
    'BFC-CONVOCATORIA-' .
    date('Ymd-His') .
    '.pdf';


/*
=================================================
ENVIAR WHATSAPP
=================================================
*/

$resultados_whatsapp = [];

$total_intentos = 0;
$total_exitosos = 0;
$total_fallidos = 0;


foreach ($deportistas as $indice => $deportista) {


    /*
    =============================================
    DATOS DEL JUGADOR
    =============================================
    */

    $nombre_deportista = trim(
        $deportista['nombre']
        ?? $deportista['nombre_completo']
        ?? $deportista['deportista']
        ?? ''
    );


    $telefono = trim(
        $deportista['telefono']
        ?? $deportista['celular']
        ?? $deportista['telefono_acudiente']
        ?? ''
    );


    $categoria_jugador = trim(
        $deportista['categoria_nombre']
        ?? $deportista['categoria']
        ?? ''
    );


    /*
    =============================================
    VALIDAR TELÉFONO
    =============================================
    */

    if ($telefono === '') {

        $resultados_whatsapp[$indice] = [

            'nombre' => $nombre_deportista,

            'telefono' => '',

            'ok' => false,

            'http_code' => 0,

            'error' => 'El deportista no tiene teléfono.',

            'respuesta' => null,

            'respuesta_raw' => null,

            'http_media' => 0,

            'error_media' => null,

            'respuesta_media' => null,

            'respuesta_media_raw' => null,

            'media_id' => null
        ];


        $total_intentos++;
        $total_fallidos++;

        continue;
    }


    /*
    =============================================
    PARÁMETROS DE LA PLANTILLA
    =============================================

    Orden esperado:

    1. Nombre jugador
    2. Rival
    3. Fecha
    4. Hora
    5. Lugar
    6. Categoría
    =============================================
    */

    $parametros_whatsapp = [

        $nombre_deportista,

        $rival,

        $fecha_formateada,

        $hora_formateada,

        $lugar,

        $categoria_jugador
    ];


    /*
    =============================================
    ENVIAR
    =============================================
    */

    $resultado = enviarConvocatoriaWhatsApp(

        $telefono,

        $parametros_whatsapp,

        $pdf_generado,

        $nombre_pdf

    );


    /*
    =============================================
    GUARDAR RESULTADO COMPLETO
    =============================================
    */

    $resultado_ok = $resultado['ok'] ?? false;


    $resultados_whatsapp[$indice] = [

        'nombre' =>
            $nombre_deportista,

        'telefono' =>
            $telefono,

        'ok' =>
            $resultado_ok,

        'http_code' =>
            $resultado['http_code'] ?? 0,

        'error' =>
            $resultado['error'] ?? null,

        'respuesta' =>
            $resultado['respuesta'] ?? null,

        'respuesta_raw' =>
            $resultado['respuesta_raw'] ?? null,

        'http_media' =>
            $resultado['http_media'] ?? 0,

        'error_media' =>
            $resultado['error_media'] ?? null,

        'respuesta_media' =>
            $resultado['respuesta_media'] ?? null,

        'respuesta_media_raw' =>
            $resultado['respuesta_media_raw'] ?? null,

        'media_id' =>
            $resultado['media_id'] ?? null
    ];


    $total_intentos++;


    if ($resultado_ok) {

        $total_exitosos++;

    } else {

        $total_fallidos++;
    }
}


/*
=================================================
GUARDAR RESULTADOS EN SESIÓN
=================================================
*/

$_SESSION['resultados_whatsapp_convocatoria']
    = $resultados_whatsapp;


$_SESSION['resumen_whatsapp_convocatoria'] = [

    'total_intentos' =>
        $total_intentos,

    'total_exitosos' =>
        $total_exitosos,

    'total_fallidos' =>
        $total_fallidos,

    'nombre_pdf' =>
        $nombre_pdf
];


/*
=================================================
MODO DIAGNÓSTICO
=================================================

IMPORTANTE:

Durante esta prueba NO descargamos el PDF.

Mostramos toda la información devuelta por Meta.
=================================================
*/

if ($modo_diagnostico === true) {

    ?>

    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>
            Diagnóstico WhatsApp - Bellavista FC
        </title>


        <style>

            body {

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background:
                    #f4f6f8;

                margin:
                    0;

                padding:
                    30px;

                color:
                    #222;
            }


            .contenedor {

                max-width:
                    1200px;

                margin:
                    auto;
            }


            h1 {

                margin-top:
                    0;

                color:
                    #1f2937;
            }


            .resumen {

                display:
                    grid;

                grid-template-columns:
                    repeat(
                        4,
                        minmax(
                            0,
                            1fr
                        )
                    );

                gap:
                    15px;

                margin-bottom:
                    25px;
            }


            .tarjeta {

                background:
                    white;

                border-radius:
                    10px;

                padding:
                    18px;

                box-shadow:
                    0 2px 8px
                    rgba(
                        0,
                        0,
                        0,
                        0.08
                    );
            }


            .tarjeta .numero {

                font-size:
                    28px;

                font-weight:
                    bold;

                margin-top:
                    5px;
            }


            .verde {

                border-left:
                    5px solid
                    #16a34a;
            }


            .rojo {

                border-left:
                    5px solid
                    #dc2626;
            }


            .azul {

                border-left:
                    5px solid
                    #2563eb;
            }


            .gris {

                border-left:
                    5px solid
                    #6b7280;
            }


            .resultado {

                background:
                    white;

                border-radius:
                    10px;

                margin-bottom:
                    25px;

                padding:
                    20px;

                box-shadow:
                    0 2px 8px
                    rgba(
                        0,
                        0,
                        0,
                        0.08
                    );
            }


            .resultado.exito {

                border-left:
                    6px solid
                    #16a34a;
            }


            .resultado.fallo {

                border-left:
                    6px solid
                    #dc2626;
            }


            .jugador {

                font-size:
                    20px;

                font-weight:
                    bold;

                margin-bottom:
                    15px;
            }


            .dato {

                margin:
                    8px 0;
            }


            .etiqueta {

                font-weight:
                    bold;
            }


            .ok {

                color:
                    #15803d;

                font-weight:
                    bold;
            }


            .error {

                color:
                    #b91c1c;

                font-weight:
                    bold;
            }


            .seccion {

                margin-top:
                    20px;
            }


            pre {

                background:
                    #111827;

                color:
                    #e5e7eb;

                padding:
                    15px;

                border-radius:
                    8px;

                overflow-x:
                    auto;

                white-space:
                    pre-wrap;

                word-break:
                    break-word;

                font-size:
                    13px;

                line-height:
                    1.5;
            }


            .boton {

                display:
                    inline-block;

                padding:
                    12px 20px;

                background:
                    #2563eb;

                color:
                    white;

                text-decoration:
                    none;

                border-radius:
                    7px;

                margin-bottom:
                    25px;
            }


            @media (
                max-width: 800px
            ) {

                .resumen {

                    grid-template-columns:
                        1fr 1fr;
                }
            }


            @media (
                max-width: 500px
            ) {

                body {

                    padding:
                        15px;
                }


                .resumen {

                    grid-template-columns:
                        1fr;
                }
            }

        </style>

    </head>


    <body>

    <div class="contenedor">


        <h1>
            Diagnóstico de WhatsApp
        </h1>


        <p>
            <strong>Plantilla:</strong>
            bellavista_convocatoria
        </p>


        <p>
            <strong>PDF:</strong>
            <?= escaparHtml($nombre_pdf) ?>
        </p>


        <p>
            <strong>Modo:</strong>
            DIAGNÓSTICO — el PDF no se descarga.
        </p>


        <a
            href="javascript:history.back()"
            class="boton"
        >
            ← Volver
        </a>


        <!-- =====================================
             RESUMEN
        ====================================== -->

        <div class="resumen">


            <div class="tarjeta azul">

                <div>
                    Total de intentos
                </div>

                <div class="numero">
                    <?= $total_intentos ?>
                </div>

            </div>


            <div class="tarjeta verde">

                <div>
                    Envíos exitosos
                </div>

                <div class="numero">
                    <?= $total_exitosos ?>
                </div>

            </div>


            <div class="tarjeta rojo">

                <div>
                    Envíos fallidos
                </div>

                <div class="numero">
                    <?= $total_fallidos ?>
                </div>

            </div>


            <div class="tarjeta gris">

                <div>
                    Tamaño PDF
                </div>

                <div class="numero">

                    <?= number_format(
                        strlen($pdf_generado) / 1024,
                        1
                    ) ?>

                    KB

                </div>

            </div>


        </div>


        <!-- =====================================
             RESULTADOS
        ====================================== -->

        <?php foreach (
            $resultados_whatsapp
            as $resultado
        ): ?>


            <div class="resultado
                <?= !empty($resultado['ok'])
                    ? 'exito'
                    : 'fallo'
                ?>"
            >


                <div class="jugador">

                    <?= escaparHtml(
                        $resultado['nombre']
                    ) ?>

                </div>


                <div class="dato">

                    <span class="etiqueta">
                        Teléfono:
                    </span>

                    <?= escaparHtml(
                        $resultado['telefono']
                    ) ?>

                </div>


                <hr>


                <!-- =================================
                     ETAPA MEDIA
                ================================== -->

                <div class="seccion">

                    <h2>
                        1. Subida del PDF a Meta
                    </h2>


                    <div class="dato">

                        <span class="etiqueta">
                            HTTP Media:
                        </span>

                        <?= escaparHtml(
                            $resultado['http_media']
                        ) ?>

                    </div>


                    <div class="dato">

                        <span class="etiqueta">
                            Media ID:
                        </span>

                        <?php if (
                            !empty(
                                $resultado['media_id']
                            )
                        ): ?>

                            <span class="ok">

                                <?= escaparHtml(
                                    $resultado['media_id']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span class="error">
                                NO SE GENERÓ
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="dato">

                        <span class="etiqueta">
                            Error cURL Media:
                        </span>

                        <?php if (
                            !empty(
                                $resultado['error_media']
                            )
                        ): ?>

                            <span class="error">

                                <?= escaparHtml(
                                    $resultado['error_media']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span class="ok">
                                Ninguno
                            </span>

                        <?php endif; ?>

                    </div>


                    <div>

                        <strong>
                            Respuesta Media:
                        </strong>

                        <pre><?=
                            escaparHtml(
                                $resultado[
                                    'respuesta_media'
                                ] ?? ''
                            )
                        ?></pre>

                    </div>


                    <div>

                        <strong>
                            Respuesta Media RAW:
                        </strong>

                        <pre><?=
                            escaparHtml(
                                $resultado[
                                    'respuesta_media_raw'
                                ] ?? ''
                            )
                        ?></pre>

                    </div>

                </div>


                <!-- =================================
                     ETAPA TEMPLATE
                ================================== -->

                <div class="seccion">

                    <h2>
                        2. Envío de la plantilla
                    </h2>


                    <div class="dato">

                        <span class="etiqueta">
                            HTTP Template:
                        </span>

                        <?php

                        $http_code =
                            (int)(
                                $resultado[
                                    'http_code'
                                ] ?? 0
                            );

                        ?>

                        <?php if (
                            $http_code >= 200 &&
                            $http_code < 300
                        ): ?>

                            <span class="ok">

                                <?= $http_code ?>

                                — Aceptado por Meta

                            </span>

                        <?php else: ?>

                            <span class="error">

                                <?= $http_code ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="dato">

                        <span class="etiqueta">
                            Error cURL:
                        </span>

                        <?php if (
                            !empty(
                                $resultado['error']
                            )
                        ): ?>

                            <span class="error">

                                <?= escaparHtml(
                                    $resultado['error']
                                ) ?>

                            </span>

                        <?php else: ?>

                            <span class="ok">
                                Ninguno
                            </span>

                        <?php endif; ?>

                    </div>


                    <div>

                        <strong>
                            Respuesta Template:
                        </strong>

                        <pre><?=
                            escaparHtml(
                                $resultado[
                                    'respuesta'
                                ] ?? ''
                            )
                        ?></pre>

                    </div>


                    <div>

                        <strong>
                            Respuesta Template RAW:
                        </strong>

                        <pre><?=
                            escaparHtml(
                                $resultado[
                                    'respuesta_raw'
                                ] ?? ''
                            )
                        ?></pre>

                    </div>

                </div>


            </div>


        <?php endforeach; ?>


        <!-- =====================================
             INFORMACIÓN DEL PDF
        ====================================== -->

        <div class="resultado">

            <h2>
                Información del PDF generado
            </h2>


            <div class="dato">

                <span class="etiqueta">
                    Nombre:
                </span>

                <?= escaparHtml(
                    $nombre_pdf
                ) ?>

            </div>


            <div class="dato">

                <span class="etiqueta">
                    Tamaño:
                </span>

                <?= number_format(
                    strlen($pdf_generado),
                    0,
                    ',',
                    '.'
                ) ?>

                bytes

            </div>


            <div class="dato">

                <span class="etiqueta">
                    Firma PDF:
                </span>

                <span class="ok">
                    <?= escaparHtml(
                        substr(
                            $pdf_generado,
                            0,
                            4
                        )
                    ) ?>
                </span>

            </div>

        </div>


    </div>

    </body>

    </html>

    <?php

    exit;
}


/*
=================================================
DESCARGA NORMAL DEL PDF
=================================================

ESTA PARTE NO SE EJECUTA MIENTRAS:

$modo_diagnostico = true

Cuando terminemos las pruebas, cambiaremos:

$modo_diagnostico = false;

y volverá a descargar el PDF.
=================================================
*/

while (
    ob_get_level() > 0
) {

    ob_end_clean();
}


header(
    'Content-Type: application/pdf'
);


header(
    'Content-Disposition: attachment; filename="' .
    $nombre_pdf .
    '"'
);


header(
    'Content-Length: ' .
    strlen($pdf_generado)
);


echo $pdf_generado;

exit;