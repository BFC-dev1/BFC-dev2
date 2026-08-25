<?php

// ======================================================
// GENERAR COMPROBANTE DE PAGO EN PDF
// ======================================================

// ------------------------------------------------------
// CARGAR DOMPDF
// ------------------------------------------------------

require_once __DIR__ . '/dompdf/autoload.inc.php';

use Dompdf\Dompdf;


// ------------------------------------------------------
// RECIBIR DATOS
// ------------------------------------------------------

$numero_comprobante = $_GET['numero'] ?? 'PRUEBA-001';
$fecha              = $_GET['fecha'] ?? date('d/m/Y');
$deportista         = $_GET['deportista'] ?? 'Juan Pérez';
$acudiente          = $_GET['acudiente'] ?? 'Carlos Pérez';
$concepto           = $_GET['concepto'] ?? 'Mensualidad Agosto 2026';
$metodo_pago        = $_GET['metodo_pago'] ?? 'Transferencia';
$valor              = isset($_GET['valor'])
                    ? (float) $_GET['valor']
                    : 120000;
$estado             = $_GET['estado'] ?? 'PAGADO';


// ------------------------------------------------------
// FORMATEAR VALOR
// ------------------------------------------------------

$valor_formateado = '$' . number_format(
    $valor,
    0,
    ',',
    '.'
);


// ------------------------------------------------------
// SEGURIDAD PARA HTML
// ------------------------------------------------------

$numero_comprobante = htmlspecialchars(
    $numero_comprobante,
    ENT_QUOTES,
    'UTF-8'
);

$fecha = htmlspecialchars(
    $fecha,
    ENT_QUOTES,
    'UTF-8'
);

$deportista = htmlspecialchars(
    $deportista,
    ENT_QUOTES,
    'UTF-8'
);

$acudiente = htmlspecialchars(
    $acudiente,
    ENT_QUOTES,
    'UTF-8'
);

$concepto = htmlspecialchars(
    $concepto,
    ENT_QUOTES,
    'UTF-8'
);

$metodo_pago = htmlspecialchars(
    $metodo_pago,
    ENT_QUOTES,
    'UTF-8'
);

$estado = htmlspecialchars(
    $estado,
    ENT_QUOTES,
    'UTF-8'
);


// ======================================================
// CREAR HTML DEL COMPROBANTE
// ======================================================

$html = '

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Comprobante de pago</title>

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
            border-bottom: 1px solid #999;
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

        <strong>No. comprobante:</strong>

        ' . $numero_comprobante . '

    </div>


    <div class="dato">

        <strong>Fecha:</strong>

        ' . $fecha . '

    </div>


    <div class="dato">

        <strong>Deportista:</strong>

        ' . $deportista . '

    </div>


    <div class="dato">

        <strong>Acudiente:</strong>

        ' . $acudiente . '

    </div>


    <div class="dato">

        <strong>Concepto:</strong>

        ' . $concepto . '

    </div>


    <div class="dato">

        <strong>Método de pago:</strong>

        ' . $metodo_pago . '

    </div>


    <div class="total">

        TOTAL PAGADO

        <br><br>

        ' . $valor_formateado . '

    </div>


    <div class="estado">

        ESTADO: ' . $estado . '

    </div>


    <div class="pie">

        Gracias por realizar su pago.<br>

        Bellavista FC

    </div>


</body>

</html>
';


// ======================================================
// CREAR DOMPDF
// ======================================================

$dompdf = new Dompdf();


// ------------------------------------------------------
// CARGAR HTML
// ------------------------------------------------------

$dompdf->loadHtml($html);


// ------------------------------------------------------
// CONFIGURAR PÁGINA
// ------------------------------------------------------

$dompdf->setPaper(
    'A4',
    'portrait'
);


// ------------------------------------------------------
// GENERAR PDF
// ------------------------------------------------------

$dompdf->render();


// ------------------------------------------------------
// MOSTRAR PDF
// ------------------------------------------------------

$nombre_archivo = 'comprobante_' .
                  $numero_comprobante .
                  '.pdf';


$dompdf->stream(
    $nombre_archivo,
    [
        'Attachment' => false
    ]
);