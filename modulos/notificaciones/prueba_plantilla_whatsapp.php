<?php
/**
 * ================================================================
 * PRUEBA DE PLANTILLA WHATSAPP - BELLAVISTA FC
 * ================================================================
 *
 * Archivo:
 * /modulos/notificaciones/prueba_plantilla_whatsapp.php
 *
 * Función:
 * Permite probar manualmente una plantilla de WhatsApp
 * antes de conectarla con el módulo de mensualidades.
 *
 * IMPORTANTE:
 * Este archivo es TEMPORAL para pruebas.
 *
 * No modifica:
 * - webhook_whatsapp.php
 * - prueba_whatsapp.php
 * - enviar_whatsapp.php
 *
 * ================================================================
 */


/* ================================================================
   CARGAR FUNCIÓN DE ENVÍO DE PLANTILLAS
   ================================================================ */

require_once __DIR__ . '/enviar_plantilla_whatsapp.php';


/* ================================================================
   CONFIGURACIÓN DE LA PRUEBA
   ================================================================ */

/*
 * Número personal autorizado en Meta.
 *
 * Formato internacional:
 *
 * Colombia:
 * 57XXXXXXXXXX
 *
 * Sin:
 * +
 * espacios
 * guiones
 */

$numero_destinatario = '573128979466';


/* ================================================================
   PLANTILLA A PROBAR
   ================================================================ */

/*
 * IMPORTANTE:
 *
 * El nombre debe coincidir EXACTAMENTE con el nombre
 * que aparece en Meta.
 *
 * Por ahora utilizaremos:
 *
 * bellavista_vencimiento_mensualidad
 */

$nombre_plantilla = 'bellavista_mensualidad_vencida';


/* ================================================================
   IDIOMA
   ================================================================ */

/*
 * Usamos español.
 *
 * Si Meta muestra posteriormente otro código de idioma
 * para esta plantilla, lo ajustaremos aquí.
 */

$idioma = 'es';


/* ================================================================
   VARIABLES DE LA PLANTILLA
   ================================================================ */

/*
 * Estos valores son SOLO DE PRUEBA.
 *
 * {{1}} = Nombre del acudiente
 * {{2}} = Nombre del deportista
 * {{3}} = Mes
 * {{4}} = Valor
 */

$parametros = [
    'Carlos',
    'Juan Pérez',
    'agosto 2026',
    '120000'
];


/* ================================================================
   ENVIAR PLANTILLA
   ================================================================ */

$resultado = enviarPlantillaWhatsApp(
    $numero_destinatario,
    $nombre_plantilla,
    $idioma,
    $parametros
);


/* ================================================================
   MOSTRAR RESULTADO
   ================================================================ */

header(
    'Content-Type: text/plain; charset=utf-8'
);


echo "============================================\n";
echo " PRUEBA PLANTILLA WHATSAPP - BELLAVISTA FC\n";
echo "============================================\n\n";


echo "Número destinatario:\n";
echo $numero_destinatario . "\n\n";


echo "Plantilla:\n";
echo $nombre_plantilla . "\n\n";


echo "Idioma:\n";
echo $idioma . "\n\n";


echo "Variables enviadas:\n";
echo json_encode(
    $parametros,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
echo "\n\n";


echo "Código HTTP:\n";
echo $resultado['http_code'] . "\n\n";


/* ================================================================
   RESULTADO
   ================================================================ */

if ($resultado['ok']) {

    echo "============================================\n";
    echo " ENVÍO DE PLANTILLA EXITOSO\n";
    echo "============================================\n\n";

} else {

    echo "============================================\n";
    echo " ERROR EN EL ENVÍO DE LA PLANTILLA\n";
    echo "============================================\n\n";

    if (!empty($resultado['error'])) {

        echo "Error:\n";
        echo $resultado['error'] . "\n\n";
    }
}


/* ================================================================
   RESPUESTA COMPLETA DE META
   ================================================================ */

echo "Respuesta de Meta:\n";

echo json_encode(
    $resultado['respuesta'],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);

echo "\n";