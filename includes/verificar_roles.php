<?php

/*
=================================================
VERIFICADOR DE ROLES

Este archivo permite:

- Validar que exista una sesión activa.
- Controlar acceso por roles.
- Redireccionar usuarios sin permisos.

Uso:

require_once("../../includes/verificar_roles.php");

permitirRoles([
    "admin",
    "auditor"
]);

=================================================
*/


/*
=================================================
INICIAR SESIÓN

Si la sesión aún no existe, se inicia.
=================================================
*/

if(session_status() === PHP_SESSION_NONE){

    session_start();

}


/*
=================================================
RUTA BASE DEL SISTEMA

Detecta si está en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/

=================================================
*/

if($_SERVER['HTTP_HOST'] == "localhost"){

    $url_base = "/BFC-dev2";

}else{

    $url_base = "";

}


/*
=================================================
VERIFICAR QUE EL USUARIO ESTÉ AUTENTICADO

El login guarda:

$_SESSION["id_usuario"]

Si no existe sesión:
envía al login.
=================================================
*/

if(!isset($_SESSION["id_usuario"])){

    header("Location: ".$url_base."/auth/login.php");

    exit;

}



/*
=================================================
FUNCIÓN PARA VALIDAR ROLES

Recibe los roles permitidos.

Ejemplo:

permitirRoles([
    "admin",
    "auditor"
]);

Si el usuario no tiene permiso:
lo devuelve al Dashboard.
=================================================
*/

function permitirRoles(array $rolesPermitidos)
{


    /*
    =============================================
    USAR RUTA BASE DEL SISTEMA

    Permite utilizar la variable $url_base
    que fue creada al inicio del archivo.

    Así las redirecciones funcionan en:

    LOCAL:
    /BFC-dev2

    WEB:
    /

    =============================================
    */

    global $url_base;



    /*
    =============================================
    OBTENER ROL ACTUAL
    =============================================
    */

    $rol = strtolower($_SESSION["rol"] ?? "");


    /*
    =============================================
    VALIDAR PERMISOS
    =============================================
    */

    if(!in_array($rol, $rolesPermitidos)){


/*
=========================================
USUARIO SIN PERMISOS

Regresa al Dashboard.

Se utiliza $url_base para que funcione
en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/

sin modificar código.
=========================================
*/

header("Location: ".$url_base."/modulos/Dashboard/index.php");

exit;

    }


}

?>