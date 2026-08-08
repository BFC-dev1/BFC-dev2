<?php

/*
=================================================
CONFIGURACIÓN GENERAL DEL SISTEMA

Define la ruta base del proyecto para que
todos los enlaces funcionen correctamente
tanto en desarrollo como en producción.

LOCAL:
http://localhost/BFC-dev2/
http://127.0.0.1/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/

=================================================
*/

/*
=================================================
DETECTAR ENTORNO

Se configura automáticamente la ruta base,
la carpeta de estilos CSS y la carpeta
de imágenes según el entorno donde se
ejecute el sistema.

LOCAL:
URL: http://localhost/BFC-dev2/
CSS: assets/
IMG: assets/img/

WEB:
URL: https://bellavistafcdev.page.gd/
CSS: style/
IMG: public/img/

=================================================
*/

if(
    $_SERVER["HTTP_HOST"] == "localhost"
    ||
    $_SERVER["HTTP_HOST"] == "127.0.0.1"
){

    /*
    =============================================
    ENTORNO LOCAL
    =============================================

    Proyecto ejecutándose en XAMPP.

    Rutas:
    CSS -> /BFC-dev2/assets/
    IMG -> /BFC-dev2/assets/img/

    =============================================
    */

    // Ruta base del proyecto
    $url_base = "/BFC-dev2";

    // Carpeta donde se encuentran los archivos CSS
    $css_base = "assets";

    // Carpeta donde se encuentran las imágenes
    $img_base = "assets/img";

}else{

    /*
    =============================================
    ENTORNO WEB
    =============================================

    Proyecto publicado en InfinityFree.

    Rutas:
    CSS -> /style/
    IMG -> /public/img/

    =============================================
    */

    // Ruta base del sitio web
    $url_base = "";

    // Carpeta donde se encuentran los archivos CSS
    $css_base = "style";

    // Carpeta donde se encuentran las imágenes
    $img_base = "public/img";

}

/*
=================================================
VERIFICAR PERMISOS DEL USUARIO
=================================================
*/
if (!function_exists('tiene_permiso')) {
    function tiene_permiso($modulo) {
        // Si el usuario es administrador, tiene acceso total
        if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'administrador') {
            return true;
        }
        
        // Verifica si el módulo está en la lista guardada en la sesión
        return isset($_SESSION['permisos']) && is_array($_SESSION['permisos']) && in_array($modulo, $_SESSION['permisos']);
    }
}