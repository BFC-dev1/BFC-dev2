<?php
/*
=========================================================
HEADER GENERAL DE MÓDULOS - BELLAVISTA FC
=========================================================

Este archivo es compartido por TODOS los módulos del
sistema.

Ejemplos:

    Deportistas
    Usuarios
    Auditoría
    Financiero
        - Mensualidades
        - Matrículas
        - Egresos
        - Movimientos

=========================================================
IMPORTANTE
=========================================================

Este archivo NO define:

    $url_base
    $css_url
    $img_url

Esas variables vienen desde:

    includes/config.php

El módulo que utilice este header debe cargar primero:

    require_once(__DIR__ . "/../../../includes/config.php");

y posteriormente:

    include("../../../template/header_modulos.php");

=========================================================
VARIABLES QUE PUEDE DEFINIR CADA MÓDULO
=========================================================

Antes de cargar este header:

    $modulo_actual = 'Financiero';
    $submodulo_actual = 'Mensualidades';

Opcionalmente:

    $menu_modulo = [];

=========================================================
*/


/*
=========================================================
VERIFICAR CONFIGURACIÓN
=========================================================

El header necesita estas variables:

    $url_base
    $css_url
    $img_url

Si no existen, significa que el módulo no cargó
correctamente:

    includes/config.php

No inventamos una URL aquí porque config.php ya se
encarga de detectar LOCAL / WEB.
=========================================================
*/

if (!isset($url_base)) {

    die(
        'Error: $url_base no está definido. ' .
        'Debe cargarse includes/config.php antes de ' .
        'header_modulos.php.'
    );
}

if (!isset($css_url)) {

    die(
        'Error: $css_url no está definido. ' .
        'Debe cargarse includes/config.php antes de ' .
        'header_modulos.php.'
    );
}

if (!isset($img_url)) {

    die(
        'Error: $img_url no está definido. ' .
        'Debe cargarse includes/config.php antes de ' .
        'header_modulos.php.'
    );
}

if (!isset($favicon_url)) {

    die(
        'Error: $favicon_url no está definido. ' .
        'Debe cargarse includes/config.php antes de ' .
        'header_modulos.php.'
    );
}


/*
=========================================================
MÓDULO ACTUAL
=========================================================

Cada módulo debe definir su nombre.

Ejemplo:

    $modulo_actual = 'Financiero';

Si no lo define, mostramos "Módulo".
=========================================================
*/

$modulo_actual = $modulo_actual ?? 'Módulo';


/*
=========================================================
SUBMÓDULO ACTUAL
=========================================================

Ejemplo:

    $submodulo_actual = 'Mensualidades';

Si el módulo no tiene submódulo:

    $submodulo_actual = '';

=========================================================
*/

$submodulo_actual = $submodulo_actual ?? '';


/*
=========================================================
MENÚ DEL MÓDULO
=========================================================

Opcional.

Si un módulo todavía no necesita menú:

    $menu_modulo = [];

Si no fue definido:

    $menu_modulo = [];

Más adelante Financiero podrá enviar:

    Mensualidades
    Matrículas
    Egresos
    Movimientos

=========================================================
*/

$menu_modulo = $menu_modulo ?? [];


/*
=========================================================
ESCAPAR TEXTO PARA HTML
=========================================================
*/

$modulo_actual_html = htmlspecialchars(
    $modulo_actual,
    ENT_QUOTES,
    'UTF-8'
);

$submodulo_actual_html = htmlspecialchars(
    $submodulo_actual,
    ENT_QUOTES,
    'UTF-8'
);


/*
=========================================================
TÍTULO DE LA PÁGINA
=========================================================
*/

if (!empty($submodulo_actual)) {

    $titulo_pagina =
        'BELLAVISTAFC | ' .
        $modulo_actual .
        ' | ' .
        $submodulo_actual;

} else {

    $titulo_pagina =
        'BELLAVISTAFC | ' .
        $modulo_actual;
}

?>

<!doctype html>

<html lang="es">

<head>

    <!-- =================================================
         CONFIGURACIÓN GENERAL DEL DOCUMENTO
         ================================================= -->

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="description"
        content="Sistema de gestión BELLAVISTA FC"
    >


        <!-- =================================================
         FAVICON
         ================================================= -->

    <link
        rel="icon"
        type="image/x-icon"
        href="<?= htmlspecialchars(
            $favicon_url,
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

    <!-- =================================================
         TÍTULO DINÁMICO
         ================================================= -->

    <title>
        <?= htmlspecialchars(
            $titulo_pagina,
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </title>


    <!-- =================================================
         BOOTSTRAP 5.3.2
         =================================================

         Todo el sistema de módulos utilizará Bootstrap 5.

         NO cargamos Bootstrap 4 aquí.
         ================================================= -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         CSS GENERAL DE MÓDULOS
         =================================================

         IMPORTANTE:

         Por ahora conservamos el archivo existente:

             header_deportistas.css

         porque todavía no hemos revisado si todos sus
         estilos son realmente generales.

         Cuando lo revisemos podremos cambiarlo por:

             header_modulos.css

         sin afectar la arquitectura PHP.

         Usamos $css_url proveniente de config.php.

         LOCAL:

             /BFC-dev2/assets

         WEB:

             /style

         ================================================= -->

<!-- =================================================
     CSS GENERAL DE MÓDULOS
     ================================================= -->

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        $css_url,
        ENT_QUOTES,
        'UTF-8'
    ); ?>/modulos.css"
>


    <!-- =================================================
         SWEETALERT2
         ================================================= -->

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    </script>

</head>


<body>


<!-- =====================================================
     NAVBAR PRINCIPAL
     ===================================================== -->

<nav class="navbar navbar-expand navbar-dark bg-dark">

    <div class="container-fluid">

        <ul class="nav navbar-nav">


            <!-- ==========================================
                 NOMBRE DEL SISTEMA
                 ========================================== -->

            <li class="nav-item">

                <a
                    class="nav-link active"
                    href="<?= htmlspecialchars(
                        $url_base,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>/"

                    aria-current="page"
                >

                    BELLAVISTAFC

                </a>

            </li>


            <!-- ==========================================
                 MÓDULO ACTUAL
                 ========================================== -->

            <li class="nav-item">

                <span class="nav-link text-white">

                    <?= $modulo_actual_html; ?>

                </span>

            </li>


            <?php if (!empty($submodulo_actual)): ?>

                <!-- ======================================
                     SUBMÓDULO ACTUAL
                     ====================================== -->

                <li class="nav-item">

                    <span class="nav-link text-white-50">

                        /

                        <?= $submodulo_actual_html; ?>

                    </span>

                </li>

            <?php endif; ?>


            <?php if (!empty($menu_modulo)): ?>

                <!-- ======================================
                     MENÚ ADICIONAL DEL MÓDULO
                     ====================================== -->

                <?php foreach ($menu_modulo as $item): ?>

                    <?php

                    /*
                    =========================================
                    DATOS DEL ELEMENTO
                    =========================================
                    */

                    $nombre_item = $item['nombre'] ?? '';
                    $url_item = $item['url'] ?? '#';


                    /*
                    =========================================
                    CONSTRUIR URL
                    =========================================

                    Si recibimos:

                        modulos/financiero/egresos/

                    se transforma en:

                        /BFC-dev2/modulos/financiero/egresos/

                    en LOCAL.

                    En WEB:

                        /modulos/financiero/egresos/

                    =========================================
                    */

                    if (
                        $url_item !== '#' &&
                        !preg_match(
                            '#^https?://#',
                            $url_item
                        )
                    ) {

                        $url_item =
                            rtrim($url_base, '/') .
                            '/' .
                            ltrim($url_item, '/');
                    }


                    /*
                    =========================================
                    DETERMINAR ELEMENTO ACTIVO
                    =========================================
                    */

                    $activo = false;

                    if (
                        !empty($submodulo_actual) &&
                        $nombre_item === $submodulo_actual
                    ) {

                        $activo = true;
                    }

                    ?>

                    <li class="nav-item">

                        <a
                            class="nav-link <?= $activo ? 'active' : ''; ?>"
                            href="<?= htmlspecialchars(
                                $url_item,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                            <?= htmlspecialchars(
                                $nombre_item,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </a>

                    </li>

                <?php endforeach; ?>

            <?php endif; ?>


        </ul>

    </div>

</nav>


<!-- =====================================================
     CONTENIDO PRINCIPAL

     Los archivos index.php colocarán aquí su contenido.

     Ejemplo:

         <h1>Mensualidades</h1>

         <table>...</table>

     ===================================================== -->

<main class="container">

    <br><br>