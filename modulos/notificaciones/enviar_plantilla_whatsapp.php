<?php
/**
 * ================================================================
 * ENVÍO DE PLANTILLAS WHATSAPP - BELLAVISTA FC
 * ================================================================
 *
 * Archivo:
 * /modulos/notificaciones/enviar_plantilla_whatsapp.php
 *
 * Función:
 * Envía mensajes utilizando plantillas aprobadas por Meta
 * mediante WhatsApp Cloud API.
 *
 * IMPORTANTE:
 * - NO contiene directamente el Access Token.
 * - Utiliza config_whatsapp.php
 * - Permite enviar plantillas con variables:
 *
 *      {{1}}
 *      {{2}}
 *      {{3}}
 *      etc.
 *
 * Ejemplo:
 *
 * plantilla:
 *
 * Hola {{1}}, te recordamos que la mensualidad
 * de {{2}} vence el {{3}}.
 *
 * parámetros:
 *
 * [
 *     'Carlos',
 *     'Bellavista FC',
 *     '20/08/2026'
 * ]
 *
 * ================================================================
 */


/* ================================================================
   CARGAR CONFIGURACIÓN
   ================================================================ */

/*
 * Cargamos las credenciales y configuración central
 * de WhatsApp Cloud API.
 *
 * El archivo contiene:
 *
 * - $whatsapp_token
 * - $whatsapp_phone_number_id
 * - $whatsapp_api_version
 * - $whatsapp_api_url
 */
require_once __DIR__ . '/config_whatsapp.php';


/* ================================================================
   FUNCIÓN: enviarPlantillaWhatsApp()
   ================================================================ */

/**
 * Envía una plantilla de WhatsApp mediante Meta.
 *
 * @param string $numero
 * Número del destinatario en formato internacional.
 *
 * Ejemplo Colombia:
 *
 * 573128979466
 *
 *
 * @param string $nombre_plantilla
 * Nombre EXACTO de la plantilla creada en Meta.
 *
 * Ejemplo:
 *
 * bellavista_vencimiento_mensualidad
 *
 *
 * @param string $idioma
 * Código del idioma de la plantilla.
 *
 * Ejemplo:
 *
 * es
 *
 *
 * @param array $parametros
 * Valores que reemplazarán:
 *
 * {{1}}
 * {{2}}
 * {{3}}
 *
 * Ejemplo:
 *
 * [
 *     'Carlos',
 *     'Mensualidad',
 *     '20/08/2026'
 * ]
 *
 *
 * @return array
 * Devuelve:
 *
 * [
 *     'ok' => true/false,
 *     'http_code' => 200,
 *     'error' => null,
 *     'respuesta' => [...]
 * ]
 */
function enviarPlantillaWhatsApp(
    $numero,
    $nombre_plantilla,
    $idioma = 'es',
    $parametros = []
) {

    /* ============================================================
       VARIABLES DE CONFIGURACIÓN
       ============================================================ */

    global $whatsapp_token;
    global $whatsapp_api_url;
    global $whatsapp_timeout;


    /* ============================================================
       VALIDAR TOKEN
       ============================================================ */

    /*
     * No hacemos la petición si el token no está configurado.
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
       VALIDAR NÚMERO
       ============================================================ */

    /*
     * Quitamos:
     *
     * +
     * espacios
     * -
     * ()
     *
     * para dejar únicamente el número.
     */
    $numero = str_replace(
        ['+', ' ', '-', '(', ')'],
        '',
        trim($numero)
    );


    /*
     * El número debe contener solamente dígitos.
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
       VALIDAR NOMBRE DE PLANTILLA
       ============================================================ */

    $nombre_plantilla = trim($nombre_plantilla);

    if ($nombre_plantilla === '') {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'El nombre de la plantilla no puede estar vacío.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       VALIDAR IDIOMA
       ============================================================ */

    $idioma = trim($idioma);

    if ($idioma === '') {
        $idioma = 'es';
    }


    /* ============================================================
       CONSTRUIR COMPONENTES
       ============================================================ */

    /*
     * Inicialmente no agregamos componentes.
     *
     * Si la plantilla NO tiene variables:
     *
     * components = []
     *
     * Si tiene variables:
     *
     * components = [
     *     [
     *         'type' => 'body',
     *         'parameters' => [...]
     *     ]
     * ]
     */
    $components = [];


    /* ============================================================
       AGREGAR VARIABLES DE LA PLANTILLA
       ============================================================ */

    /*
     * Si recibimos parámetros, los convertimos
     * al formato requerido por WhatsApp Cloud API.
     */
    if (!empty($parametros)) {

        $parameters = [];

        foreach ($parametros as $parametro) {

            $parameters[] = [
                'type' => 'text',
                'text' => (string) $parametro
            ];
        }


        /*
         * Las variables de texto se envían
         * dentro del componente body.
         */
        $components[] = [
            'type' => 'body',
            'parameters' => $parameters
        ];
    }


    /* ============================================================
       CONSTRUIR DATOS DE LA PETICIÓN
       ============================================================ */

    /*
     * Estructura principal requerida por
     * WhatsApp Cloud API.
     */
    $datos = [
        'messaging_product' => 'whatsapp',

        'to' => $numero,

        'type' => 'template',

        'template' => [
            'name' => $nombre_plantilla,

            'language' => [
                'code' => $idioma
            ]
        ]
    ];


    /*
     * Si existen componentes, los agregamos
     * a la plantilla.
     */
    if (!empty($components)) {

        $datos['template']['components'] = $components;
    }


    /* ============================================================
       CONVERTIR A JSON
       ============================================================ */

    $json_datos = json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );


    /*
     * Comprobar que JSON sea válido.
     */
    if ($json_datos === false) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No fue posible convertir los datos de la plantilla a JSON.',
            'respuesta' => null
        ];
    }


    /* ============================================================
       INICIAR CURL
       ============================================================ */

    $ch = curl_init($whatsapp_api_url);


    /* ============================================================
       CONFIGURAR CURL
       ============================================================ */

    curl_setopt_array(
        $ch,
        [

            /*
             * Método HTTP.
             */
            CURLOPT_POST => true,


            /*
             * Devuelve la respuesta de Meta.
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
             * Datos enviados a Meta.
             */
            CURLOPT_POSTFIELDS => $json_datos,


            /*
             * Tiempo máximo de espera.
             */
            CURLOPT_TIMEOUT => $whatsapp_timeout,


            /*
             * Seguridad SSL.
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


    /*
     * Capturamos cualquier error de cURL.
     */
    $curl_error = curl_error($ch);


    /* ============================================================
       CERRAR CURL
       ============================================================ */

    curl_close($ch);


    /* ============================================================
       MANEJAR ERROR CURL
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