<?php

// ======================================================
// BELLAVISTA FC
// MÓDULO: FINANCIERO
// SUBMÓDULO: MENSUALIDADES
//
// ARCHIVO:
// ver_comprobante.php
//
// FUNCIÓN:
// Buscar el PDF de una transacción y abrirlo
// directamente en el navegador.
// ======================================================


// ======================================================
// 1. INICIAR SESIÓN
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// 2. CARGAR CONFIGURACIÓN
// ======================================================

require_once(
    __DIR__ . "/../../../../includes/config.php"
);

require_once(
    __DIR__ . "/../../../../includes/conexion.php"
);

require_once(
    __DIR__ . "/../../../../includes/verificar_roles.php"
);


// ======================================================
// 3. VERIFICAR PERMISOS
// ======================================================

if (
    !tiene_permiso('mensualidades') &&
    !tiene_permiso('ver_mensualidades')
) {

    http_response_code(403);

    exit(
        'No tienes permisos para consultar este comprobante.'
    );
}


// ======================================================
// 4. RECIBIR ID DE TRANSACCIÓN
// ======================================================

$id_pago = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($id_pago <= 0) {

    http_response_code(400);

    exit(
        'Identificador de pago no válido.'
    );
}


// ======================================================
// 5. CONSULTAR TRANSACCIÓN
// ======================================================

$sql = "

    SELECT

        t.id AS id_pago,

        t.id_cuota,

        t.fecha_pago,

        t.monto_pagado,

        c.mes,

        c.anio,

        d.id AS id_deportista,

        d.nombre AS deportista_nombre,

        d.documento AS documento_deportista

    FROM transacciones_pago t

    INNER JOIN cuotas_mensuales c
        ON c.id = t.id_cuota

    INNER JOIN deportista d
        ON d.id = c.id_deportista

    WHERE t.id = ?

    LIMIT 1

";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    $id_pago
]);


$pago = $stmt->fetch(
    PDO::FETCH_ASSOC
);


// ======================================================
// 6. VERIFICAR TRANSACCIÓN
// ======================================================

if (!$pago) {

    http_response_code(404);

    exit(
        'La transacción de pago no existe.'
    );
}


// ======================================================
// 7. OBTENER FECHA DEL PAGO
// ======================================================

$fecha_pago =
    $pago['fecha_pago'] ?? null;


if (
    empty($fecha_pago) ||
    strtotime($fecha_pago) === false
) {

    http_response_code(404);

    exit(
        'La fecha de pago no es válida.'
    );
}


// ======================================================
// 8. OBTENER AÑO
// ======================================================

$anio_pdf = date(
    'Y',
    strtotime($fecha_pago)
);


// ======================================================
// 9. OBTENER FECHA DEL COMPROBANTE
// ======================================================

$fecha_comprobante = date(
    'Ymd',
    strtotime($fecha_pago)
);


// ======================================================
// 10. LIMPIAR DOCUMENTO
// ======================================================

$documento = preg_replace(
    '/[^0-9A-Za-z_-]/',
    '',
    trim(
        $pago['documento_deportista'] ?? ''
    )
);


if ($documento === '') {

    $documento = 'SIN-DOCUMENTO';
}


// ======================================================
// 11. NÚMERO ESPERADO DEL COMPROBANTE
// ======================================================

$numero_comprobante =
    'BFC-COMP-' .
    $fecha_comprobante .
    '-' .
    $documento;


// ======================================================
// 12. RUTA FÍSICA CORRECTA
// ======================================================
//
// __DIR__:
//
// C:\xampp\htdocs\BFC-dev2\
// modulos\financiero\mensualidades\comprobantes_pago
//
// ../../:
//
// C:\xampp\htdocs\BFC-dev2\
// modulos\financiero
//
// Luego:
//
// comprobantes_pago\comprobantes\pdf\AÑO
//
// RESULTADO:
//
// C:\xampp\htdocs\BFC-dev2\
// modulos\financiero\comprobantes_pago\
// comprobantes\pdf\2026\
//
// ======================================================

$carpeta_pdf =
    __DIR__ .
    '/../../comprobantes_pago/comprobantes/pdf/' .
    $anio_pdf .
    '/';


// ======================================================
// 13. NORMALIZAR RUTA
// ======================================================
//
// realpath() nos permite obtener la ruta física
// absoluta real de Windows.
//
// ======================================================

$carpeta_real = realpath(
    $carpeta_pdf
);


// ======================================================
// 14. VERIFICAR CARPETA
// ======================================================

if (
    $carpeta_real === false ||
    !is_dir($carpeta_real)
) {

    http_response_code(404);

    echo '<!DOCTYPE html>';

    echo '<html lang="es">';

    echo '<head>';

    echo '<meta charset="UTF-8">';

    echo '<title>Comprobante no encontrado</title>';

    echo '</head>';

    echo '<body style="
        font-family:Arial,sans-serif;
        padding:40px;
    ">';

    echo '<h2>Comprobante PDF no encontrado</h2>';

    echo '<p>';

    echo '<strong>Transacción:</strong> #' .
        (int)$id_pago;

    echo '</p>';

    echo '<p>';

    echo '<strong>Ruta buscada:</strong><br>';

    echo '<code>' .
        htmlspecialchars(
            $carpeta_pdf,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</code>';

    echo '</p>';

    echo '<p>';

    echo '<strong>Documento utilizado:</strong><br>';

    echo '<code>' .
        htmlspecialchars(
            $documento,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</code>';

    echo '</p>';

    echo '<p style="color:#b02a37;">';

    echo 'La carpeta de comprobantes no existe.';

    echo '</p>';

    echo '</body>';

    echo '</html>';

    exit;
}


// ======================================================
// 15. BUSCAR ARCHIVO EXACTO
// ======================================================

$ruta_pdf =

    $carpeta_real .
    DIRECTORY_SEPARATOR .
    $numero_comprobante .
    '.pdf';


// ======================================================
// 16. BUSCAR POR DOCUMENTO SI NO EXISTE
// ======================================================
//
// Esto permite encontrar:
//
// BFC-COMP-20260825-1037975835.pdf
//
// aunque la fecha de la transacción sea:
//
// 20260824
//
// ======================================================

if (!is_file($ruta_pdf)) {

    $patron_pdf =

        $carpeta_real .
        DIRECTORY_SEPARATOR .
        'BFC-COMP-*-' .
        $documento .
        '.pdf';


    $archivos_pdf = glob(
        $patron_pdf
    );


    if (!empty($archivos_pdf)) {

        // Ordenar del más reciente al más antiguo.

        usort(

            $archivos_pdf,

            function ($a, $b) {

                return filemtime($b)
                    <=>
                    filemtime($a);

            }

        );


        $ruta_pdf =
            $archivos_pdf[0];
    }
}


// ======================================================
// 17. SEGUNDA BÚSQUEDA
// ======================================================
//
// Por seguridad buscamos también cualquier archivo
// que termine con el documento.
//
// ======================================================

if (!is_file($ruta_pdf)) {

    $patron_general =

        $carpeta_real .
        DIRECTORY_SEPARATOR .
        '*-' .
        $documento .
        '*.pdf';


    $archivos_pdf = glob(
        $patron_general
    );


    if (!empty($archivos_pdf)) {

        usort(

            $archivos_pdf,

            function ($a, $b) {

                return filemtime($b)
                    <=>
                    filemtime($a);

            }

        );


        $ruta_pdf =
            $archivos_pdf[0];
    }
}


// ======================================================
// 18. VERIFICAR PDF
// ======================================================

if (!is_file($ruta_pdf)) {

    http_response_code(404);

    echo '<!DOCTYPE html>';

    echo '<html lang="es">';

    echo '<head>';

    echo '<meta charset="UTF-8">';

    echo '<title>Comprobante PDF no encontrado</title>';

    echo '</head>';

    echo '<body style="
        font-family:Arial,sans-serif;
        padding:40px;
    ">';

    echo '<h2>Comprobante PDF no encontrado</h2>';

    echo '<p>';

    echo '<strong>Transacción:</strong> #' .
        (int)$id_pago;

    echo '</p>';

    echo '<p>';

    echo '<strong>Comprobante esperado:</strong><br>';

    echo '<code>' .
        htmlspecialchars(
            $numero_comprobante . '.pdf',
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</code>';

    echo '</p>';

    echo '<p>';

    echo '<strong>Carpeta buscada:</strong><br>';

    echo '<code>' .
        htmlspecialchars(
            $carpeta_real,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</code>';

    echo '</p>';

    echo '<p>';

    echo '<strong>Documento utilizado:</strong><br>';

    echo '<code>' .
        htmlspecialchars(
            $documento,
            ENT_QUOTES,
            'UTF-8'
        ) .
        '</code>';

    echo '</p>';

    echo '<p>';

    echo 'No se encontró ningún PDF correspondiente a este documento.';

    echo '</p>';

    echo '</body>';

    echo '</html>';

    exit;
}


// ======================================================
// 19. VERIFICAR TAMAÑO
// ======================================================

$tamano_pdf = filesize(
    $ruta_pdf
);


if (
    $tamano_pdf === false ||
    $tamano_pdf <= 0
) {

    http_response_code(500);

    exit(
        'El archivo PDF está vacío o dañado.'
    );
}


// ======================================================
// 20. VERIFICAR EXTENSIÓN
// ======================================================

$extension = strtolower(
    pathinfo(
        $ruta_pdf,
        PATHINFO_EXTENSION
    )
);


if ($extension !== 'pdf') {

    http_response_code(403);

    exit(
        'El archivo solicitado no es un PDF válido.'
    );
}


// ======================================================
// 21. NOMBRE DEL PDF
// ======================================================

$nombre_pdf = basename(
    $ruta_pdf
);


// ======================================================
// 22. LIMPIAR BUFFER
// ======================================================

while (
    ob_get_level() > 0
) {

    ob_end_clean();
}


// ======================================================
// 23. CABECERAS PDF
// ======================================================
//
// inline:
//
// El navegador abre el PDF directamente.
//
// ======================================================

header(
    'Content-Type: application/pdf'
);

header(
    'Content-Disposition: inline; filename="' .
    $nombre_pdf .
    '"'
);

header(
    'Content-Length: ' .
    $tamano_pdf
);

header(
    'Accept-Ranges: bytes'
);

header(
    'Cache-Control: private, max-age=0, must-revalidate'
);

header(
    'Pragma: public'
);

header(
    'Expires: 0'
);


// ======================================================
// 24. ENVIAR PDF
// ======================================================

readfile(
    $ruta_pdf
);


// ======================================================
// 25. TERMINAR
// ======================================================

exit;