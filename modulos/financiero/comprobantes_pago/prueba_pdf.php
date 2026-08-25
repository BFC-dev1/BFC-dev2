<?php

// ======================================================
// CARGAR DOMPDF
// ======================================================

require_once __DIR__ . '/dompdf/autoload.inc.php';

use Dompdf\Dompdf;


// ======================================================
// CREAR INSTANCIA DE DOMPDF
// ======================================================

$dompdf = new Dompdf();


// ======================================================
// CONTENIDO DEL COMPROBANTE DE PRUEBA
// ======================================================

$html = '

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Comprobante de prueba</title>

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
        PRUEBA-001
    </div>


    <div class="dato">
        <strong>Fecha:</strong>
        21/08/2026
    </div>


    <div class="dato">
        <strong>Deportista:</strong>
        Juan Pérez
    </div>


    <div class="dato">
        <strong>Acudiente:</strong>
        Carlos Pérez
    </div>


    <div class="dato">
        <strong>Concepto:</strong>
        Mensualidad Agosto 2026
    </div>


    <div class="dato">
        <strong>Método de pago:</strong>
        Transferencia bancaria
    </div>


    <div class="total">

        TOTAL PAGADO<br><br>

        $120.000

    </div>


    <div class="estado">

        ESTADO: PAGADO

    </div>


    <div class="pie">

        Gracias por realizar su pago.<br>

        Bellavista FC

    </div>


</body>

</html>
';


// ======================================================
// CARGAR HTML
// ======================================================

$dompdf->loadHtml($html);


// ======================================================
// CONFIGURAR TAMAÑO DE PÁGINA
// ======================================================

$dompdf->setPaper('A4', 'portrait');


// ======================================================
// GENERAR PDF
// ======================================================

$dompdf->render();


// ======================================================
// MOSTRAR PDF EN EL NAVEGADOR
// ======================================================

$dompdf->stream(
    'prueba_comprobante.pdf',
    [
        'Attachment' => false
    ]
);