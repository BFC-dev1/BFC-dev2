<?php
/*
|--------------------------------------------------------------------------
| PRUEBA DE WHATSAPP - BELLAVISTA FC
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/enviar_whatsapp.php';


// ================================================================
// NÚMERO DESTINATARIO
// ================================================================
// IMPORTANTE:
// Coloca aquí TU número personal autorizado en Meta.
//
// Formato internacional:
// Colombia: 57XXXXXXXXXX
// Sin +, sin espacios y sin guiones.
// ================================================================

$numero_destinatario = '573128979466';


// ================================================================
// MENSAJE DE PRUEBA
// ================================================================

$mensaje = "Hola. Esta es una prueba de WhatsApp enviada desde BFC-dev2.\n\n"
         . "Bellavista FC - Sistema de Notificaciones";


// ================================================================
// ENVÍO
// ================================================================

$resultado = enviarWhatsApp(
    $numero_destinatario,
    $mensaje
);


// ================================================================
// MOSTRAR RESULTADO
// ================================================================

header('Content-Type: text/plain; charset=utf-8');

echo "============================================\n";
echo " PRUEBA WHATSAPP - BELLAVISTA FC\n";
echo "============================================\n\n";

echo "Código HTTP: " . $resultado['http_code'] . "\n\n";

if ($resultado['ok']) {

    echo "ENVÍO EXITOSO\n\n";

} else {

    echo "ERROR EN EL ENVÍO\n\n";

    if (!empty($resultado['error'])) {
        echo "Error cURL:\n";
        echo $resultado['error'] . "\n\n";
    }
}

echo "Respuesta de Meta:\n";
echo json_encode(
    $resultado['respuesta'],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);