    <?php

    /*
    ====================================================================
    BELLAVISTA FC
    REPORTE DE CONVOCATORIA

    Archivo:
        /modulos/reportes/reporte_convocatoria.php

    FUNCIÓN:

    1. Recibir los datos de la convocatoria.
    2. Obtener los jugadores almacenados en sesión.
    3. Generar el PDF utilizando Dompdf.
    4. Mantener el PDF únicamente en memoria.
    5. Enviar el mismo PDF individualmente por WhatsApp.
    6. Descargar el PDF al navegador.

    IMPORTANTE:

    - NO consulta nuevamente la tabla deportista.
    - NO consulta la tabla categoria.
    - NO utiliza la tabla categorias.
    - NO guarda permanentemente el PDF.
    - NO elimina $_SESSION['convocados'].
    - Utiliza:
        enviar_convocatoria_whatsapp.php

    ====================================================================
    */


    /*
    ====================================================================
    INICIAR SESIÓN
    ====================================================================
    */

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    /*
    ====================================================================
    CONFIGURACIÓN GENERAL
    ====================================================================
    */

    require_once(
        __DIR__ . "/../../includes/config.php"
    );


    /*
    ====================================================================
    DOMPDF
    ====================================================================
    */

    require_once(
        __DIR__ .
        "/../financiero/comprobantes_pago/dompdf/autoload.inc.php"
    );


    /*
    ====================================================================
    HELPER WHATSAPP
    ====================================================================
    */

    require_once(
        __DIR__ .
        "/../notificaciones/enviar_convocatoria_whatsapp.php"
    );


    use Dompdf\Dompdf;


    /*
    ====================================================================
    VALIDAR MÉTODO
    ====================================================================
    */

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);

        exit(
            'Método no permitido.'
        );
    }


    /*
    ====================================================================
    OBTENER JUGADORES CONVOCADOS
    ====================================================================
    */

    $deportistas =
        $_SESSION['convocados']
        ?? [];


    /*
    ====================================================================
    VALIDAR JUGADORES
    ====================================================================
    */

    if (empty($deportistas)) {

        http_response_code(400);

        exit(
            'No existen jugadores seleccionados para la convocatoria.'
        );
    }


    /*
    ====================================================================
    OBTENER DATOS DE LA CONVOCATORIA
    ====================================================================
    */

    $fecha =
        trim(
            $_POST['fecha']
            ?? $_SESSION['fecha_convocatoria']
            ?? ''
        );

    $rival =
        trim(
            $_POST['rival']
            ?? $_SESSION['rival_convocatoria']
            ?? ''
        );

    $hora =
        trim(
            $_POST['hora']
            ?? $_SESSION['hora_convocatoria']
            ?? ''
        );

    $lugar =
        trim(
            $_POST['lugar']
            ?? $_SESSION['lugar_convocatoria']
            ?? ''
        );

    $notas =
        trim(
            $_POST['notas']
            ?? $_SESSION['notas_convocatoria']
            ?? ''
        );

    $categoria_id =
        trim(
            $_POST['categoria_id']
            ?? $_SESSION['categoria_convocatoria']
            ?? ''
        );


    /*
    ====================================================================
    ACTUALIZAR DATOS DE CONVOCATORIA EN SESIÓN
    ====================================================================
    */

    $_SESSION['fecha_convocatoria'] =
        $fecha;

    $_SESSION['rival_convocatoria'] =
        $rival;

    $_SESSION['hora_convocatoria'] =
        $hora;

    $_SESSION['lugar_convocatoria'] =
        $lugar;

    $_SESSION['notas_convocatoria'] =
        $notas;

    $_SESSION['categoria_convocatoria'] =
        $categoria_id;


    /*
    ====================================================================
    VALIDAR DATOS PRINCIPALES
    ====================================================================
    */

    if ($fecha === '') {

        http_response_code(400);

        exit(
            'La fecha de la convocatoria es obligatoria.'
        );
    }


    if ($rival === '') {

        http_response_code(400);

        exit(
            'El rival de la convocatoria es obligatorio.'
        );
    }


    if ($hora === '') {

        http_response_code(400);

        exit(
            'La hora de la convocatoria es obligatoria.'
        );
    }


    if ($lugar === '') {

        http_response_code(400);

        exit(
            'El lugar de la convocatoria es obligatorio.'
        );
    }


    /*
    ====================================================================
    FORMATEAR FECHA
    ====================================================================
    */

    $fecha_formateada =
        $fecha;

    $fecha_objeto =
        DateTime::createFromFormat(
            'Y-m-d',
            $fecha
        );


    if ($fecha_objeto !== false) {

        $fecha_formateada =
            $fecha_objeto->format(
                'd/m/Y'
            );
    }


    /*
    ====================================================================
    FORMATEAR HORA

    El input type="time" normalmente entrega:

        13:00
        13:30
        09:00
        18:45

    Aquí la convertimos a formato de 12 horas:

        13:00 -> 1 PM
        13:30 -> 1:30 PM
        09:00 -> 9 AM
        18:00 -> 6 PM
        18:45 -> 6:45 PM

    ====================================================================
    */

    $hora_formateada =
        $hora;

    $hora_objeto =
        DateTime::createFromFormat(
            'H:i',
            $hora
        );


    if ($hora_objeto !== false) {

        /*
        ---------------------------------------------------------------
        SI LOS MINUTOS SON 00
        ---------------------------------------------------------------

        Ejemplo:

            13:00 -> 1 PM

        ---------------------------------------------------------------
        */

        if (
            $hora_objeto->format('i') === '00'
        ) {

            $hora_formateada =
                $hora_objeto->format(
                    'g A'
                );

        } else {

            /*
            -----------------------------------------------------------
            SI TIENE MINUTOS
            -----------------------------------------------------------

            Ejemplo:

                13:30 -> 1:30 PM

            -----------------------------------------------------------
            */

            $hora_formateada =
                $hora_objeto->format(
                    'g:i A'
                );
        }
    }


    /*
    ====================================================================
    FUNCIÓN PARA ESCAPAR HTML
    ====================================================================
    */

    function escaparHtml($valor)
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }


    /*
    ====================================================================
    OBTENER CATEGORÍAS DESDE LA SESIÓN
    ====================================================================
    */

    $categorias = [];


    foreach (
        $deportistas as $deportista
    ) {

        $categoria_jugador =
            trim(
                $deportista['categoria']
                ?? ''
            );


        if ($categoria_jugador !== '') {

            $categorias[] =
                $categoria_jugador;
        }
    }


    /*
    ====================================================================
    ELIMINAR CATEGORÍAS REPETIDAS
    ====================================================================
    */

    $categorias =
        array_values(
            array_unique(
                $categorias
            )
        );


    /*
    ====================================================================
    NOMBRE DE CATEGORÍA
    ====================================================================
    */

    if (!empty($categorias)) {

        $nombre_categoria =
            implode(
                ', ',
                $categorias
            );

    } else {

        $nombre_categoria =
            'Categoría general';
    }


    /*
    ====================================================================
    CONSTRUIR TABLA DE JUGADORES
    ====================================================================

    IMPORTANTE:

    La tabla utiliza:

        #
        Jugador
        Teléfono
        Categoría
        Entrenador
        Acudiente
        Parentesco

    NO SE UTILIZA:

        documento

    ====================================================================
    */

    $filas_jugadores = '';

    $numero_jugador = 1;


    foreach (
        $deportistas as $deportista
    ) {

        /*
        ---------------------------------------------------------------
        NOMBRE DEL JUGADOR
        ---------------------------------------------------------------
        */

        $nombre =
            trim(
                $deportista['nombre']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        TELÉFONO DEL JUGADOR
        ---------------------------------------------------------------

        IMPORTANTE:

        Se toma directamente de:

            $deportista['telefono']

        NO se utiliza documento.

        ---------------------------------------------------------------
        */

        $telefono =
            trim(
                $deportista['telefono']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        ENTRENADOR
        ---------------------------------------------------------------
        */

        $entrenador =
            trim(
                $deportista['entrenador']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        ACUDIENTE
        ---------------------------------------------------------------
        */

        $acudiente =
            trim(
                $deportista['acudiente']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        PARENTESCO
        ---------------------------------------------------------------
        */

        $parentesco =
            trim(
                $deportista['parentesco']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        CATEGORÍA
        ---------------------------------------------------------------
        */

        $categoria_jugador =
            trim(
                $deportista['categoria']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        CATEGORÍA DE RESPALDO
        ---------------------------------------------------------------
        */

        if (
            $categoria_jugador === ''
        ) {

            $categoria_jugador =
                $nombre_categoria;
        }


        /*
        ---------------------------------------------------------------
        FILA DEL PDF
        ---------------------------------------------------------------
        */

        $filas_jugadores .= '

            <tr>

                <td class="numero">
                    ' .
                    escaparHtml(
                        $numero_jugador
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $nombre
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $telefono
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $categoria_jugador
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $entrenador
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $acudiente
                    ) .
                    '
                </td>

                <td>
                    ' .
                    escaparHtml(
                        $parentesco
                    ) .
                    '
                </td>

            </tr>

        ';


        $numero_jugador++;
    }


    /*
    ====================================================================
    NOTAS
    ====================================================================
    */

    $notas_html =
        $notas !== ''
            ? nl2br(
                escaparHtml(
                    $notas
                )
            )
            : 'Sin observaciones';


    /*
    ====================================================================
    HTML DEL PDF
    ====================================================================
    */

    $html = '

    <!DOCTYPE html>

    <html>

    <head>

    <meta charset="UTF-8">

    <title>
        Convocatoria Bellavista FC
    </title>


    <style>

    @page {
        margin: 35px 35px 40px 35px;
    }


    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #222;
    }


    /*
    =================================================
    ENCABEZADO
    =================================================
    */

    .encabezado {
        text-align: center;
        margin-bottom: 18px;
    }


    .titulo {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 4px;
    }


    .subtitulo {
        font-size: 15px;
        font-weight: bold;
        margin-bottom: 15px;
    }


    /*
    =================================================
    INFORMACIÓN
    =================================================
    */

    .info {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }


    .info td {
        border: 1px solid #d0d0d0;
        padding: 7px;
    }


    .info .etiqueta {
        width: 18%;
        font-weight: bold;
        background: #f1f1f1;
    }


    /*
    =================================================
    TÍTULOS
    =================================================
    */

    .seccion {
        font-size: 13px;
        font-weight: bold;
        margin-top: 15px;
        margin-bottom: 8px;
    }


    /*
    =================================================
    TABLA DE JUGADORES
    =================================================
    */

    .jugadores {
        width: 100%;
        border-collapse: collapse;
    }


    .jugadores th {
        background: #212529;
        color: #ffffff;
        border: 1px solid #000000;
        padding: 6px;
        text-align: center;
        font-size: 9px;
    }


    .jugadores td {
        border: 1px solid #bdbdbd;
        padding: 6px;
        font-size: 9px;
    }


    .jugadores tbody tr:nth-child(even) {
        background: #f7f7f7;
    }


    .numero {
        width: 25px;
        text-align: center;
    }


    /*
    =================================================
    OBSERVACIONES
    =================================================
    */

    .observaciones {
        border: 1px solid #bdbdbd;
        padding: 9px;
        min-height: 35px;
    }


    /*
    =================================================
    TOTAL
    =================================================
    */

    .total {
        margin-top: 15px;
        font-size: 11px;
    }


    /*
    =================================================
    PIE
    =================================================
    */

    .pie {
        margin-top: 25px;
        text-align: center;
        font-size: 9px;
    }

    </style>

    </head>


    <body>


    <!-- =========================================================
        ENCABEZADO
    ========================================================= -->

    <div class="encabezado">

        <div class="titulo">
            BELLAVISTA FC
        </div>

        <div class="subtitulo">
            CONVOCATORIA
        </div>

    </div>


    <!-- =========================================================
        INFORMACIÓN DE LA CONVOCATORIA
    ========================================================= -->

    <table class="info">

        <tr>

            <td class="etiqueta">
                Fecha
            </td>

            <td>
                ' .
                escaparHtml(
                    $fecha_formateada
                ) .
                '
            </td>


            <td class="etiqueta">
                Rival
            </td>

            <td>
                ' .
                escaparHtml(
                    $rival
                ) .
                '
            </td>

        </tr>


        <tr>

            <td class="etiqueta">
                Hora
            </td>

            <td>
                ' .
                escaparHtml(
                    $hora_formateada
                ) .
                '
            </td>


            <td class="etiqueta">
                Lugar
            </td>

            <td>
                ' .
                escaparHtml(
                    $lugar
                ) .
                '
            </td>

        </tr>


        <tr>

            <td class="etiqueta">
                Categoría
            </td>

            <td colspan="3">
                ' .
                escaparHtml(
                    $nombre_categoria
                ) .
                '
            </td>

        </tr>

    </table>


    <!-- =========================================================
        JUGADORES
    ========================================================= -->

    <div class="seccion">
        JUGADORES CONVOCADOS
    </div>


    <table class="jugadores">

        <thead>

            <tr>

                <th>
                    #
                </th>

                <th>
                    Jugador
                </th>

                <th>
                    Teléfono
                </th>

                <th>
                    Categoría
                </th>

                <th>
                    Entrenador
                </th>

                <th>
                    Acudiente
                </th>

                <th>
                    Parentesco
                </th>

            </tr>

        </thead>


        <tbody>

            ' .
            $filas_jugadores .
            '

        </tbody>

    </table>


    <!-- =========================================================
        OBSERVACIONES
    ========================================================= -->

    <div class="seccion">
        OBSERVACIONES
    </div>


    <div class="observaciones">

        ' .
        $notas_html .
        '

    </div>


    <!-- =========================================================
        TOTAL
    ========================================================= -->

    <div class="total">

        <strong>
            Total de jugadores convocados:
        </strong>

        ' .
        count(
            $deportistas
        ) .
        '

    </div>


    <!-- =========================================================
        PIE
    ========================================================= -->

    <div class="pie">

        Bellavista FC

    </div>


    </body>

    </html>

    ';


    /*
    ====================================================================
    CREAR DOMPDF
    ====================================================================
    */

    $dompdf =
        new Dompdf();


    /*
    ====================================================================
    CONFIGURAR PAPEL
    ====================================================================
    */

    $dompdf->setPaper(
        'letter',
        'portrait'
    );


    /*
    ====================================================================
    CARGAR HTML
    ====================================================================
    */

    $dompdf->loadHtml(
        $html,
        'UTF-8'
    );


    /*
    ====================================================================
    GENERAR PDF
    ====================================================================
    */

    $dompdf->render();


    /*
    ====================================================================
    OBTENER PDF EN MEMORIA
    ====================================================================
    */

    $pdf_generado =
        $dompdf->output();


    /*
    ====================================================================
    VALIDAR PDF
    ====================================================================
    */

    if (
        empty($pdf_generado) ||
        substr(
            $pdf_generado,
            0,
            4
        ) !== '%PDF'
    ) {

        http_response_code(500);

        exit(
            'No fue posible generar el PDF de la convocatoria.'
        );
    }


    /*
    ====================================================================
    NOMBRE DEL ARCHIVO
    ====================================================================
    */

    $nombre_pdf =
        'BFC-CONVOCATORIA-' .
        date('Ymd-His') .
        '.pdf';


    /*
    ====================================================================
    RESULTADOS DE WHATSAPP
    ====================================================================
    */

    $resultados_whatsapp = [];


    /*
    ====================================================================
    ENVIAR CONVOCATORIA POR WHATSAPP
    ====================================================================

    Parámetros:

    {{1}} = Nombre jugador
    {{2}} = Rival
    {{3}} = Fecha
    {{4}} = Hora
    {{5}} = Lugar
    {{6}} = Categoría

    ====================================================================
    */

    foreach (
        $deportistas as $indice => $deportista
    ) {

        /*
        ---------------------------------------------------------------
        NOMBRE
        ---------------------------------------------------------------
        */

        $nombre_deportista =
            trim(
                $deportista['nombre']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        TELÉFONO
        ---------------------------------------------------------------
        */

        $telefono =
            trim(
                $deportista['telefono']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        CATEGORÍA
        ---------------------------------------------------------------
        */

        $categoria_jugador =
            trim(
                $deportista['categoria']
                ?? ''
            );


        /*
        ---------------------------------------------------------------
        CATEGORÍA DE RESPALDO
        ---------------------------------------------------------------
        */

        if (
            $categoria_jugador === ''
        ) {

            $categoria_jugador =
                $nombre_categoria;
        }


        /*
        ---------------------------------------------------------------
        VALIDAR TELÉFONO
        ---------------------------------------------------------------
        */

        if ($telefono === '') {

            $resultados_whatsapp[$indice] = [

                'nombre' =>
                    $nombre_deportista,

                'telefono' =>
                    '',

                'ok' =>
                    false,

                'error' =>
                    'El jugador no tiene teléfono registrado.'

            ];

            continue;
        }


        /*
        ---------------------------------------------------------------
        PARÁMETROS DEL TEMPLATE
        ---------------------------------------------------------------
        */

        $parametros_whatsapp = [

            $nombre_deportista,

            $rival,

            $fecha_formateada,

            $hora_formateada,

            $lugar,

            $categoria_jugador

        ];


        /*
        ---------------------------------------------------------------
        ENVIAR WHATSAPP
        ---------------------------------------------------------------
        */

        $resultado =
            enviarConvocatoriaWhatsApp(

                $telefono,

                $parametros_whatsapp,

                $pdf_generado,

                $nombre_pdf

            );


        /*
        ---------------------------------------------------------------
        GUARDAR RESULTADO
        ---------------------------------------------------------------
        */

        $resultados_whatsapp[$indice] = [

            'nombre' =>
                $nombre_deportista,

            'telefono' =>
                $telefono,

            'ok' =>
                $resultado['ok']
                ?? false,

            'http_code' =>
                $resultado['http_code']
                ?? 0,

            'error' =>
                $resultado['error']
                ?? null,

            'media_id' =>
                $resultado['media_id']
                ?? null

        ];
    }


    /*
    ====================================================================
    GUARDAR RESULTADOS EN SESIÓN
    ====================================================================
    */

    $_SESSION[
        'resultados_convocatoria_whatsapp'
    ] =
        $resultados_whatsapp;


    /*
    ====================================================================
    CONTADORES
    ====================================================================
    */

    $total_envios =
        count(
            $resultados_whatsapp
        );

    $total_exitosos = 0;

    $total_fallidos = 0;


    foreach (
        $resultados_whatsapp as $resultado
    ) {

        if (
            !empty(
                $resultado['ok']
            )
        ) {

            $total_exitosos++;

        } else {

            $total_fallidos++;
        }
    }


    /*
    ====================================================================
    GUARDAR RESUMEN
    ====================================================================
    */

    $_SESSION[
        'resumen_convocatoria_whatsapp'
    ] = [

        'total' =>
            $total_envios,

        'exitosos' =>
            $total_exitosos,

        'fallidos' =>
            $total_fallidos,

        'fecha' =>
            date(
                'Y-m-d H:i:s'
            )

    ];


    /*
    ====================================================================
    LIMPIAR BUFFER
    ====================================================================
    */

    while (
        ob_get_level() > 0
    ) {

        ob_end_clean();
    }


    /*
    ====================================================================
    CABECERAS DEL PDF
    ====================================================================
    */

    header(
        'Content-Type: application/pdf'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $nombre_pdf .
        '"'
    );

    header(
        'Content-Length: ' .
        strlen(
            $pdf_generado
        )
    );

    header(
        'Cache-Control: private, max-age=0, must-revalidate'
    );

    header(
        'Pragma: public'
    );


    /*
    ====================================================================
    ENVIAR PDF
    ====================================================================
    */

    echo $pdf_generado;


    /*
    ====================================================================
    FIN
    ====================================================================
    */
