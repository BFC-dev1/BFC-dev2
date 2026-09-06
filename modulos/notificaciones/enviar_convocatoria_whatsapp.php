<?php

/*
=========================================================
BELLAVISTA FC
WHATSAPP - CONVOCATORIA

Archivo:
    /modulos/notificaciones/enviar_convocatoria_whatsapp.php

FUNCIONES:

1. Recibir un número de WhatsApp.
2. Recibir los parámetros del template.
3. Recibir el PDF en memoria.
4. Crear temporalmente el PDF.
5. Subir el PDF a WhatsApp Cloud API.
6. Obtener el media_id.
7. Enviar el template bellavista_convocatoria.
8. Eliminar inmediatamente el archivo temporal.

IMPORTANTE:

- NO modifica enviar_plantilla_whatsapp.php.
- NO guarda el PDF permanentemente.
- El archivo temporal se elimina después de subirlo.
- Utiliza exclusivamente las variables de
  config_whatsapp.php.
=========================================================
*/


/*
=========================================================
CONFIGURACIÓN DE WHATSAPP
=========================================================
*/

require_once(
    __DIR__ . "/config_whatsapp.php"
);


/*
=========================================================
FUNCIÓN PRINCIPAL
=========================================================
*/

function enviarConvocatoriaWhatsApp(
    $numero,
    $parametros = [],
    $pdf = '',
    $nombre_pdf = 'convocatoria.pdf'
) {

    /*
    =====================================================
    VALIDAR TOKEN
    =====================================================
    */

    if (
        !isset($whatsapp_token) ||
        trim($whatsapp_token) === ''
    ) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No existe el token de WhatsApp.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


    /*
    =====================================================
    VALIDAR URL DE API
    =====================================================
    */

    if (
        !isset($whatsapp_api_url) ||
        trim($whatsapp_api_url) === ''
    ) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No existe la URL de WhatsApp Cloud API.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


    /*
    =====================================================
    VALIDAR NÚMERO
    =====================================================
    */

    $numero = preg_replace(
        '/[^0-9]/',
        '',
        (string) $numero
    );


    /*
    =====================================================
    CONVERTIR NÚMERO COLOMBIANO

    Ejemplo:

        3001234567

    se convierte en:

        573001234567
    =====================================================
    */

    if (
        strlen($numero) === 10 &&
        str_starts_with($numero, '3')
    ) {

        $numero = '57' . $numero;
    }


    /*
    =====================================================
    VALIDAR NÚMERO FINAL
    =====================================================
    */

    if ($numero === '') {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'Número de WhatsApp vacío.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


    /*
    =====================================================
    VALIDAR PDF
    =====================================================
    */

    if (
        empty($pdf) ||
        substr($pdf, 0, 4) !== '%PDF'
    ) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'El PDF recibido no es válido.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


/*
=========================================================
VALIDAR PARÁMETROS DEL TEMPLATE
=========================================================
*/

if (count($parametros) !== 6) {

    return [
        'ok' => false,
        'http_code' => 0,
        'error' => 'La plantilla bellavista_convocatoria requiere exactamente 6 parámetros.',
        'respuesta' => null,
        'respuesta_raw' => null,
        'media_id' => null
    ];
}

    /*
    =====================================================
    VALIDAR NOMBRE DEL PDF
    =====================================================
    */

    $nombre_pdf = trim(
        (string) $nombre_pdf
    );

    if ($nombre_pdf === '') {

        $nombre_pdf = 'convocatoria.pdf';
    }


    /*
    =====================================================
    CREAR ARCHIVO TEMPORAL

    WhatsApp Cloud API necesita recibir el documento
    mediante multipart/form-data.

    Por eso se crea temporalmente un archivo físico.

    IMPORTANTE:
    Este archivo NO queda guardado permanentemente.
    =====================================================
    */

    $archivo_temporal = tempnam(
        sys_get_temp_dir(),
        'bfc_conv_'
    );


    /*
    =====================================================
    VALIDAR ARCHIVO TEMPORAL
    =====================================================
    */

    if ($archivo_temporal === false) {

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No fue posible crear el archivo temporal.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


    /*
    =====================================================
    GUARDAR PDF TEMPORALMENTE
    =====================================================
    */

    $resultado_archivo = file_put_contents(
        $archivo_temporal,
        $pdf
    );


    /*
    =====================================================
    VALIDAR ESCRITURA DEL PDF
    =====================================================
    */

    if ($resultado_archivo === false) {

        @unlink(
            $archivo_temporal
        );

        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'No fue posible preparar el PDF.',
            'respuesta' => null,
            'respuesta_raw' => null,
            'media_id' => null
        ];
    }


    /*
    =====================================================
    CREAR URL PARA SUBIR MEDIA

    Nuestra configuración contiene:

        $whatsapp_api_url

    cuyo valor termina en:

        /messages

    Para subir archivos necesitamos:

        /media
    =====================================================
    */

    $url_media = preg_replace(
        '/\/messages\/?$/',
        '/media',
        rtrim(
            $whatsapp_api_url,
            '/'
        )
    );


    /*
    =====================================================
    CREAR ARCHIVO CURL
    =====================================================
    */

    $archivo_curl = curl_file_create(
        $archivo_temporal,
        'application/pdf',
        $nombre_pdf
    );


    /*
    =====================================================
    DATOS PARA SUBIR EL PDF
    =====================================================
    */

    $post_fields = [

        'messaging_product' =>
            'whatsapp',

        'file' =>
            $archivo_curl
    ];


    /*
    =====================================================
    CURL - SUBIR PDF A META
    =====================================================
    */

    $ch = curl_init(
        $url_media
    );


    curl_setopt_array(
        $ch,
        [

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                $post_fields,

            CURLOPT_HTTPHEADER => [

                'Authorization: Bearer ' .
                $whatsapp_token

            ],

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                $whatsapp_timeout ?? 60

        ]
    );


    /*
    =====================================================
    EJECUTAR SUBIDA DEL PDF
    =====================================================
    */

    $respuesta_media = curl_exec(
        $ch
    );


    /*
    =====================================================
    OBTENER INFORMACIÓN DE LA RESPUESTA
    =====================================================
    */

    $http_media = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    $error_curl_media = curl_error(
        $ch
    );


    /*
    =====================================================
    CERRAR CURL
    =====================================================
    */

    curl_close(
        $ch
    );


    /*
    =====================================================
    ELIMINAR ARCHIVO TEMPORAL

    El PDF deja de existir físicamente en el servidor
    después de subirlo a Meta.
    =====================================================
    */

    @unlink(
        $archivo_temporal
    );


    /*
    =====================================================
    DECODIFICAR RESPUESTA DE MEDIA
    =====================================================
    */

    $datos_media = json_decode(
        $respuesta_media,
        true
    );


    /*
    =====================================================
    VALIDAR RESPUESTA DE MEDIA
    =====================================================
    */

    if (
        $http_media < 200 ||
        $http_media >= 300 ||
        empty(
            $datos_media['id']
        )
    ) {

        return [
            'ok' => false,

            'http_code' =>
                $http_media,

            'error' =>
                $error_curl_media !== ''
                    ? $error_curl_media
                    : (
                        $datos_media['error']['message']
                        ?? 'No fue posible subir el PDF a WhatsApp.'
                    ),

            'respuesta' =>
                $datos_media,

            'respuesta_raw' =>
                $respuesta_media,

            'media_id' =>
                null
        ];
    }


    /*
    =====================================================
    OBTENER MEDIA ID
    =====================================================
    */

    $media_id =
        $datos_media['id'];


    /*
    =====================================================
    PREPARAR PARÁMETROS DEL BODY

    Ejemplo:

        $parametros = [

            nombre,
            rival,
            fecha,
            hora,
            lugar,
            categoria

        ];

    Se convierten en:

        {{1}}
        {{2}}
        {{3}}
        {{4}}
        {{5}}
        {{6}}
    =====================================================
    */

    $parametros_body = [];


    foreach (
        $parametros as $parametro
    ) {

        $parametros_body[] = [

            'type' =>
                'text',

            'text' =>
                (string) $parametro

        ];
    }


    /*
    =====================================================
    CREAR COMPONENTES DEL TEMPLATE
    =====================================================
    */

    $components = [];


    /*
    -----------------------------------------------------
    BODY
    -----------------------------------------------------
    */

    if (
        !empty($parametros_body)
    ) {

        $components[] = [

            'type' =>
                'body',

            'parameters' =>
                $parametros_body

        ];
    }


    /*
    -----------------------------------------------------
    HEADER - DOCUMENTO
    -----------------------------------------------------
    */

    $components[] = [

        'type' =>
            'header',

        'parameters' => [

            [

                'type' =>
                    'document',

                'document' => [

                    'id' =>
                        $media_id,

                    'filename' =>
                        $nombre_pdf

                ]

            ]

        ]

    ];


    /*
    =====================================================
    PREPARAR MENSAJE
    =====================================================
    */

    $mensaje = [

        'messaging_product' =>
            'whatsapp',

        'to' =>
            $numero,

        'type' =>
            'template',

        'template' => [

            'name' =>
    'bellavista_convocatoria',

            'language' => [

                'code' =>
                    'es'

            ],

            'components' =>
                $components

        ]

    ];


    /*
    =====================================================
    URL DE ENVÍO

    IMPORTANTE:

    Se utiliza directamente:

        $whatsapp_api_url

    proveniente de:

        config_whatsapp.php

    NO se utiliza:

        $phone_number_id

    NO se utiliza:

        $whatsapp_access_token

    =====================================================
    */

    $ch = curl_init(
        $whatsapp_api_url
    );


    /*
    =====================================================
    CONFIGURAR CURL - ENVÍO DEL TEMPLATE
    =====================================================
    */

    curl_setopt_array(
        $ch,
        [

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                json_encode(
                    $mensaje,
                    JSON_UNESCAPED_UNICODE
                ),

            CURLOPT_HTTPHEADER => [

                'Authorization: Bearer ' .
                $whatsapp_token,

                'Content-Type: application/json'

            ],

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                $whatsapp_timeout ?? 60

        ]
    );


    /*
    =====================================================
    EJECUTAR ENVÍO
    =====================================================
    */

    $respuesta = curl_exec(
        $ch
    );


    /*
    =====================================================
    OBTENER CÓDIGO HTTP
    =====================================================
    */

    $http_code = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    /*
    =====================================================
    OBTENER ERROR CURL
    =====================================================
    */

    $error_curl = curl_error(
        $ch
    );


    /*
    =====================================================
    CERRAR CURL
    =====================================================
    */

    curl_close(
        $ch
    );


    /*
    =====================================================
    DECODIFICAR RESPUESTA
    =====================================================
    */

    $datos_respuesta = json_decode(
        $respuesta,
        true
    );


    /*
    =====================================================
    DETERMINAR RESULTADO
    =====================================================
    */

    $ok = (
        $http_code >= 200 &&
        $http_code < 300
    );


    /*
    =====================================================
    OBTENER ERROR DE META
    =====================================================
    */

    $error_meta =
        $datos_respuesta['error']['message']
        ?? null;


    /*
    =====================================================
    DEVOLVER RESULTADO
    =====================================================
    */

    return [

        'ok' =>
            $ok,

        'http_code' =>
            $http_code,

        'error' =>
            $error_curl !== ''
                ? $error_curl
                : $error_meta,

        'respuesta' =>
            $datos_respuesta,

        'respuesta_raw' =>
            $respuesta,

        'media_id' =>
            $media_id

    ];
}
