<?php

/*
=================================================
VERIFICADOR DE ROLES
Bellavista FC
=================================================

Este archivo se encarga de:

1. Verificar que exista una sesión activa.
2. Verificar que el usuario esté autenticado.
3. Obtener la ruta base del sistema.
4. Controlar el acceso a páginas mediante ROLES.

IMPORTANTE:
-------------------------------------------------
La función tienePermiso() NO está en este archivo.

La función tienePermiso() se encuentra en:

/includes/permisos.php

Esto evita declarar la misma función dos veces.

Por lo tanto:

verificar_roles.php
    ↓
Controla ROLES
    ↓
permitirRoles()


permisos.php
    ↓
Controla PERMISOS
    ↓
tienePermiso()


=================================================
FUNCIONES DISPONIBLES
=================================================

Para proteger una página por rol:

require_once("../../includes/verificar_roles.php");

permitirRoles([
    "admin",
    "auditor"
]);


Para comprobar permisos de módulos se utiliza
la función ubicada en:

/includes/permisos.php

Ejemplos:

tienePermiso("usuarios");

tienePermiso("deportistas");

tienePermiso("pagos");

tienePermiso("asistencia");

tienePermiso("auditoria");

tienePermiso("cms");

tienePermiso("reportes");

=================================================
*/


/*
=================================================
INICIAR SESIÓN
=================================================

Si todavía no existe una sesión activa,
se inicia automáticamente.

Esto permite utilizar variables como:

$_SESSION["id_usuario"]
$_SESSION["rol"]
$_SESSION["nombre"]
$_SESSION["usuario"]

=================================================
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
=================================================
RUTA BASE DEL SISTEMA
=================================================

El proyecto funciona en dos entornos:

LOCAL
-------------------------------------------------
http://localhost/BFC-dev2/


WEB
-------------------------------------------------
https://bellavistafcdev.page.gd/


En LOCAL:

$url_base = "/BFC-dev2";


En WEB:

$url_base = "";


Esto permite utilizar las mismas rutas
en ambos entornos.

=================================================
*/

if (
    isset($_SERVER["HTTP_HOST"]) &&
    $_SERVER["HTTP_HOST"] === "localhost"
) {

    /*
    =============================================
    ENTORNO LOCAL
    =============================================
    */

    $url_base = "/BFC-dev2";

} else {

    /*
    =============================================
    ENTORNO WEB
    =============================================
    */

    $url_base = "";

}


/*
=================================================
VERIFICAR USUARIO AUTENTICADO
=================================================

El sistema de login guarda el ID del usuario
en:

$_SESSION["id_usuario"]


Si esta variable no existe:

1. El usuario no ha iniciado sesión.
2. Se redirecciona al login.
3. Se detiene la ejecución del archivo.

=================================================
*/

if (!isset($_SESSION["id_usuario"])) {

    /*
    =============================================
    REDIRECCIONAR AL LOGIN
    =============================================
    */

    header(
        "Location: "
        . $url_base
        . "/auth/login.php"
    );

    exit;

}


/*
=================================================
CARGAR CONEXIÓN A LA BASE DE DATOS
=================================================

conexion.php crea la variable:

$conexion


Esta variable contiene la conexión PDO
a la base de datos:

bellavistafc


Utilizamos require_once para evitar cargar
el archivo de conexión varias veces.

__DIR__ representa la carpeta actual:

/includes


Por lo tanto:

__DIR__ . "/conexion.php"

corresponde a:

/includes/conexion.php

=================================================
*/

require_once(__DIR__ . "/conexion.php");


/*
=================================================
FUNCIÓN: permitirRoles()
=================================================

Esta función controla el acceso a páginas
según el ROL del usuario.

Ejemplo:

permitirRoles([
    "admin"
]);


También podemos permitir varios roles:

permitirRoles([
    "admin",
    "auditor"
]);


Si el usuario tiene uno de los roles indicados:

    → puede continuar.


Si el usuario NO tiene uno de los roles:

    → es enviado al Dashboard.


IMPORTANTE:
-------------------------------------------------

Esta función protege directamente las páginas.

Por ejemplo:

/modulos/usuarios/index.php

puede utilizar:

permitirRoles([
    "admin"
]);


Esto significa que aunque un usuario escriba
manualmente la URL en el navegador, no podrá
acceder si su rol no está autorizado.

=================================================
*/

function permitirRoles(array $rolesPermitidos)
{

    /*
    =============================================
    UTILIZAR LA RUTA BASE
    =============================================

    $url_base fue creada anteriormente.

    Como la función se encuentra dentro de este
    mismo archivo, necesitamos indicar que vamos
    a utilizar la variable global.

    =============================================
    */

    global $url_base;


    /*
    =============================================
    OBTENER ROL DEL USUARIO
    =============================================

    El login guarda el rol en:

    $_SESSION["rol"]

    Si por alguna razón no existe,
    utilizamos una cadena vacía.

    strtolower() permite comparar sin importar
    si el rol está escrito como:

    ADMIN
    Admin
    admin

    =============================================
    */

    $rol = strtolower(
        $_SESSION["rol"] ?? ""
    );


    /*
    =============================================
    NORMALIZAR ROLES PERMITIDOS
    =============================================

    Convertimos todos los roles permitidos
    a minúsculas.

    Esto evita problemas de comparación.

    Ejemplo:

    ["Admin", "Auditor"]

    se convierte en:

    ["admin", "auditor"]

    =============================================
    */

    $rolesPermitidos = array_map(
        "strtolower",
        $rolesPermitidos
    );


    /*
    =============================================
    COMPROBAR SI EL ROL ESTÁ AUTORIZADO
    =============================================

    in_array() verifica si el rol actual
    se encuentra dentro de los roles permitidos.

    =============================================
    */

    if (!in_array($rol, $rolesPermitidos, true)) {

        /*
        =========================================
        USUARIO SIN PERMISO
        =========================================

        Si el usuario no tiene el rol permitido,
        lo enviamos al Dashboard.

        =========================================
        */

        header(
            "Location: "
            . $url_base
            . "/modulos/Dashboard/index.php"
        );

        /*
        =========================================
        DETENER EJECUCIÓN
        =========================================

        Evita que el usuario continúe ejecutando
        el código de la página protegida.

        =========================================
        */

        exit;

    }

}


/*
=================================================
FIN DEL VERIFICADOR DE ROLES
=================================================

IMPORTANTE:

La función:

tienePermiso()

NO debe agregarse nuevamente aquí.

Esa función pertenece a:

/includes/permisos.php


De esta manera evitamos errores como:

Fatal error:
Cannot redeclare tienePermiso()

=================================================
*/

?>

