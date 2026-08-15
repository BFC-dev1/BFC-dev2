<?php
/**
 * ============================================================
 * WEBHOOK WHATSAPP - BELLAVISTA FC
 * ============================================================
 *
 * Ruta:
 * /modulos/notificaciones/webhook_whatsapp.php
 *
 * Funciones:
 * - Verificación GET solicitada por Meta.
 * - Recepción POST de eventos de WhatsApp.
 * ============================================================
 */


/* ============================================================
   TOKEN DE VERIFICACIÓN
   ============================================================ */

$verify_token = "BFC_Webhook_2026";


/* ============================================================
   VERIFICACIÓN DEL WEBHOOK - GET
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $mode = isset($_GET['hub_mode'])
        ? $_GET['hub_mode']
        : '';

    $token = isset($_GET['hub_verify_token'])
        ? $_GET['hub_verify_token']
        : '';

    $challenge = isset($_GET['hub_challenge'])
        ? $_GET['hub_challenge']
        : '';


    /*
     * Meta debe enviar:
     *
     * hub_mode = subscribe
     * hub_verify_token = nuestro token
     * hub_challenge = código generado por Meta
     */

    if ($mode === 'subscribe' && $token === $verify_token) {

        http_response_code(200);

        echo $challenge;

        exit;

    }


    /*
     * Si el token no coincide.
     */

    http_response_code(403);

    echo "Token de verificacion incorrecto.";

    exit;
}


/* ============================================================
   RECEPCIÓN DE EVENTOS - POST
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Recibir información enviada por Meta.
     */

    $input = file_get_contents('php://input');


    /*
     * Guardar temporalmente el evento recibido.
     *
     * Esto nos permitirá revisar posteriormente
     * qué información está enviando Meta.
     */

    $archivo = __DIR__ . '/webhook_whatsapp.log';


    $contenido = date('Y-m-d H:i:s') . PHP_EOL;

    $contenido .= $input . PHP_EOL;

    $contenido .= "----------------------------------------" . PHP_EOL;


    /*
     * Agregar el evento al archivo.
     */

    file_put_contents(
        $archivo,
        $contenido,
        FILE_APPEND
    );


    /*
     * Confirmar recepción a Meta.
     */

    http_response_code(200);

    echo "EVENT_RECEIVED";

    exit;
}


/* ============================================================
   OTROS MÉTODOS
   ============================================================ */

http_response_code(405);

echo "Metodo no permitido.";

exit;