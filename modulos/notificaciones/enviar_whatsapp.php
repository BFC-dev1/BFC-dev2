<?php
/**
 * ================================================================
 * ENVÍO DE MENSAJES WHATSAPP - BELLAVISTA FC
 * ================================================================
 *
 * Archivo:
 * /modulos/notificaciones/enviar_whatsapp.php
 *
 * Función:
 * Contiene las funciones encargadas de comunicarse con
 * WhatsApp Cloud API de Meta para enviar mensajes.
 *
 * Este archivo NO contiene directamente el Access Token.
 *
 * El token y las demás credenciales se cargan desde:
 *
 * /modulos/notificaciones/config_whatsapp.php
 *
 * ================================================================
 */


/* ================================================================
   CARGAR CONFIGURACIÓN
   ================================================================ */

/*
 * Cargamos la configuración central de WhatsApp.
 *
 * __DIR__ permite que la ruta funcione independientemente
 * de desde qué archivo sea llamada esta función.
 */

require_once __DIR__ . '/config_whatsapp.php';


/* ================================================================
   FUNCIÓN: enviarWhatsApp()
   ================================================================ */

/**
 * Envía un mensaje de texto mediante WhatsApp Cloud API.
 *
 * @param string $numero
 * Número del destinatario en formato internacional.
 *
 * Ejemplo Colombia:
 * 573128979466
 *
 * IMPORTANTE:
 * No utilizar:
 * +573128979466
 *
 * No utilizar:
 * 57 312 897 9466
 *
 *
 * @param string $mensaje
 * Texto que se desea enviar.
 *
 * @return array
 * Devuelve información sobre el resultado del envío.
 */

function enviarWhatsApp($numero, $mensaje)
{
    /* ============================================================
       VARIABLES DE CONFIGURACIÓN
       ============================================================ */

    global $whatsapp_token;
    global $whatsapp_api_url;
    global $whatsapp_timeout;


    /* ============================================================
       VALIDACIÓN DEL TOKEN
       ============================================================ */

    /*
     * Evitamos intentar realizar una petición si todavía
     * no se ha configurado el token.
     */

    if (
        empty($whatsapp_token) ||
        $whatsapp_token === 'PEGA_AQUI_TU_TOKEN_SEGURO'
    ) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'El Access Token de WhatsApp no está configurado.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       VALIDACIÓN DEL NÚMERO
       ============================================================ */

    /*
     * Eliminamos espacios, guiones y el signo + por seguridad.
     */

    $numero = str_replace(
        ['+', ' ', '-', '(', ')'],
        '',
        trim($numero)
    );


    /*
     * Verificamos que solamente queden números.
     */

    if (!preg_match('/^[0-9]+$/', $numero)) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'El número de WhatsApp no tiene un formato válido.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       VALIDACIÓN DEL MENSAJE
       ============================================================ */

    $mensaje = trim($mensaje);


    if ($mensaje === '') {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'El mensaje de WhatsApp no puede estar vacío.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       CONSTRUIR DATOS DE LA SOLICITUD
       ============================================================ */

    /*
     * Estructura requerida por WhatsApp Cloud API para
     * enviar un mensaje de texto.
     */

    $datos = [
        'messaging_product' => 'whatsapp',

        'to' => $numero,

        'type' => 'text',

        'text' => [
            'body' => $mensaje
        ]
    ];


    /* ============================================================
       CONVERTIR A JSON
       ============================================================ */

    $json_datos = json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );


    /*
     * Comprobamos que la conversión a JSON haya funcionado.
     */

    if ($json_datos === false) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No fue posible convertir los datos a JSON.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       INICIAR CURL
       ============================================================ */

    $ch = curl_init($whatsapp_api_url);


    /* ============================================================
       CONFIGURACIÓN CURL
       ============================================================ */

    curl_setopt_array(
        $ch,
        [

            /*
             * Método HTTP:
             * POST
             */
            CURLOPT_POST => true,


            /*
             * Devuelve la respuesta de Meta en lugar
             * de mostrarla directamente.
             */
            CURLOPT_RETURNTRANSFER => true,


            /*
             * Encabezados HTTP.
             */
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $whatsapp_token,
                'Content-Type: application/json'
            ],


            /*
             * Información que enviamos a Meta.
             */
            CURLOPT_POSTFIELDS => $json_datos,


            /*
             * Tiempo máximo de espera.
             */
            CURLOPT_TIMEOUT => $whatsapp_timeout,


            /*
             * Permite comprobar correctamente el certificado
             * SSL de Meta.
             */
            CURLOPT_SSL_VERIFYPEER => true,

            CURLOPT_SSL_VERIFYHOST => 2
        ]
    );


    /* ============================================================
       EJECUTAR PETICIÓN
       ============================================================ */

    $respuesta = curl_exec($ch);


    /* ============================================================
       INFORMACIÓN DE LA PETICIÓN
       ============================================================ */

    $http_code = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    $curl_error = curl_error($ch);


    /* ============================================================
       CERRAR CURL
       ============================================================ */

    curl_close($ch);


    /* ============================================================
       MANEJO DE ERROR CURL
       ============================================================ */

    if ($respuesta === false || !empty($curl_error)) {

        return [
            'ok' => false,
            'http_code' => $http_code,
            'error' => $curl_error !== ''
                ? $curl_error
                : 'No fue posible comunicarse con Meta.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       CONVERTIR RESPUESTA DE META
       ============================================================ */

    $respuesta_json = json_decode(
        $respuesta,
        true
    );


    /* ============================================================
       COMPROBAR RESULTADO HTTP
       ============================================================ */

    $exitoso = (
        $http_code >= 200 &&
        $http_code < 300
    );


    /* ============================================================
       DEVOLVER RESULTADO
       ============================================================ */

    return [
        'ok' => $exitoso,

        'http_code' => $http_code,

        'error' => $exitoso
            ? null
            : 'Meta rechazó la solicitud.',

        'respuesta' => $respuesta_json
    ];
}


/* ================================================================
   FIN DEL ARCHIVO
   ================================================================ */