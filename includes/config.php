<?php

/*
=================================================
CONFIGURACIÓN GENERAL DEL SISTEMA
=================================================
*/


/*
=================================================
DETECTAR ENTORNO
=================================================
*/

$host = $_SERVER['HTTP_HOST'] ?? '';


// =================================================
// ENTORNO LOCAL - XAMPP
// =================================================

if (
    $host === 'localhost' ||
    str_starts_with($host, 'localhost:') ||
    $host === '127.0.0.1' ||
    str_starts_with($host, '127.0.0.1:')
) {

    /*
    =============================================
    LOCAL

    http://localhost/BFC-dev2/

    CSS:
    /BFC-dev2/assets/

    IMÁGENES:
    /BFC-dev2/assets/img/
    =============================================
    */

    $url_base = '/BFC-dev2';

    $css_base = 'assets';

    $img_base = 'assets/img';

} else {

    /*
    =============================================
    WEB

    https://bellavistafcdev.page.gd/

    CSS:
    /style/

    IMÁGENES:
    /public/img/
    =============================================
    */

    $url_base = '';

    $css_base = 'style';

    $img_base = 'public/img';
}


/*
=================================================
RUTAS COMPLETAS
=================================================
*/

$css_url = $url_base . '/' . $css_base;

$img_url = $url_base . '/' . $img_base;


// =================================================
// FAVICON
// =================================================

$favicon_url = $url_base . '/icono.ico';


/*
=================================================
SESIÓN
=================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
=================================================
VERIFICAR PERMISOS DEL USUARIO
=================================================
*/

if (!function_exists('tiene_permiso')) {

    function tiene_permiso($modulo)
    {

        // Administrador = acceso total
        if (
            isset($_SESSION['rol']) &&
            strtolower($_SESSION['rol']) === 'administrador'
        ) {
            return true;
        }

        // Verificar permisos guardados en sesión
        return (
            isset($_SESSION['permisos']) &&
            is_array($_SESSION['permisos']) &&
            in_array($modulo, $_SESSION['permisos'])
        );
    }
}