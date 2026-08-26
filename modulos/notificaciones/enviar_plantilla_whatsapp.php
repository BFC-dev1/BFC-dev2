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
     * Envía plantillas mediante WhatsApp Cloud API de Meta.
     *
     * También permite:
     *
     * 1. Enviar variables de texto.
     * 2. Subir un PDF a Meta.
     * 3. Obtener el media_id del PDF.
     * 4. Enviar el PDF dentro de una plantilla.
     * 5. Utilizar el nombre real del archivo PDF.
     *
     * ================================================================
     */


    /* ================================================================
    CARGAR CONFIGURACIÓN
    ================================================================ */

    require_once __DIR__ . '/config_whatsapp.php';


    /* ================================================================
    FUNCIÓN PRINCIPAL
    ================================================================ */

    /**
     * Envía una plantilla de WhatsApp.
     *
     * @param string $numero
     * Número del destinatario en formato internacional.
     *
     * @param string $nombre_plantilla
     * Nombre exacto de la plantilla aprobada en Meta.
     *
     * @param string $idioma
     * Código del idioma.
     *
     * @param array $parametros
     * Variables de texto de la plantilla.
     *
     * Ejemplo:
     *
     * [
     *     'Carlos Pérez',
     *     'Juan Pérez',
     *     'Agosto 2026',
     *     '120000'
     * ]
     *
     * @param string|null $ruta_documento
     * Ruta física del PDF que se desea adjuntar.
     *
     * Ejemplo:
     *
     * C:/xampp/htdocs/BFC-dev2/...
     *
     * @return array
     */
    function enviarPlantillaWhatsApp(
        $numero,
        $nombre_plantilla,
        $idioma = 'es',
        $parametros = [],
        $ruta_documento = null
    ) {

        /* ============================================================
        VARIABLES DE CONFIGURACIÓN
        ============================================================ */

        global $whatsapp_token;
        global $whatsapp_api_url;
        global $whatsapp_phone_number_id;
        global $whatsapp_api_version;
        global $whatsapp_timeout;


        /* ============================================================
        VALIDAR TOKEN
        ============================================================ */

        if (
            empty($whatsapp_token) ||
            $whatsapp_token === 'PEGA_AQUI_TU_TOKEN_SEGURO'
        ) {

            return [
                'ok' => false,
                'http_code' => 0,
                'error' => [
                    'message' =>
                        'El Access Token de WhatsApp no está configurado.'
                ],
                'respuesta' => null,
                'respuesta_raw' => null
            ];
        }


        /* ============================================================
        VALIDAR NÚMERO
        ============================================================ */

        $numero = str_replace(
            ['+', ' ', '-', '(', ')'],
            '',
            trim($numero)
        );


        if (!preg_match('/^[0-9]+$/', $numero)) {

            return [
                'ok' => false,
                'http_code' => 0,
                'error' => [
                    'message' =>
                        'El número de WhatsApp no tiene un formato válido.'
                ],
                'respuesta' => null,
                'respuesta_raw' => null
            ];
        }


        /* ============================================================
        VALIDAR PLANTILLA
        ============================================================ */

        $nombre_plantilla = trim($nombre_plantilla);

        if ($nombre_plantilla === '') {

            return [
                'ok' => false,
                'http_code' => 0,
                'error' => [
                    'message' =>
                        'El nombre de la plantilla no puede estar vacío.'
                ],
                'respuesta' => null,
                'respuesta_raw' => null
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

        $components = [];


        /* ============================================================
        COMPONENTE BODY - VARIABLES DE TEXTO
        ============================================================ */

        if (!empty($parametros)) {

            $parameters = [];

            foreach ($parametros as $parametro) {

                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) $parametro
                ];
            }


            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }


        /* ============================================================
        SUBIR DOCUMENTO A META
        ============================================================ */

        $media_id = null;

        if (
            $ruta_documento !== null &&
            trim($ruta_documento) !== ''
        ) {

            /* --------------------------------------------------------
            VERIFICAR EXISTENCIA DEL ARCHIVO
            -------------------------------------------------------- */

            if (!file_exists($ruta_documento)) {

                return [
                    'ok' => false,
                    'http_code' => 0,
                    'error' => [
                        'message' =>
                            'El archivo PDF no existe.',
                        'ruta_documento' =>
                            $ruta_documento
                    ],
                    'respuesta' => null,
                    'respuesta_raw' => null
                ];
            }


            /* --------------------------------------------------------
            VERIFICAR QUE SEA UN ARCHIVO
            -------------------------------------------------------- */

            if (!is_file($ruta_documento)) {

                return [
                    'ok' => false,
                    'http_code' => 0,
                    'error' => [
                        'message' =>
                            'La ruta indicada para el comprobante no corresponde a un archivo.',
                        'ruta_documento' =>
                            $ruta_documento
                    ],
                    'respuesta' => null,
                    'respuesta_raw' => null
                ];
            }


            /* --------------------------------------------------------
            OBTENER NOMBRE REAL DEL ARCHIVO
            -------------------------------------------------------- */

            $nombre_documento = basename($ruta_documento);


            /* --------------------------------------------------------
            VERIFICAR EXTENSIÓN PDF
            -------------------------------------------------------- */

            $extension = strtolower(
                pathinfo(
                    $nombre_documento,
                    PATHINFO_EXTENSION
                )
            );

            if ($extension !== 'pdf') {

                return [
                    'ok' => false,
                    'http_code' => 0,
                    'error' => [
                        'message' =>
                            'El archivo que se intenta enviar no es un PDF.',
                        'archivo' =>
                            $nombre_documento
                    ],
                    'respuesta' => null,
                    'respuesta_raw' => null
                ];
            }


            /* --------------------------------------------------------
            URL PARA SUBIR MEDIA
            -------------------------------------------------------- */

            $url_media =
                'https://graph.facebook.com/' .
                $whatsapp_api_version .
                '/' .
                $whatsapp_phone_number_id .
                '/media';


            /* --------------------------------------------------------
            CREAR ARCHIVO CURL
            -------------------------------------------------------- */

            $archivo_curl = curl_file_create(
                $ruta_documento,
                'application/pdf',
                $nombre_documento
            );


            /* --------------------------------------------------------
            DATOS PARA SUBIR EL PDF
            -------------------------------------------------------- */

            $datos_media = [
                'messaging_product' => 'whatsapp',
                'file' => $archivo_curl,
                'type' => 'application/pdf'
            ];


            /* --------------------------------------------------------
            CURL - SUBIR PDF A META
            -------------------------------------------------------- */

            $ch_media = curl_init(
                $url_media
            );


            curl_setopt_array(
                $ch_media,
                [

                    CURLOPT_POST => true,

                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_HTTPHEADER => [

                        'Authorization: Bearer ' .
                        $whatsapp_token

                    ],

                    CURLOPT_POSTFIELDS =>
                        $datos_media,

                    CURLOPT_TIMEOUT =>
                        $whatsapp_timeout,

                    CURLOPT_SSL_VERIFYPEER => true,

                    CURLOPT_SSL_VERIFYHOST => 2
                ]
            );


            /* --------------------------------------------------------
            EJECUTAR SUBIDA
            -------------------------------------------------------- */

            $respuesta_media =
                curl_exec($ch_media);


            /* --------------------------------------------------------
            INFORMACIÓN HTTP
            -------------------------------------------------------- */

            $http_code_media =
                curl_getinfo(
                    $ch_media,
                    CURLINFO_HTTP_CODE
                );


            $curl_error_media =
                curl_error($ch_media);


            curl_close($ch_media);


            /* --------------------------------------------------------
            ERROR DE CURL
            -------------------------------------------------------- */

            if (
                $respuesta_media === false ||
                !empty($curl_error_media)
            ) {

                return [
                    'ok' => false,
                    'http_code' => $http_code_media,
                    'error' => [
                        'message' =>
                            $curl_error_media !== ''
                            ? $curl_error_media
                            : 'No fue posible subir el PDF a Meta.',
                        'archivo' =>
                            $nombre_documento
                    ],
                    'respuesta' => null,
                    'respuesta_raw' =>
                        $respuesta_media
                ];
            }


            /* --------------------------------------------------------
            CONVERTIR RESPUESTA DE META
            -------------------------------------------------------- */

            $respuesta_media_json =
                json_decode(
                    $respuesta_media,
                    true
                );


            /* --------------------------------------------------------
            VERIFICAR SUBIDA
            -------------------------------------------------------- */

            $media_exitosa = (
                $http_code_media >= 200 &&
                $http_code_media < 300
            );


            if (!$media_exitosa) {

                if (
                    is_array($respuesta_media_json) &&
                    isset($respuesta_media_json['error'])
                ) {

                    $error_media =
                        $respuesta_media_json['error'];

                } else {

                    $error_media = [
                        'message' =>
                            'Meta rechazó la subida del PDF.',
                        'respuesta_raw' =>
                            $respuesta_media
                    ];
                }


                return [
                    'ok' => false,
                    'http_code' => $http_code_media,
                    'error' => $error_media,
                    'respuesta' =>
                        $respuesta_media_json,
                    'respuesta_raw' =>
                        $respuesta_media
                ];
            }


            /* --------------------------------------------------------
            OBTENER MEDIA ID
            -------------------------------------------------------- */

            if (
                !is_array($respuesta_media_json) ||
                empty($respuesta_media_json['id'])
            ) {

                return [
                    'ok' => false,
                    'http_code' => $http_code_media,
                    'error' => [
                        'message' =>
                            'Meta aceptó la subida, pero no devolvió un media_id.',
                        'respuesta_meta' =>
                            $respuesta_media_json
                    ],
                    'respuesta' =>
                        $respuesta_media_json,
                    'respuesta_raw' =>
                        $respuesta_media
                ];
            }


            $media_id =
                $respuesta_media_json['id'];


            /* --------------------------------------------------------
            COMPONENTE DOCUMENT
            -------------------------------------------------------- */

            $components[] = [

                'type' => 'header',

                'parameters' => [

                    [
                        'type' => 'document',

                        'document' => [

                            'id' => $media_id,

                            'filename' =>
                                $nombre_documento
                        ]
                    ]

                ]

            ];
        }


        /* ============================================================
        CONSTRUIR PETICIÓN FINAL
        ============================================================ */

        $datos = [

            'messaging_product' =>
                'whatsapp',

            'to' =>
                $numero,

            'type' =>
                'template',

            'template' => [

                'name' =>
                    $nombre_plantilla,

                'language' => [

                    'code' =>
                        $idioma

                ]

            ]

        ];


        /* ============================================================
        AGREGAR COMPONENTES
        ============================================================ */

        if (!empty($components)) {

            $datos['template']['components'] =
                $components;
        }


        /* ============================================================
        CONVERTIR A JSON
        ============================================================ */

        $json_datos = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        );


        if ($json_datos === false) {

            return [
                'ok' => false,
                'http_code' => 0,
                'error' => [
                    'message' =>
                        'No fue posible convertir la petición de WhatsApp a JSON.'
                ],
                'respuesta' => null,
                'respuesta_raw' => null
            ];
        }


        /* ============================================================
        ENVIAR PLANTILLA A META
        ============================================================ */

        $ch = curl_init(
            $whatsapp_api_url
        );


        curl_setopt_array(
            $ch,
            [

                CURLOPT_POST => true,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_HTTPHEADER => [

                    'Authorization: Bearer ' .
                    $whatsapp_token,

                    'Content-Type: application/json'

                ],

                CURLOPT_POSTFIELDS =>
                    $json_datos,

                CURLOPT_TIMEOUT =>
                    $whatsapp_timeout,

                CURLOPT_SSL_VERIFYPEER => true,

                CURLOPT_SSL_VERIFYHOST => 2

            ]
        );


        /* ============================================================
        EJECUTAR PETICIÓN
        ============================================================ */

        $respuesta =
            curl_exec($ch);


        /* ============================================================
        INFORMACIÓN DE LA PETICIÓN
        ============================================================ */

        $http_code =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );


        $curl_error =
            curl_error($ch);


        curl_close($ch);


        /* ============================================================
        ERROR CURL
        ============================================================ */

        if (
            $respuesta === false ||
            !empty($curl_error)
        ) {

            return [
                'ok' => false,
                'http_code' => $http_code,
                'error' => [
                    'message' =>
                        $curl_error !== ''
                        ? $curl_error
                        : 'No fue posible comunicarse con Meta.'
                ],
                'respuesta' => null,
                'respuesta_raw' => $respuesta
            ];
        }


        /* ============================================================
        RESPUESTA DE META
        ============================================================ */

        $respuesta_json =
            json_decode(
                $respuesta,
                true
            );


        /* ============================================================
        VERIFICAR RESULTADO
        ============================================================ */

        $exitoso = (
            $http_code >= 200 &&
            $http_code < 300
        );


        /* ============================================================
        ERROR DE META
        ============================================================ */

        $error_meta = null;

        if (!$exitoso) {

            if (
                is_array($respuesta_json) &&
                isset($respuesta_json['error'])
            ) {

                $error_meta =
                    $respuesta_json['error'];

            } else {

                $error_meta = [

                    'message' =>
                        'Meta rechazó la solicitud.',

                    'respuesta_raw' =>
                        $respuesta

                ];
            }
        }


        /* ============================================================
        RESULTADO FINAL
        ============================================================ */

        return [

            'ok' =>
                $exitoso,

            'http_code' =>
                $http_code,

            'error' =>
                $error_meta,

            'respuesta' =>
                $respuesta_json,

            'respuesta_raw' =>
                $respuesta,

            'media_id' =>
                $media_id,

            'archivo_documento' =>
                isset($nombre_documento)
                    ? $nombre_documento
                    : null

        ];
    }


    /* ================================================================
    FIN DEL ARCHIVO
    ================================================================ */