```php
<?php

/*
====================================================
VERIFICADOR DE PERMISOS
Bellavista FC
====================================================

Este archivo controla los PERMISOS de los módulos
asignados al rol del usuario.

La relación utilizada es:

usuario
   ↓
rol_id
   ↓
rol_permiso
   ↓
permiso_id
   ↓
permiso
   ↓
modulo


Ejemplo:

tienePermiso("usuarios");


Si el rol tiene asignado el permiso:

usuarios

la función devuelve:

true

Si el rol NO tiene ese permiso:

false


====================================================
IMPORTANTE
====================================================

Este archivo contiene la función:

tienePermiso()


NO debe existir otra función con el mismo nombre
en verificar_roles.php.


verificar_roles.php
    ↓
permitirRoles()
    ↓
Control de acceso por ROL


permisos.php
    ↓
tienePermiso()
    ↓
Control de acceso por PERMISO


/*
====================================================
PERMISOS ACTUALES
====================================================

Administración:

usuarios
deportistas
pagos
asistencia
auditoria
cms
reportes


Usuario:

mis_pagos
mi_perfil


Entrenador:

ver_deportistas
convocatoria
reportes_asistencia
reportes_deportistas


====================================================
*/


/*
====================================================
FUNCIÓN: tienePermiso()
====================================================

Consulta si el ROL actual tiene permiso sobre
un módulo determinado.

Ejemplo:

if (tienePermiso("usuarios")) {

    // Mostrar menú Usuarios

}


Otro ejemplo:

if (tienePermiso("pagos")) {

    // Mostrar menú Pagos

}


Devuelve:

true
    ↓
El rol tiene el permiso.


false
    ↓
El rol NO tiene el permiso.


====================================================
*/

function tienePermiso($modulo)
{

    /*
    =================================================
    CONEXIÓN A LA BASE DE DATOS
    =================================================

    conexion.php crea la variable:

    $conexion

    Utilizamos global para poder utilizarla
    dentro de esta función.

    =================================================
    */

    global $conexion;


    /*
    =================================================
    VERIFICAR QUE EXISTA EL ROL EN LA SESIÓN
    =================================================

    El login debe guardar:

    $_SESSION["rol_id"]

    Ejemplo:

    $_SESSION["rol_id"] = 1;

    Si no existe el rol_id, por seguridad
    devolvemos false.

    Esto significa:

    NO mostrar el menú.

    =================================================
    */

    if (!isset($_SESSION["rol_id"])) {

        return false;

    }


    /*
    =================================================
    CONSULTAR PERMISO
    =================================================

    Buscamos una relación entre:

    rol_permiso
         ↓
    permiso


    rol_permiso.rol_id
         ↓
    identifica el rol


    rol_permiso.permiso_id
         ↓
    identifica el permiso


    permiso.id
         ↓
    identifica el permiso


    permiso.modulo
         ↓
    identifica el módulo solicitado.

    =================================================
    */

    $sql = "

        SELECT COUNT(*)

        FROM rol_permiso rp

        INNER JOIN permiso p
            ON p.id = rp.permiso_id

        WHERE rp.rol_id = ?

        AND p.modulo = ?

    ";


    /*
    =================================================
    PREPARAR CONSULTA
    =================================================
    */

    $stmt = $conexion->prepare($sql);


    /*
    =================================================
    EJECUTAR CONSULTA
    =================================================

    Primer parámetro:

    $_SESSION["rol_id"]

    Segundo parámetro:

    $modulo

    Ejemplo:

    tienePermiso("usuarios")

    genera:

    rol_id = rol del usuario
    modulo = usuarios

    =================================================
    */

    $stmt->execute([

        $_SESSION["rol_id"],

        $modulo

    ]);


    /*
    =================================================
    DEVOLVER RESULTADO
    =================================================

    fetchColumn() devuelve la cantidad de registros
    encontrados.

    Si devuelve:

    1 o más
        ↓
    existe permiso
        ↓
    true


    Si devuelve:

    0
        ↓
    no existe permiso
        ↓
    false

    =================================================
    */

    return $stmt->fetchColumn() > 0;

}

?>

